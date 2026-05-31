<?php
class OrderHistoryModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Lấy thông tin khách hàng theo customer_id
     */
    public function getCustomer(string $customer_id): array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT full_name FROM customer WHERE customer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result) ?? ['full_name' => ''];
        mysqli_stmt_close($stmt);
        return $row;
    }

    /**
     * Đếm số đơn hàng theo từng trạng thái
     */
    public function getOrderCounts(string $customer_id): array
    {
        $counts = ['all' => 0, 'pending' => 0, 'confirmed' => 0,
                   'shipping' => 0, 'delivered' => 0, 'completed' => 0, 'cancelled' => 0];

        $stmt = mysqli_prepare($this->conn,
            "SELECT order_status, COUNT(*) AS cnt
             FROM `order`
             WHERE customer_id = ?
             GROUP BY order_status");
        mysqli_stmt_bind_param($stmt, 's', $customer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $map = [
            'chờ xác nhận' => 'pending',
            'pending'      => 'pending',
            'đã xác nhận'  => 'confirmed',
            'confirmed'    => 'confirmed',
            'đang giao'    => 'shipping',
            'shipping'     => 'shipping',
            'đã giao'      => 'delivered',
            'delivered'    => 'delivered',
            'hoàn thành'   => 'completed',
            'completed'    => 'completed',
            'đã hủy'       => 'cancelled',
            'đã huỷ'       => 'cancelled',
            'cancelled'    => 'cancelled'
        ];

        while ($row = mysqli_fetch_assoc($result)) {
            $db_st = mb_strtolower($row['order_status'], 'UTF-8');
            $en_st = $map[$db_st] ?? null;
            if ($en_st && isset($counts[$en_st])) {
                $counts[$en_st] += (int)$row['cnt'];
            }
            $counts['all'] += (int)$row['cnt'];
        }
        mysqli_stmt_close($stmt);
        return $counts;
    }

    /**
     * Lấy danh sách đơn hàng
     */
    public function getOrders(string $customer_id, string $tab): array
    {
        if ($tab === 'all') {
            $sql = "SELECT o.order_id, o.order_status, o.total_quantity_order,
                           o.created_at, o.shipment_id, o.address_id,
                           p.total_amount, p.payment_method
                    FROM `order` o
                    LEFT JOIN payment p ON o.order_id = p.order_id
                    WHERE o.customer_id = ?
                    ORDER BY o.created_at DESC";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, 's', $customer_id);
        } else {
            $map_reverse = [
                'pending'   => ['Chờ xác nhận', 'pending'],
                'confirmed' => ['Đã xác nhận', 'confirmed'],
                'shipping'  => ['Đang giao', 'shipping'],
                'delivered' => ['Đã giao', 'delivered'],
                'completed' => ['Hoàn thành', 'completed'],
                'cancelled' => ['Đã hủy', 'Đã huỷ', 'cancelled']
            ];
            $allowed_statuses = $map_reverse[$tab] ?? [$tab];
            
            $placeholders = implode(',', array_fill(0, count($allowed_statuses), '?'));
            $sql = "SELECT o.order_id, o.order_status, o.total_quantity_order,
                           o.created_at, o.shipment_id, o.address_id,
                           p.total_amount, p.payment_method
                    FROM `order` o
                    LEFT JOIN payment p ON o.order_id = p.order_id
                    WHERE o.customer_id = ? AND o.order_status IN ($placeholders)
                    ORDER BY o.created_at DESC";
            
            $stmt = mysqli_prepare($this->conn, $sql);
            $types = 's' . str_repeat('s', count($allowed_statuses));
            mysqli_stmt_bind_param($stmt, $types, $customer_id, ...$allowed_statuses);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) $orders[] = $row;
        mysqli_stmt_close($stmt);
        return $orders;
    }

    /**
     * Lấy các sản phẩm trong danh sách đơn hàng
     */
    public function getOrderItems(array $order_ids): array
    {
        if (empty($order_ids)) return [];

        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $sql = "SELECT oi.order_id, oi.product_id, oi.quantity, oi.price,
                       pr.product_name, pr.product_image
                FROM orderitem oi
                LEFT JOIN product pr ON oi.product_id = pr.product_id
                WHERE oi.order_id IN ($placeholders)";
        $stmt = mysqli_prepare($this->conn, $sql);
        $types = str_repeat('s', count($order_ids));
        mysqli_stmt_bind_param($stmt, $types, ...$order_ids);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[$row['order_id']][] = $row;
        }
        mysqli_stmt_close($stmt);
        return $items;
    }

    /**
     * Kiểm tra đơn hàng đã được giao trong vòng X ngày
     */
    public function isReturnEligible(string $order_id, int $days = 3): bool
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT created_at FROM `order`
             WHERE order_id = ? AND (order_status = 'delivered' OR order_status = 'Hoàn thành' OR order_status = 'completed' OR order_status = 'Đã giao') LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row) return false;
        $delivered_at = strtotime($row['created_at']);
        $diff_days = (time() - $delivered_at) / 86400;
        return $diff_days <= $days;
    }

    /**
     * Kiểm tra đơn hàng đã có yêu cầu trả hàng chưa
     */
    public function hasExistingReturn(string $order_id): bool
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT return_id FROM returnrequest
             WHERE order_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result) !== null;
        mysqli_stmt_close($stmt);
        return $exists;
    }
}
