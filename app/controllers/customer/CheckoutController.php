<?php
require_once __DIR__ . "/../../models/OrderModel.php";
require_once __DIR__ . "/../../models/OrderDetailModel.php";
require_once __DIR__ . "/../../models/CartModel.php";
require_once __DIR__ . "/../../models/UserModel.php";

class CheckoutController
{

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['checkout_items'])) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/cart.php");
=======
            header("Location: ../../../public/index.php?page=cart");
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            exit();
        }

        $cartModel = new CartModel();
        $userModel = new UserModel();

        if (!isset($_SESSION['customer_id'])) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/LogIn.php");
=======
            header("Location: ../../../public/index.php?page=login");
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            exit();
        }
        $customer_id = $_SESSION['customer_id'];

        // [FIX 1] Lấy cả thông tin customer lẫn địa chỉ mặc định
        $customer_raw     = $userModel->getCustomerById($customer_id);
        $default_address  = $userModel->getDefaultAddress($customer_id);

        // Gộp thành 1 array dùng trong view, chuẩn hoá key
        // [FIX] fullname luon lay tu customer.full_name, KHONG dung address.receiver_name
        $customer_info = [
            'fullname'     => $customer_raw['full_name'] ?? '',
            'phone'        => $customer_raw['phone']     ?? '',
            'address'      => $default_address
                ? implode(', ', array_filter([
                    $default_address['street_address'] ?? '',
                    $default_address['ward']           ?? '',
                    $default_address['district']       ?? '',
                    $default_address['province']       ?? '',
                ]))
                : '',
            'address_type' => $default_address['address_type'] ?? 'Nha rieng',
        ];

        $all_cart_items = $cartModel->getCartItems($customer_id);
        $selected_ids   = array_column($_SESSION['checkout_items'], 'product_id');

        $checkout_products = [];
        $subtotal = 0;

        if ($all_cart_items) {
            foreach ($all_cart_items as $item) {
                if (
                    in_array($item['product_id'],   $selected_ids)
                ) {
                    $product = [
                        'name'         => $item['product_name'],
                        'image'        => $item['product_image'],
                        'price'        => $item['unit_price'],
                        'quantity'     => $item['quantity'],
                        'total_price'  => $item['unit_price'] * $item['quantity'],
                        'unit'         => 'Bó/Túi',
                        'product_id'   => $item['product_id'],
                        'cart_item_id' => $item['cart_item_id'],
                    ];
                    $subtotal += $product['total_price'];
                    $checkout_products[] = $product;
                }
            }
        }

        require_once __DIR__ . "/../../views/customer/Checkout.php";
    }

    // ----------------------------------------------------------------
    // 2. Xử lý đặt hàng (AJAX POST từ Checkout.js)
    // ----------------------------------------------------------------
    public function process()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $cart = $_SESSION['checkout_items'] ?? [];
        if (empty($cart)) {
            echo json_encode(["status" => "error", "message" => "Không có sản phẩm để thanh toán"]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            echo json_encode(["status" => "error", "message" => "Dữ liệu không hợp lệ"]);
            return;
        }

        $customer_id = $_SESSION['customer_id'];
        $name           = trim($data['name']           ?? '');
        $phone          = trim($data['phone']          ?? '');
        $address        = trim($data['address']        ?? '');
        $shipping_fee   = (int)($data['shipping_fee']  ?? 0);
        $total_amount   = (int)($data['total_amount']  ?? 0);
        $payment_method = trim($data['payment_method'] ?? 'cod');

        if (!$name || !$phone || !$address) {
            echo json_encode(["status" => "error", "message" => "Vui lòng điền đầy đủ thông tin giao hàng"]);
            return;
        }

        $orderModel = new OrderModel();
        $order_id   = $orderModel->createOrder(
            $customer_id,
            $name,
            $phone,
            $address,
            $shipping_fee,
            $total_amount,
            $payment_method
        );

        if (!$order_id) {
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống khi tạo đơn hàng!"]);
            return;
        }
        if ($payment_method === 'cod') {
            // COD thì cho thẳng vào trạng thái Đang giao
            $orderModel->updateStatus($order_id, 'Đang giao');
        } else {
            // MoMo thì để Chờ xác nhận (đợi quét QR)
            $orderModel->updateStatus($order_id, 'Chờ xác nhận');
        }

        $cartModel        = new CartModel();
        $orderDetailModel = new OrderDetailModel();
        $all_cart_items   = $cartModel->getCartItems($customer_id);

        // Build price_map và cart_item_id_map theo product_id
        $price_map        = [];  // product_id  => unit_price
        $cart_item_id_map = [];  // product_id  => cart_item_id
        if ($all_cart_items) {
            foreach ($all_cart_items as $ci) {
                $price_map[$ci['product_id']]        = $ci['unit_price'];
                $cart_item_id_map[$ci['product_id']] = $ci['cart_item_id'];
                // fallback nếu session có cart_item_id
                if (!empty($ci['cart_item_id'])) {
                    $price_map[$ci['cart_item_id']] = $ci['unit_price'];
                }
            }
        }

        $cart_item_ids_to_delete = [];
        foreach ($cart as $item) {
            $pid = $item['product_id'];
            $qty = (int)($item['quantity'] ?? 1);

            // [FIX] Ưu tiên map theo product_id vì session thường không có cart_item_id
            $price = $price_map[$item['cart_item_id'] ?? '']
                ?? $price_map[$pid]
                ?? 0;

            $orderDetailModel->addDetail($order_id, $pid, $price, $qty);

            // Lấy cart_item_id để xoá: từ session hoặc từ map
            $cid = $item['cart_item_id'] ?? $cart_item_id_map[$pid] ?? null;
            if ($cid) {
                $cart_item_ids_to_delete[] = $cid;
            }
        }

        if ($payment_method !== 'momo') {
            if (!empty($cart_item_ids_to_delete)) {
                foreach ($cart_item_ids_to_delete as $cid) {
                    $cartModel->deleteItem($cid);
                }
            }
            unset($_SESSION['checkout_items']);
        }
        $_SESSION['last_order_id']       = $order_id;
        $_SESSION['last_order_shipping'] = $shipping_fee; // dùng bởi MomoPaymentController

        if ($payment_method === 'momo') {
            echo json_encode([
                "status"       => "success",
                "payment"      => "momo",
                "redirect_url" => "MomoPaymentController.php?order_id=" . urlencode($order_id)
            ]);
        } else {
            echo json_encode([
                "status"   => "success",
                "payment"  => "cod",
                "order_id" => $order_id,
                "message"  => "Đặt hàng thành công! Mã đơn: " . $order_id
            ]);
        }
    }
}

// ---- Kích hoạt controller ----
$checkoutController = new CheckoutController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkoutController->process();
} else {
    $checkoutController->index();
}
