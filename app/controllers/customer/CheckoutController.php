<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../models/OrderModel.php";
require_once __DIR__ . "/../../models/OrderDetailModel.php";
require_once __DIR__ . "/../../models/CartModel.php";
require_once __DIR__ . "/../../models/UserModel.php";

class CheckoutController {

    public function index() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (empty($_SESSION['checkout_items'])) {
        header("Location: /app/views/customer/Cart.php");
        exit();
    }

    $cartModel = new CartModel();
    $userModel = new UserModel();

    $customer_id = $_SESSION['customer_id'] ?? null;
    if (!$customer_id) {
        // Thử gán mặc định CUS001 để test, sau này bỏ
        // $customer_id = 'CUS001';
        header("Location: /app/views/customer/Login.php");
        exit();
    }

    // Lấy thông tin khách hàng và địa chỉ
    $customer_info = $userModel->getCustomerById($customer_id);
    $default_address = $userModel->getDefaultAddress($customer_id);
    if (!$default_address) {
        $addresses = $userModel->getAddresses($customer_id);
        $default_address = !empty($addresses) ? $addresses[0] : null;
    }

    // Lấy tất cả sản phẩm trong giỏ
    $all_cart_items = $cartModel->getCartItems($customer_id);
    
    // QUAN TRỌNG: Xác định danh sách ID cần lọc
    // $_SESSION['checkout_items'] có thể là mảng chứa ['cart_item_id' => ...] hoặc ['product_id' => ...]
    // hoặc đơn giản là mảng các cart_item_id
    $selected_ids = [];
    if (!empty($_SESSION['checkout_items'])) {
        // Trường hợp 1: mảng các object có khóa 'cart_item_id'
        if (isset($_SESSION['checkout_items'][0]['cart_item_id'])) {
            $selected_ids = array_column($_SESSION['checkout_items'], 'cart_item_id');
        }
        // Trường hợp 2: mảng các object có khóa 'product_id'
        elseif (isset($_SESSION['checkout_items'][0]['product_id'])) {
            $selected_ids = array_column($_SESSION['checkout_items'], 'product_id');
        }
        // Trường hợp 3: mảng đơn giản (các giá trị là cart_item_id)
        else {
            $selected_ids = $_SESSION['checkout_items'];
        }
    }

    $checkout_products = [];
    $subtotal = 0;

    if ($all_cart_items && !empty($selected_ids)) {
        foreach ($all_cart_items as $item) {
            // So sánh với cart_item_id hoặc product_id tùy theo dữ liệu có sẵn
            if (in_array($item['cart_item_id'], $selected_ids) || in_array($item['product_id'], $selected_ids)) {
                $product = [
                    'product_id'  => $item['product_id'],
                    'name'        => $item['product_name'],
                    'image'       => $item['product_image'],
                    'price'       => $item['unit_price'],
                    'quantity'    => $item['quantity'],
                    'total_price' => $item['unit_price'] * $item['quantity'],
                    'unit'        => 'Bó/Túi'
                ];
                $subtotal += $product['total_price'];
                $checkout_products[] = $product;
            }
        }
    }

    // Debug: nếu không có sản phẩm, ghi log để kiểm tra
    if (empty($checkout_products)) {
        error_log("Checkout warning: no products found. Selected IDs: " . print_r($selected_ids, true));
        error_log("Cart items: " . print_r($all_cart_items, true));
    }
    
    $_SESSION['checkout_products_ready'] = $checkout_products;
    $_SESSION['checkout_default_address'] = $default_address;
    $_SESSION['checkout_customer'] = $customer_info;
    
    require_once __DIR__ . "/../../views/customer/Checkout.php";
}

    public function process() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $cart = $_SESSION['checkout_products_ready'] ?? [];
            if (empty($cart)) {
                throw new Exception("Không có sản phẩm để thanh toán");
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Dữ liệu không hợp lệ");
            }

            $customer_id = $_SESSION['customer_id'] ?? null;
            if (!$customer_id) {
                throw new Exception("Vui lòng đăng nhập");
            }

            $default_address = $_SESSION['checkout_default_address'] ?? null;
            if (!$default_address || empty($default_address['address_id'])) {
                throw new Exception("Không có địa chỉ giao hàng");
            }
            $address_id = $default_address['address_id'];

            $shipping_fee = (int)$data['shipping_fee'];
            $total_amount = (int)$data['total_amount'];
            $payment_method = $data['payment_method'];
            $total_quantity = array_sum(array_column($cart, 'quantity'));

            $shipment_method = ($shipping_fee == 25000) ? 'Giao hàng tiêu chuẩn' : 'Hỏa tốc 2h';
            
            $orderModel = new OrderModel();
            $order_id = $orderModel->createOrder(
                $customer_id, 
                $address_id, 
                $total_quantity, 
                $total_amount, 
                $payment_method, 
                $shipping_fee, 
                $shipment_method
            );
            
            if (!$order_id) {
                throw new Exception("Lỗi tạo đơn hàng");
            }

            $orderDetailModel = new OrderDetailModel();
            foreach ($cart as $item) {
                $product_id = $item['product_id'];
                $price = $item['price'];
                $quantity = $item['quantity'];
                if (!$orderDetailModel->addDetail($order_id, $product_id, $price, $quantity)) {
                    throw new Exception("Lỗi thêm chi tiết đơn");
                }
            }

            unset($_SESSION['checkout_items']);
            unset($_SESSION['checkout_products_ready']);

            if ($payment_method === 'momo') {
                $momoUrl = "?action=momo&order_id=" . $order_id;
                echo json_encode(["status" => "success", "payment" => "momo", "redirect_url" => $momoUrl]);
            } else {
                echo json_encode(["status" => "success", "payment" => "cod", "message" => "Đặt hàng thành công!"]);
            }
        } catch (Exception $e) {
            error_log("[Checkout Error] " . $e->getMessage());
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    public function momoPayment() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $order_id = $_GET['order_id'] ?? null;
        if (!$order_id) die("Lỗi: Không tìm thấy mã đơn hàng");

        $orderModel = new OrderModel();
        $orderDetailModel = new OrderDetailModel();

        $orderInfo = $orderModel->getOrderById($order_id);
        if (!$orderInfo) die("Lỗi: Đơn hàng không tồn tại");

        $orderItems = $orderDetailModel->getItemsByOrderId($order_id);
        $order_products = [];
        $subtotal = 0;
        foreach ($orderItems as $item) {
            $order_products[] = [
                'product_name'  => $item['name'],
                'product_image' => $item['img'],
                'quantity'      => $item['quantity'],
                'price'         => $item['price'],
                'unit'          => 'Bó/Túi'
            ];
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping_fee = $orderInfo['shipping_fee'] ?? 0;
        $total_amount = $orderInfo['total_amount'] ?? 0;

        require_once __DIR__ . "/../../views/customer/Momo.php";
    }
}

$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$controller = new CheckoutController();
if ($requestMethod === 'POST') {
    $controller->process();
} else {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    if ($action === 'momo') $controller->momoPayment();
    else $controller->index();
}
?>