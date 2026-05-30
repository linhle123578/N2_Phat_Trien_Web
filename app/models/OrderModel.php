<?php

class OrderModel {
    private $conn;

    public function __construct() {
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
        mysqli_set_charset($this->conn, "utf8");
    }

    /**
     * Tạo đơn hàng mới
     * [FIX 2] Chỉ INSERT các cột thực sự có trong bảng `order`:
     *   order_id, customer_id, address_id, order_status,
     *   total_quantity_order, created_at
     * Trả về order_id hoặc false nếu lỗi
     */
    public function createOrder($customer_id, $address_id, $shipment_id, $total_amount, $payment_method) {
        $order_id = 'ORD-' . strtoupper(substr(uniqid(), -8));
        $cid      = $this->conn->real_escape_string($customer_id);
        $oid_esc  = $this->conn->real_escape_string($order_id);
        $addr_id  = $address_id ? "'" . $this->conn->real_escape_string($address_id) . "'" : "NULL";
        $ship_id  = $shipment_id ? "'" . $this->conn->real_escape_string($shipment_id) . "'" : "NULL";

        // Đếm tổng số lượng sản phẩm từ session (nếu có, không thì để 0)
        $checkout_items = $_SESSION['checkout_items'] ?? [];
        $total_qty = 0;
        foreach ($checkout_items as $item) {
            $total_qty += (int)($item['quantity'] ?? 1);
        }

        $ok = $this->conn->query(
            "INSERT INTO `order`
                (order_id, customer_id, address_id, shipment_id, order_status, total_quantity_order, created_at)
             VALUES
                ('$oid_esc', '$cid', $addr_id, $ship_id, 'Chờ xác nhận', $total_qty, NOW())"
        );

        if (!$ok) {
            error_log("OrderModel::createOrder failed: " . $this->conn->error);
            return false;
        }

        // Tạo dữ liệu thanh toán
        $pay_id = 'PAY-' . strtoupper(substr(uniqid(), -8));
        $pay_method = $this->conn->real_escape_string($payment_method);
        $total = (float)$total_amount;
        $this->conn->query(
            "INSERT INTO payment (payment_id, order_id, total_amount, payment_method, payment_status, payment_date)
             VALUES ('$pay_id', '$oid_esc', $total, '$pay_method', 'pending', NOW())"
        );

        return $order_id;
    }

    /**
     * Lấy thông tin 1 đơn hàng theo order_id
     */
    public function getOrderById($order_id) {
        $oid = $this->conn->real_escape_string($order_id);
        $res = $this->conn->query(
            "SELECT o.*, s.shipment_method, s.price as shipment_price 
             FROM `order` o 
             LEFT JOIN shipment s ON o.shipment_id = s.shipment_id 
             WHERE o.order_id = '$oid' LIMIT 1"
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
    /**
     * Trừ tồn kho sản phẩm sau khi đơn hàng được xác nhận
     */
    public function decreaseStock($product_id, $quantity) {
        $pid = $this->conn->real_escape_string($product_id);
        $qty = (int)$quantity;
        return $this->conn->query(
            "UPDATE product SET stock = GREATEST(0, stock - $qty) WHERE product_id = '$pid'"
        );
    }

    /**
     * Lấy danh sách các phương thức giao hàng
     */
    public function getAllShipments() {
        $res = $this->conn->query("SELECT * FROM shipment");
        $shipments = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $shipments[] = $row;
            }
        }
        return $shipments;
    }
}