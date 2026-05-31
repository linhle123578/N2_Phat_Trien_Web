<?php
/**
 * Controller: OrderDetailController
 * Nhận request → gọi Model → truyền data xuống View (OrderDetail.php)
 * Pattern tương tự OrderHistoryController
 */
require_once __DIR__ . '/../../models/OrderHistoryModel.php';

class OrderDetailController
{
    private $conn;
    private $model;
    private string $customer_id;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Kết nối DB
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        $success = mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3YHrkxqAKWynehu.root",
            "BzDRrZAdAT2jLuyd",
            "db_web_farm2home",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );
        if (!$success) {
            die("Kết nối database thất bại: " . mysqli_connect_error());
        }
        mysqli_set_charset($this->conn, "utf8mb4");

        $this->model       = new OrderHistoryModel($this->conn);
        $this->customer_id = $_SESSION['customer_id'] ?? 'KH001';
    }

    // ----------------------------------------------------------------
    // Action chính: hiển thị chi tiết đơn hàng
    // ----------------------------------------------------------------
    public function index(): void
    {
        $order_id = trim($_GET['id'] ?? '');
        if (!$order_id) {
            die("Mã đơn hàng không hợp lệ.");
        }

        // Bảo vệ: chỉ lấy đơn của đúng customer đang đăng nhập
        $orders = $this->model->getOrders($this->customer_id, 'all');
        $order  = null;
        foreach ($orders as $o) {
            if ($o['order_id'] === $order_id) {
                $order = $o;
                break;
            }
        }
        if (!$order) {
            die("Đơn hàng không tồn tại hoặc không thuộc về bạn.");
        }

        // Lấy sản phẩm trong đơn
        $all_items = $this->model->getOrderItems([$order_id]);
        $items     = $all_items[$order_id] ?? [];

        // Lấy thông tin thanh toán
        $payment = $this->fetchPayment($order_id);

        // Lấy thông tin vận chuyển
        $shipment = $this->fetchShipment($order['shipment_id'] ?? '');

        // Lấy địa chỉ giao hàng
        $address = $this->fetchAddress($order['address_id'] ?? '');

        // Lấy thông tin khách hàng (SĐT + tên fallback)
        $c_info         = $this->fetchCustomerInfo($this->customer_id);
        $customer_phone = $c_info['phone']     ?? '';
        $customer_name  = $c_info['full_name'] ?? 'Khách hàng';

        // Số lượng đơn hàng cho badge sidebar
        $counts      = $this->model->getOrderCounts($this->customer_id);
        $order_count = $counts['all'];

        // Kiểm tra trạng thái trả hàng
        $status      = $order['order_status'];
        $can_return  = false;
        $is_returned = false;
        $delivered_statuses = ['delivered', 'Hoàn thành', 'Đã giao', 'completed'];
        if (in_array($status, $delivered_statuses)) {
            $is_returned = $this->model->hasExistingReturn($order_id);
            $can_return  = !$is_returned && $this->model->isReturnEligible($order_id, 3);
        }

        // Tính tổng tiền sản phẩm
        $subtotal = 0;
        foreach ($items as $itm) {
            $subtotal += (float)$itm['price'] * (int)$itm['quantity'];
        }
        $total_amount = $payment ? (float)$payment['total_amount'] : $subtotal;
        $discount     = ($subtotal > $total_amount) ? ($subtotal - $total_amount) : 0;

        // Render view
        $data = compact(
            'order_id', 'order', 'items',
            'payment', 'shipment', 'address',
            'customer_phone', 'customer_name',
            'order_count',
            'can_return', 'is_returned',
            'subtotal', 'total_amount', 'discount'
        );
        $this->render('OrderDetail', $data);
    }

    // ----------------------------------------------------------------
    // Action: Huỷ đơn hàng (POST cancel_order)
    // ----------------------------------------------------------------
    public function cancelOrder(): void
    {
        $order_id = trim($_POST['order_id'] ?? $_GET['id'] ?? '');
        if (!$order_id) {
            header("Location: OrderDetailController.php");
            exit;
        }

        // Chỉ huỷ được khi đơn ở trạng thái pending
        $orders = $this->model->getOrders($this->customer_id, 'all');
        $order  = null;
        foreach ($orders as $o) {
            if ($o['order_id'] === $order_id) { $order = $o; break; }
        }

        if ($order && $order['order_status'] === 'pending') {
            $stmt = mysqli_prepare($this->conn,
                "UPDATE `order` SET order_status = 'cancelled' WHERE order_id = ?");
            mysqli_stmt_bind_param($stmt, 's', $order_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header("Location: OrderDetailController.php?id=" . urlencode($order_id));
        exit;
    }

    // ----------------------------------------------------------------
    // Action: Xác nhận đã nhận hàng (POST confirm_received)
    // ----------------------------------------------------------------
    public function confirmReceived(): void
    {
        $order_id = trim($_POST['order_id'] ?? $_GET['id'] ?? '');
        if (!$order_id) {
            header("Location: OrderDetailController.php");
            exit;
        }

        $stmt = mysqli_prepare($this->conn,
            "UPDATE `order` SET order_status = 'delivered' WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: OrderDetailController.php?id=" . urlencode($order_id));
        exit;
    }

    // ================================================================
    // PRIVATE helpers
    // ================================================================

    private function fetchPayment(string $order_id): ?array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT * FROM payment WHERE order_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    private function fetchShipment(string $shipment_id): ?array
    {
        if (!$shipment_id) return null;
        $stmt = mysqli_prepare($this->conn,
            "SELECT * FROM shipment WHERE shipment_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $shipment_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    private function fetchAddress(string $address_id): ?array
    {
        if (!$address_id) return null;
        $stmt = mysqli_prepare($this->conn,
            "SELECT * FROM address WHERE address_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $address_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    private function fetchCustomerInfo(string $customer_id): array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT phone, full_name FROM customer WHERE customer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $customer_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return $row ?: [];
    }

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

    // ── Static helpers dùng trong View ──────────────────────────────

    public static function statusLabel(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        return match($s) {
            'pending',   'chờ xác nhận' => 'Chờ xác nhận',
            'confirmed', 'đã xác nhận'  => 'Đã xác nhận',
            'shipping',  'đang giao'    => 'Đang giao',
            'delivered', 'đã giao',
            'hoàn thành','completed'    => 'Hoàn thành',
            'cancelled', 'đã hủy',
            'đã huỷ'                    => 'Đã huỷ',
            default                     => ucfirst($s),
        };
    }

    public static function statusClass(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        return match($s) {
            'pending',   'chờ xác nhận' => 'status-pending',
            'confirmed', 'đã xác nhận'  => 'status-confirmed',
            'shipping',  'đang giao'    => 'status-shipping',
            'delivered', 'đã giao',
            'hoàn thành','completed'    => 'status-delivered',
            'cancelled', 'đã hủy',
            'đã huỷ'                    => 'status-cancelled',
            default                     => 'status-pending',
        };
    }

    public static function buildAddress(array $addr): string
    {
        $parts = array_filter([
            $addr['street_address'] ?? '',
            $addr['ward']           ?? '',
            $addr['district']       ?? '',
            $addr['province']       ?? '',
        ]);
        return implode(', ', $parts);
    }

    public static function formatPrice(float $n): string
    {
        return number_format($n, 0, ',', '.') . ' đ';
    }

    public static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ── Router ──────────────────────────────────────────────────────────
$ctrl = new OrderDetailController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_order'])) {
        $ctrl->cancelOrder();
    } elseif (isset($_POST['confirm_received'])) {
        $ctrl->confirmReceived();
    } else {
        $ctrl->index();
    }
} else {
    $ctrl->index();
}
