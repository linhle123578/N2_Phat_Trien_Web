<?php

class OrderModel {
    private $conn;

    // Kết nối database theo chuẩn của bạn
    public function __construct() {
        // Khởi tạo kết nối cho database đám mây (yêu cầu SSL)
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        
        // Thực hiện kết nối tới TiDB Cloud
        $success = mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com", // Host
            "3YHrkxqAKWynehu.root",                                  // User
            "BzDRrZAdAT2jLuyd",                                      // Password
            "db_web_farm2home",                                      // Database
            4000,                                                    // Port
            NULL,
            MYSQLI_CLIENT_SSL
        );

        // Kiểm tra kết nối
        if (!$success) {
    throw new Exception("Kết nối database thất bại: " . mysqli_connect_error());
}

        mysqli_set_charset($this->conn, "utf8");
    }

    // Tạo đơn hàng mới và trả về ID của đơn hàng đó
    public function createOrder($customer_id, $address_id, $total_quantity, $total_amount, $payment_method, $shipment_method = 'Giao hàng tiêu chuẩn') {
        // Tạo đơn trong bảng `order`
        $stmt = $this->conn->prepare("INSERT INTO `order` (customer_id, address_id, order_status, total_quantity_order, created_at) VALUES (?, ?, 'pending', ?, NOW())");
        $stmt->bind_param("ssi", $customer_id, $address_id, $total_quantity);
        if (!$stmt->execute()) {
            error_log("SQL Error createOrder: " . $stmt->error);
            return false;
        }
        $order_id = $this->conn->insert_id; // Lấy ID tự động (int) - cần chuyển sang dạng Oxxx?
        
        // Thêm bản ghi payment
        $stmt2 = $this->conn->prepare("INSERT INTO payment (order_id, total_amount, payment_method, payment_status, payment_date) VALUES (?, ?, ?, 'Chờ thanh toán', NOW())");
        $stmt2->bind_param("iss", $order_id, $total_amount, $payment_method);
        $stmt2->execute();
        
        // Thêm shipment mặc định
        $stmt3 = $this->conn->prepare("INSERT INTO shipment (order_id, shipment_method, shipment_status, estimated_date) VALUES (?, ?, 'Chờ xử lý', DATE_ADD(NOW(), INTERVAL 3 DAY))");
        $stmt3->bind_param("is", $order_id, $shipment_method);
        $stmt3->execute();
        
        return $order_id;
    }

    public function getOrderById($order_id) {
        $sql = "SELECT o.*, p.total_amount, p.payment_method, s.shipment_method 
                FROM `order` o 
                LEFT JOIN payment p ON o.order_id = p.order_id 
                LEFT JOIN shipment s ON o.order_id = s.order_id 
                WHERE o.order_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>