<?php
require_once __DIR__ . "/../../models/OrderModel.php";
require_once __DIR__ . "/../../models/OrderDetailModel.php";

class CheckoutController {

    // 1. Hiển thị trang thanh toán với dữ liệu thực tế từ CartModel
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (empty($_SESSION['checkout_items'])) {
            header("Location: /app/views/customer/Cart.php");
            exit();
        }

        require_once __DIR__ . "/../../models/CartModel.php";
        require_once __DIR__ . "/../../models/UserModel.php";

        $cartModel = new CartModel();
        $userModel = new UserModel();

        // ĐÓN ID KHÁCH HÀNG TỪ SESSION SANG
        // Nếu Session có dữ liệu thì lấy, nếu không có thì mới dự phòng là 1
        $customer_id = $_SESSION['customer_id'] ?? 1; 

        // 1. Lấy thông tin khách hàng từ DB dựa vào ID trên
        $customer_info = $userModel->getUserById($customer_id); 

        // 2. Lấy dữ liệu sản phẩm giỏ hàng
        $all_cart_items = $cartModel->getCartItems($customer_id);
        $selected_ids = array_column($_SESSION['checkout_items'], 'product_id');
        
        $checkout_products = [];
        $subtotal = 0; 

        if ($all_cart_items) {
            foreach ($all_cart_items as $item) {
                if (in_array($item['cart_item_id'], $selected_ids) || in_array($item['product_id'], $selected_ids)) {
                    $product = [
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

        // Gọi hiển thị giao diện
        require_once __DIR__ . "/../../views/customer/Checkout.php";
    }

    // 2. Xử lý logic khi bấm nút "ĐẶT HÀNG" từ JS gọi lên
    public function process() {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Kiểm tra session đồ ăn đã chọn
        $cart = $_SESSION['checkout_items'] ?? [];
        if (empty($cart)) {
            echo json_encode(["status" => "error", "message" => "Không có sản phẩm để thanh toán"]);
            return;
        }

        // Lấy dữ liệu user nhập (từ file JS gửi lên bằng AJAX)
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            echo json_encode(["status" => "error", "message" => "Dữ liệu không hợp lệ"]);
            return;
        }

        $customer_id = $_SESSION['customer_id'];
        $name = $data['name'];
        $phone = $data['phone'];
        $address = $data['address'];
        $shipping_fee = $data['shipping_fee'];
        $total_amount = $data['total_amount'];
        $payment_method = $data['payment_method'];

        // Gọi Model lưu đơn hàng
        $orderModel = new OrderModel();
        // Sửa lại Model một chút nếu bạn muốn lưu cả customer_id
        $order_id = $orderModel->createOrder($customer_id, $name, $phone, $address, $shipping_fee, $total_amount, $payment_method);

        if ($order_id) {
            // Lưu chi tiết từng sản phẩm vào DB
            $orderDetailModel = new OrderDetailModel();
            foreach ($cart as $item) {
                // TODO: Để an toàn, bạn nên gọi hàm lấy giá gốc (price) của product_id từ DB ở đây
                $price = 0; // Thay bằng hàm lấy giá thực tế từ ProductModel
                $orderDetailModel->addDetail($order_id, $item['product_id'], $price, $item['quantity']);
            }

            // Xóa session chứa các mặt hàng đang checkout vì đã đặt thành công
            unset($_SESSION['checkout_items']);
            // TODO: Ở đây bạn cũng cần code xóa các mặt hàng này khỏi giỏ hàng gốc trong DB (CartModel)

            // Trả về JSON cho Frontend
            if ($payment_method === 'momo') {
                $momoUrl = "/momo-payment?order_id=" . $order_id;
                echo json_encode(["status" => "success", "payment" => "momo", "redirect_url" => $momoUrl]);
            } else {
                echo json_encode(["status" => "success", "payment" => "cod", "message" => "Đặt hàng thành công!"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống khi tạo đơn!"]);
        }
    }
}
// Kích hoạt controller tự động
$checkoutController = new CheckoutController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nếu là thao tác bấm nút ĐẶT HÀNG gửi API lên
    $checkoutController->process();
} else {
    // Nếu là load trang bình thường
    $checkoutController->index();
}
?>