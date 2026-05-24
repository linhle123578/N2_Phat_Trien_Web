<?php
require_once __DIR__ . '/../../models/ProductModel.php';

class ProductController {
    private $model;

    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ProductModel();
    }

    public function index() {
        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
        $search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

        $limit = 9;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $total_products = $this->model->getTotalProductsCount($category_filter, $search_filter);
        $total_pages = ceil($total_products / $limit);

        if ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
            $offset = ($page - 1) * $limit;
        }

        $cat_result = $this->model->getCategories();
        $prod_result = $this->model->getProducts($category_filter, $search_filter, $sort_filter, $limit, $offset);

        // Lấy session kiểm tra login thật của dự án khách hàng
        $customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : '';
        $total_cart_items = !empty($customer_id) ? $this->model->getCartCount($customer_id) : 0;

        $start_product = ($total_products == 0) ? 0 : $offset + 1;
        $end_product = min($offset + $limit, $total_products);

        require_once __DIR__ . '/../../views/customer/Products.php';
    }

    public function addToCartAjax() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
            echo json_encode([
                'status' => 'not_logged_in',
                'message' => 'Bạn cần phải đăng nhập hệ thống để thực hiện chức năng mua sắm này!'
            ]);
            exit();
        }

        $customer_id = $_SESSION['customer_id'];
        $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';

        if (empty($product_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Mã ID sản phẩm trống.']);
            exit();
        }

        $result = $this->model->addToCart($customer_id, $product_id, 1);

        if ($result) {
            $new_total = $this->model->getCartCount($customer_id);
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã thêm sản phẩm thành công!',
                'new_cart_count' => $new_total
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối lưu cơ sở dữ liệu.']);
        }
        exit();
    }
}

// KHỞI ĐỘNG ĐIỀU PHỐI ROUTER
$controller = new ProductController();
if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart') {
    $controller->addToCartAjax();
} else {
    $controller->index();
}
?>