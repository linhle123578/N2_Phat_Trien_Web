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
            header("Location: ../../../app/views/customer/cart.php");
            exit();
        }

        $cartModel = new CartModel();
        $userModel = new UserModel();

        if (!isset($_SESSION['customer_id'])) {
            header("Location: ../../../app/views/customer/LogIn.php");
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
        if (!empty($_SESSION['checkout_info'])) {
            $customer_info['fullname'] = $_SESSION['checkout_info']['name']    ?: $customer_info['fullname'];
            $customer_info['phone']    = $_SESSION['checkout_info']['phone']   ?: $customer_info['phone'];
            $customer_info['address']  = $_SESSION['checkout_info']['address'] ?: $customer_info['address'];
        }

        $checkout_products = [];
        $subtotal = 0;

        $is_buy_now = false;
        if (!empty($_SESSION['checkout_items'])) {
            foreach ($_SESSION['checkout_items'] as $item) {
                if (isset($item['is_buy_now']) && $item['is_buy_now'] === true) {
                    $is_buy_now = true;
                    break;
                }
            }
        }

        if ($is_buy_now) {
            require_once __DIR__ . "/../../models/ProductModel.php";
            $productModel = new ProductModel();
            foreach ($_SESSION['checkout_items'] as $item) {
                $product = $productModel->getProductById($item['product_id']);
                if ($product) {
                    $prod_info = [
                        'name'         => $product['product_name'],
                        'image'        => $product['product_image'],
                        'price'        => $product['price'],
                        'quantity'     => $item['quantity'],
                        'total_price'  => $product['price'] * $item['quantity'],
                        'unit'         => $product['unit'] ?? 'Bó/Túi',
                        'product_id'   => $product['product_id'],
                        'cart_item_id' => null,
                    ];
                    $subtotal += $prod_info['total_price'];
                    $checkout_products[] = $prod_info;
                }
            }
        } else {
            $all_cart_items = $cartModel->getCartItems($customer_id);
            $selected_ids   = array_column($_SESSION['checkout_items'], 'product_id');

            // Lấy map quantity từ session để đảm bảo đúng số lượng lúc submit checkout
            $selected_qty_map = [];
            foreach ($_SESSION['checkout_items'] as $it) {
                $selected_qty_map[$it['product_id']] = $it['quantity'] ?? 1;
            }

            if ($all_cart_items) {
                foreach ($all_cart_items as $item) {
                    if (in_array($item['product_id'], $selected_ids)) {
                        $qty = isset($selected_qty_map[$item['product_id']]) ? $selected_qty_map[$item['product_id']] : $item['quantity'];
                        $product = [
                            'name'         => $item['product_name'],
                            'image'        => $item['product_image'],
                            'price'        => $item['unit_price'],
                            'quantity'     => $qty,
                            'total_price'  => $item['unit_price'] * $qty,
                            'unit'         => 'Bó/Túi',
                            'product_id'   => $item['product_id'],
                            'cart_item_id' => $item['cart_item_id'],
                        ];
                        $subtotal += $product['total_price'];
                        $checkout_products[] = $product;
                    }
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

        if (!isset($_SESSION['customer_id'])) {
            echo json_encode(["status" => "error", "message" => "Vui lòng đăng nhập"]);
            return;
        }

        $customer_id    = $_SESSION['customer_id'];
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

        // Luôn lưu checkout_info để back về checkout vẫn có dữ liệu
        $_SESSION['checkout_info'] = [
            'name'    => $name,
            'phone'   => $phone,
            'address' => $address,
        ];
        $_SESSION['last_order_shipping'] = $shipping_fee;

        // ── MoMo: chỉ lưu pending_order, KHÔNG tạo đơn ──
        if ($payment_method === 'momo') {
            $_SESSION['pending_order'] = [
                'name'         => $name,
                'phone'        => $phone,
                'address'      => $address,
                'shipping_fee' => $shipping_fee,
                'total_amount' => $total_amount,
                'items'        => $cart,
            ];
            echo json_encode([
                "status"       => "success",
                "payment"      => "momo",
                "redirect_url" => "/app/controllers/customer/MomoPaymentController.php"
            ]);
            return;
        }

        // ── COD: tạo đơn ngay ──
        $orderModel       = new OrderModel();
        $cartModel        = new CartModel();
        $orderDetailModel = new OrderDetailModel();

        $order_id = $orderModel->createOrder(
            $customer_id, $name, $phone, $address,
            $shipping_fee, $total_amount, $payment_method
        );

        if (!$order_id) {
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống khi tạo đơn hàng!"]);
            return;
        }

        // Trạng thái COD → Chờ xác nhận
        $orderModel->updateStatus($order_id, 'Chờ xác nhận');

        // Lưu order details + trừ tồn kho + xóa cart
        $all_cart_items   = $cartModel->getCartItems($customer_id);
        $price_map        = [];
        $cart_item_id_map = [];
        foreach ($all_cart_items as $ci) {
            $price_map[$ci['product_id']]        = $ci['unit_price'];
            $cart_item_id_map[$ci['product_id']] = $ci['cart_item_id'];
        }

        require_once __DIR__ . "/../../models/ProductModel.php";
        $productModel = new ProductModel();

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