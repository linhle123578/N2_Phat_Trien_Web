<?php

require_once __DIR__ . '/../../models/OrderHistoryModel.php';

class OrderHistoryController
{
    private $model;
    private string $customer_id;

    private const ALLOWED_TABS = ['all', 'pending', 'confirmed', 'shipping', 'delivered', 'completed', 'cancelled'];

    public function __construct($conn)
    {
        $this->model = new OrderHistoryModel($conn);
        // Ưu tiên GET (để test dễ dàng), sau đó session, fallback về CUS005
        $this->customer_id = trim($_GET['customer_id'] ?? $_SESSION['customer_id'] ?? 'CUS005');
    }


    public function index(): void
    {
        // 1. Validate tab
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, self::ALLOWED_TABS)) $tab = 'all';

        // 2. Lấy dữ liệu từ Model
        $customer    = $this->model->getCustomer($this->customer_id);
        $counts      = $this->model->getOrderCounts($this->customer_id);
        $orders      = $this->model->getOrders($this->customer_id, $tab);
        $order_ids   = array_column($orders, 'order_id');
        $order_items = $this->model->getOrderItems($order_ids);

        // 3. Tính trạng thái trả hàng cho từng đơn delivered
        $return_eligible = [];
        $has_returned = [];
        foreach ($orders as $order) {
            $st = $order['order_status'];
            if ($st === 'delivered' || $st === 'Hoàn thành' || $st === 'Đã giao') {
                $oid = $order['order_id'];
                $has_ret = $this->model->hasExistingReturn($oid);
                $has_returned[$oid] = $has_ret;
                $return_eligible[$oid] = $this->model->isReturnEligible($oid, 3) && !$has_ret;
            }
        }

        // 4. Cập nhật tổng đơn cho sidebar
        $customer['orders'] = $counts['all'];

        // 5. Render view, truyền data qua compact()
        $data = compact('tab', 'customer', 'counts', 'orders', 'order_items', 'return_eligible', 'has_returned');
        $this->render('OrderHistory', $data);
    }

    // load view và inject data
    private function render(string $view, array $data = []): void
    {
        extract($data);
        $view_path = __DIR__ . "/../../views/customer/{$view}.php";
        if (!file_exists($view_path)) {
            http_response_code(404);
            echo "View không tồn tại: {$view}";
            return;
        }
        require $view_path;
        exit;

    }

    // Action: Mua lại đơn hàng
    public function rebuy(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('OrderHistory', 'Yêu cầu không hợp lệ.');
            return;
        }

        $order_id = trim($_POST['order_id'] ?? '');
        if (empty($order_id)) {
            $this->redirectWithError('OrderHistory', 'Thiếu mã đơn hàng.');
            return;
        }

        // Lấy sản phẩm của đơn hàng này
        $items_by_order = $this->model->getOrderItems([$order_id]);
        $order_items = $items_by_order[$order_id] ?? [];

        if (empty($order_items)) {
            $this->redirectWithError('OrderHistory', 'Không tìm thấy sản phẩm trong đơn hàng.');
            return;
        }

        require_once __DIR__ . '/../../models/ProductModel.php';
        $productModel = new ProductModel();

        foreach ($order_items as $item) {
            $product_id = $item['product_id'];
            $quantity = (int)$item['quantity'];
            if ($quantity > 0) {
                $productModel->addToCart($this->customer_id, $product_id, $quantity);
            }
        }

        // Chuyển hướng sang giỏ hàng
        header("Location: ../../../app/views/customer/cart.php");
        exit;
    }

    // Helper tĩnh dùng trong View 
    public static function statusLabel(string $s): string
    {
        return match($s) {
            'pending'   => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipping'  => 'Đang giao',
            'delivered' => 'Đã giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã huỷ',
            default     => $s,
        };
    }

    public static function statusClass(string $s): string
    {
        return match($s) {
            'pending'   => 'status-pending',
            'confirmed' => 'status-confirmed',
            'shipping'  => 'status-shipping',
            'delivered' => 'status-delivered',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            default     => '',
        };
    }

    public static function statusIcon(string $s): string
    {
        return match($s) {
            'pending'   => 'bi-clock',
            'confirmed' => 'bi-check2-circle',
            'shipping'  => 'bi-truck',
            'delivered' => 'bi-box-seam',
            'completed' => 'bi-check-circle-fill',
            'cancelled' => 'bi-x-circle-fill',
            default     => 'bi-circle',
        };
    }

    public static function formatPrice(float $n): string
    {
        return number_format($n, 0, ',', '.') . ' ₫';
    }

    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
    private function redirectWithError(string $page, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['error'] = $msg;
    header("Location: " . BASE_URL . "index.php?page={$page}");
    exit;
}
}
