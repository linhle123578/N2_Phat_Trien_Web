<?php
// Sửa lại dấu gạch chéo đường dẫn nạp Model cho đúng
require_once __DIR__ . "../models/ProductModel.php";

class ProductController {

    // Hiển thị danh sách sản phẩm
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $model = new ProductModel();

        // Thu thập các bộ lọc từ URL (Query String)
        $category_filter = $_GET['category'] ?? '';
        $search_filter   = $_GET['search'] ?? '';
        $sort_filter     = $_GET['sort'] ?? '';

        // Đọc dữ liệu từ Model
        $categories = $model->getCategories();
        $products   = $model->getProducts($category_filter, $search_filter, $sort_filter);

        // --- BỔ SUNG: Tính toán các biến số lượng phục vụ hiển thị trên View ---
        $total_products = count($products);
        $start_product = ($total_products > 0) ? 1 : 0;
        $end_product = $total_products;
        $total_pages = 1; // Tạm thời để 1 trang nếu bạn chưa làm phân trang nâng cao
        $page = 1;

        // Đếm số lượng hiển thị trên Badge giỏ hàng (nếu đã đăng nhập)
        $cart_count = 0;
        if (isset($_SESSION['customer_id'])) {
            $cart_count = $model->getCartBadgeCount($_SESSION['customer_id']);
        }

        // Gọi đúng file View hiển thị giao diện ra
        require_once "../views/customer/Products.php";
    }

    // Xử lý hành động Thêm vào giỏ qua AJAX POST
    public function addToCartAjax() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 1. Kiểm tra trạng thái đăng nhập
        if (!isset($_SESSION['customer_id'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(["status" => "unauthenticated", "message" => "Vui lòng đăng nhập để thực hiện tính năng này."]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
            $customer_id = $_SESSION['customer_id'];
            $product_id = $_POST['product_id'];

            $model = new ProductModel();
            $success = $model->addToCart($customer_id, $product_id, 1);

            if ($success) {
                $new_count = $model->getCartBadgeCount($customer_id);
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Thêm sản phẩm thành công!",
                    "cart_count" => $new_count
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Lỗi hệ thống không thể lưu sản phẩm."]);
            }
            exit();
        }
    }
}

$controller = new ProductController();

// Kiểm tra hành động gửi từ AJAX
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $controller->addToCartAjax();
} else {
    $controller->index();
}
