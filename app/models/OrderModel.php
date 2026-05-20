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
            die("Kết nối database thất bại: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->conn, "utf8");
    }

    // Tạo đơn hàng mới và trả về ID của đơn hàng đó
    public function createOrder($name, $phone, $address, $shipping_fee, $total_amount, $payment_method) {
        // Chuẩn bị câu lệnh SQL (chống SQL Injection)
        $stmt = $this->conn->prepare("INSERT INTO orders (customer_name, customer_phone, customer_address, shipping_fee, total_amount, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        
        $stmt->bind_param("sssiis", $name, $phone, $address, $shipping_fee, $total_amount, $payment_method);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id; // Trả về ID đơn hàng vừa tạo để dùng cho Order Detail
        } else {
            return false;
        }
    }
}
?>