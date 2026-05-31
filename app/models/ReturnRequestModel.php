<?php
class ReturnRequestModel
{
    private $conn;

    // Các trạng thái hợp lệ
    public const STATUSES = ['Đang xử lý', 'Đã hoàn tiền', 'Đã đổi hàng', 'Đã hủy đơn', 'Từ chối'];

    // Các lý do gợi ý
    public const SUGGEST_REASONS = [
        'Sản phẩm bị hư hỏng khi nhận',
        'Giao sai sản phẩm / sai loại',
        'Sản phẩm không tươi / quá date',
        'Thiếu hàng so với đơn',
        'Chất lượng không như mô tả',
        'Không còn nhu cầu sử dụng',
        'Khác',
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Lấy thông tin đơn hàng + payment theo order_id & customer_id
     */
    public function getOrderInfo(string $order_id, string $customer_id): ?array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT o.order_id, o.order_status, o.created_at,
                    o.total_quantity_order,
                    p.total_amount, p.payment_method,
                    c.full_name, c.phone
             FROM `order` o
             LEFT JOIN payment p ON o.order_id = p.order_id
             LEFT JOIN customer c ON o.customer_id = c.customer_id
             WHERE o.order_id = ? AND o.customer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $order_id, $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    public function getOrderItems(string $order_id): array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price,
                    pr.product_name, pr.product_image
             FROM orderitem oi
             LEFT JOIN product pr ON oi.product_id = pr.product_id
             WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) $items[] = $row;
        mysqli_stmt_close($stmt);
        return $items;
    }

    /**
     * Tạo yêu cầu trả hàng mới
     */
    public function createReturnRequest(array $data): ?string
    {
        // Sinh return_id tự động: RET + uniqid
        $return_id    = 'RET' . strtoupper(substr(uniqid(), -6));
        $order_id     = $data['order_id'];
        $reason       = $data['reason'];
        $description  = $data['description'] ?? '';
        $return_type  = $data['return_type'];  // 'Đổi hàng' | 'Hoàn tiền'
        $bank_name    = $data['bank_name'] ?? null;
        $bank_account = $data['bank_account'] ?? null;
        $bank_holder  = $data['bank_holder'] ?? null;
        $status       = 'Đang xử lý';
        $request_date = date('Y-m-d H:i:s');

        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO returnrequest
             (return_id, order_id, reason, description, return_type,
              bank_name, bank_account, bank_holder,
              return_status, request_date)
             VALUES (?,?,?,?,?,?,?,?,?,?)");

        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, 'ssssssssss',
            $return_id, $order_id, $reason, $description, $return_type,
            $bank_name, $bank_account, $bank_holder,
            $status, $request_date
        );
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok ? $return_id : null;
    }

    /**
     * Lấy chi tiết một yêu cầu trả hàng
     */
    public function getReturnById(string $return_id): ?array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT * FROM returnrequest WHERE return_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $return_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    /**
     * Lấy chi tiết một yêu cầu trả hàng theo order_id
     */
    public function getReturnByOrderId(string $order_id): ?array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT * FROM returnrequest WHERE order_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    /**
     * Kiểm tra điều kiện đơn hàng
     */
    public function checkEligibility(string $order_id, string $customer_id): array
    {
        // Lấy thông tin đơn
        $info = $this->getOrderInfo($order_id, $customer_id);
        if (!$info) {
            return ['eligible' => false, 'reason' => 'Không tìm thấy đơn hàng.'];
        }
        // Kiểm tra đã có return chưa
        $stmt = mysqli_prepare($this->conn,
            "SELECT return_id FROM returnrequest
             WHERE order_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result) !== null;
        mysqli_stmt_close($stmt);

        if ($exists) {
            return ['eligible' => false, 'reason' => 'Đơn hàng đã có yêu cầu trả hàng đang xử lý.'];
        }

        if (!in_array($info['order_status'], ['delivered', 'Đã giao', 'Hoàn thành'])) {
            return ['eligible' => false, 'reason' => 'Đơn hàng chưa được giao.'];
        }
        $diff_days = (time() - strtotime($info['created_at'])) / 86400;
        if ($diff_days > 3) {
            return ['eligible' => false, 'reason' => 'Đã quá 3 ngày kể từ khi nhận hàng.'];
        }
        return ['eligible' => true, 'reason' => ''];
    }
}
