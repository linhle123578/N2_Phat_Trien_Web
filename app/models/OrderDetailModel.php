<?php

class OrderDetailModel {
    private $conn;

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

    // Thêm chi tiết cho một đơn hàng
    public function addDetail($order_id, $product_id, $price, $quantity) {
        $stmt = $this->conn->prepare("INSERT INTO order_details (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $order_id, $product_id, $price, $quantity);
        return $stmt->execute();
    }
}
?>