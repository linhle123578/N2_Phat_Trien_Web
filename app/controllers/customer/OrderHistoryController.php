<?php
/**
 * Controller: OrderHistoryController
 * Nhận request → gọi Model → truyền data xuống View
 */
require_once __DIR__ . '/../../models/OrderHistoryModel.php';

class OrderHistoryController
{
    private $model;
    private string $customer_id;

    // Các tab hợp lệ
    private const ALLOWED_TABS = ['all', 'pending', 'confirmed', 'shipping', 'delivered', 'completed', 'cancelled'];

    public function __construct($conn)
    {
        $this->model = new OrderHistoryModel($conn);
        // Ưu tiên GET (để test dễ dàng), sau đó session, fallback về CUS001
        $this->customer_id = trim($_GET['customer_id'] ?? $_SESSION['customer_id'] ?? 'CUS005');
    }

    /**
     * Action chính: hiển thị trang lịch sử đơn hàng
     */
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

        // 3. Tính trạng thái trả hàng cho từng đơn "delivered"
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

    /**
     * Helper: load view và inject data
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);
        // __DIR__ = app/controllers/customer → ../../views/customer
        $view_path = __DIR__ . "/../../views/customer/{$view}.php";
        if (!file_exists($view_path)) {
            http_response_code(404);
            echo "View không tồn tại: {$view}";
            return;
        }
        require $view_path;
        exit;

    }

    // ── Helper tĩnh dùng trong View ──────────────────────

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
}