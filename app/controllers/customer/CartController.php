<?php
require_once __DIR__ . "/../../models/CartModel.php";

class CartController
{

    // Hàm hiển thị giao diện giỏ hàng
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['customer_id'])) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/LogIn.php");
=======
            header("Location: ../../../public/index.php?page=login");
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            exit();
        }

        $cartModel = new CartModel();
        $items = $cartModel->getCartItems($_SESSION['customer_id']);
        $total_items = count($items);

        // Đẩy dữ liệu ra view
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

    // Hàm xử lý thanh toán khi form submit
    // Hàm xử lý khi form giỏ hàng submit
    public function checkout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['customer_id'])) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/LogIn.php");
=======
            header("Location: ../../../public/index.php?page=login");
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            exit();
        }
        
        $customer_id = $_SESSION['customer_id'];

        // Lấy danh sách sản phẩm được tick chọn (ID sản phẩm) và số lượng
        $selected = $_POST['selected'] ?? [];
        $qty_map = $_POST['qty'] ?? [];

        // Nếu không chọn sản phẩm nào mà bấm thanh toán
        if (empty($selected)) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/msg=noselect");
=======
            header("Location: ../../../public/index.php?page=cart&msg=noselect");
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
        exit();
        }

        // MỚI: Thay vì tạo đơn hàng luôn, ta lưu danh sách mua vào Session
        // Để mang dữ liệu này sang Trang Thanh Toán (Checkout)
        $_SESSION['checkout_items'] = [];

        foreach ($selected as $product_id) {
            if (isset($qty_map[$product_id])) {
                $_SESSION['checkout_items'][] = [
                    'product_id' => $product_id,
                    'quantity' => $qty_map[$product_id]
                ];
            }
        }

        // Chuyển hướng sang trang Checkout để user nhập địa chỉ, chọn ship/thanh toán
        header("Location: CheckoutController.php");
        exit();
    }

    public function add()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['customer_id'])) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/LogIn.php");
=======
            header("Location: ../../../public/index.php?page=login");
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            exit();
        }

        require_once __DIR__ . "/../../models/ProductModel.php";
        $productModel = new ProductModel();

        $customer_id = $_SESSION['customer_id'];
        $product_id = $_POST['product_id'] ?? '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

        if (!empty($product_id)) {
            $productModel->addToCart($customer_id, $product_id, $quantity);
        }

        if (isset($_POST['buy_now'])) {
<<<<<<< HEAD
            header("Location: ../../../app/views/customer/cart.php");
        } else {
            $referer = $_SERVER['HTTP_REFERER'] ?? '../../../app/views/customer/cart.php';
=======
            header("Location: ../../../public/index.php?page=cart");
        } else {
            $referer = $_SERVER['HTTP_REFERER'] ?? '../../../public/index.php?page=cart';
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
            header("Location: " . $referer);
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartController = new CartController();
    
    // Nếu có truyền cart_item_id qua POST (AJAX xóa)
    if (isset($_POST['cart_item_id'])) {
        $cartController->delete();
    } 
    // Nếu có truyền product_id qua POST (Thêm vào giỏ hoặc Mua ngay)
    elseif (isset($_POST['product_id'])) {
        $cartController->add();
    } 
    // Nếu bấm nút Thanh Toán trong giỏ hàng
    else {
        $cartController->checkout();
    }
}
