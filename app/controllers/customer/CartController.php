<?php
require_once __DIR__ . "/../../models/CartModel.php";

class CartController
{

    // Hiển thị giao diện giỏ hàng
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['customer_id'])) {
            header("Location: ../../../app/views/customer/LogIn.php");
            exit();
        }

        $cartModel = new CartModel();
        $items = $cartModel->getCartItems($_SESSION['customer_id']);
        $total_items = count($items);

        require_once "../app/views/customer/cart.php";
    }

    // Hàm gọi qua AJAX để xóa sản phẩm
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'])) {
            $cartModel = new CartModel();
            $result = $cartModel->deleteItem($_POST['cart_item_id']);

            if ($result) {
                http_response_code(200);
                echo "Xóa thành công";
            } else {
                http_response_code(500);
                echo "Lỗi hệ thống";
            }
        }
    }

    // Hàm xử lý thanh toán khi form giỏ hàng submit
    public function checkout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['customer_id'])) {
            header("Location: ../../../app/views/customer/LogIn.php");
            exit();
        }
        
        $customer_id = $_SESSION['customer_id'];

        // Lấy danh sách sản phẩm được tick chọn (ID sản phẩm) và số lượng
        $selected = $_POST['selected'] ?? [];
        $qty_map = $_POST['qty'] ?? [];

        // Nếu không chọn sản phẩm nào mà bấm thanh toán
        if (empty($selected)) {
            header("Location: ../../../app/views/customer/msg=noselect");
        exit();
        }

        // Lưu danh sách mua vào Session, mang dữ liệu này sang Trang Thanh Toán (Checkout)
        $_SESSION['checkout_items'] = [];

        foreach ($selected as $product_id) {
            if (isset($qty_map[$product_id])) {
                $_SESSION['checkout_items'][] = [
                    'product_id' => $product_id,
                    'quantity' => $qty_map[$product_id]
                ];
            }
        }

        // Chuyển hướng sang trang Checkout
        header("Location: CheckoutController.php");
        exit();
    }

    // Hàm cập nhật số lượng qua AJAX
    public function updateQty()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cart_item_id = $_POST['cart_item_id'] ?? '';
            $quantity     = (int)($_POST['quantity'] ?? 1);

            if (!$cart_item_id || $quantity < 1) {
                echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
                return;
            }

            $cartModel = new CartModel();
            $result    = $cartModel->updateQuantity($cart_item_id, $quantity);

            if ($result) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật']);
            }
        }
    }

    public function add()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['customer_id'])) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['status' => 'not_logged_in', 'message' => 'Vui lòng đăng nhập để sử dụng giỏ hàng.']);
                exit();
            }
            header("Location: ../../../app/views/customer/LogIn.php");
            exit();
        }

        require_once __DIR__ . "/../../models/ProductModel.php";
        $productModel = new ProductModel();

        $customer_id = $_SESSION['customer_id'];
        $product_id = $_POST['product_id'] ?? '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if (isset($_POST['buy_now'])) {
            $_SESSION['checkout_items'] = [
                [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'is_buy_now' => true
                ]
            ];
            header("Location: ../../../app/controllers/customer/CheckoutController.php");
            exit();
        }

        if (!empty($product_id)) {
            $productModel->addToCart($customer_id, $product_id, $quantity);
        }

        if (isset($_POST['ajax'])) {
            $cartModel = new CartModel();
            $items = $cartModel->getCartItems($customer_id);
            echo json_encode(['status' => 'success', 'new_cart_count' => count($items)]);
            exit();
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '../../../app/views/customer/cart.php';
        header("Location: " . $referer);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartController = new CartController();
    
    if (isset($_POST['cart_item_id']) && isset($_POST['quantity'])) {
        $cartController->updateQty();
    } elseif (isset($_POST['cart_item_id'])) {
        $cartController->delete();
    } elseif (isset($_POST['product_id'])) {
        $cartController->add();
    } else {
        $cartController->checkout();
    }
}
