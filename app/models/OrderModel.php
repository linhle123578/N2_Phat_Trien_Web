<?php

class OrderModel {
    private $conn;

    public function __construct() {
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
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
        mysqli_set_charset($this->conn, "utf8");
    }

    /**
     * Tạo đơn hàng mới
     * [FIX 2] Chỉ INSERT các cột thực sự có trong bảng `order`:
     *   order_id, customer_id, address_id, order_status,
     *   total_quantity_order, created_at
     * Trả về order_id hoặc false nếu lỗi
     */
    public function createOrder($customer_id, $name, $phone, $address, $shipping_fee, $total_amount, $payment_method) {
        $order_id = 'ORD-' . strtoupper(substr(uniqid(), -8));
        $cid      = $this->conn->real_escape_string($customer_id);
        $oid_esc  = $this->conn->real_escape_string($order_id);

        // Lấy address_id mặc định của customer (nếu có)
        $r_addr = $this->conn->query(
            "SELECT address_id FROM address WHERE customer_id = '$cid' AND is_default = 1 LIMIT 1"
        );
        $addr_row   = $r_addr ? $r_addr->fetch_assoc() : null;
        $address_id = $addr_row ? "'{$addr_row['address_id']}'" : "NULL";

        // Đếm tổng số lượng sản phẩm từ session (nếu có, không thì để 0)
        $checkout_items = $_SESSION['checkout_items'] ?? [];
        $total_qty = 0;
        foreach ($checkout_items as $item) {
            $total_qty += (int)($item['quantity'] ?? 1);
        }

        $ok = $this->conn->query(
            "INSERT INTO `order`
                (order_id, customer_id, address_id, order_status, total_quantity_order, created_at)
             VALUES
                ('$oid_esc', '$cid', $address_id, 'pending', $total_qty, NOW())"
        );

        if (!$ok) {
            error_log("OrderModel::createOrder failed: " . $this->conn->error);
            return false;
        }

        return $order_id;
    }

    /**
     * Lấy thông tin 1 đơn hàng theo order_id
     */
    public function getOrderById($order_id) {
        $oid = $this->conn->real_escape_string($order_id);
        $res = $this->conn->query(
            "SELECT * FROM `order` WHERE order_id = '$oid' LIMIT 1"
        );
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Lấy danh sách sản phẩm của 1 đơn hàng (JOIN orderitem + product)
     */
    public function getOrderItems($order_id) {
        $oid  = $this->conn->real_escape_string($order_id);
        $res  = $this->conn->query(
            "SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price,
                    p.product_name, p.product_image
             FROM orderitem oi
             JOIN product p ON oi.product_id = p.product_id
             WHERE oi.order_id = '$oid'"
        );
        $items = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateStatus($order_id, $status) {
        $oid = $this->conn->real_escape_string($order_id);
        $st  = $this->conn->real_escape_string($status);
        return $this->conn->query(
            "UPDATE `order` SET order_status = '$st' WHERE order_id = '$oid'"
        );
    }

    /**
     * Cập nhật trạng thái thanh toán MoMo
     */
    public function updateMomoPayment($order_id, $status, $transaction_id = null) {
        $oid = $this->conn->real_escape_string($order_id);
        $st  = $this->conn->real_escape_string($status);
        $tid = $transaction_id ? "'" . $this->conn->real_escape_string($transaction_id) . "'" : "NULL";

        $ok = $this->conn->query(
            "UPDATE `order`
             SET order_status = '$st', momo_transaction_id = $tid, paid_at = NOW()
             WHERE order_id = '$oid'"
        );
        if (!$ok) {
            $this->conn->query(
                "UPDATE `order` SET order_status = '$st' WHERE order_id = '$oid'"
            );
        }
        return true;
    }
}
?>