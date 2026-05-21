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
    throw new Exception("Kết nối database thất bại: " . mysqli_connect_error());
}

        mysqli_set_charset($this->conn, "utf8");
    }

    // Thêm chi tiết cho một đơn hàng
    public function addDetail($order_id, $product_id, $price, $quantity) {
        $stmt = $this->conn->prepare("INSERT INTO order_details (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $order_id, $product_id, $price, $quantity);
        return $stmt->execute();
    }

    public function getItemsByOrderId($order_id) {
        /* JOIN bảng order_details (od) với bảng products (p) 
           Chú ý: Sửa lại tên cột cho đúng với Database của bạn nếu có khác biệt
           (VD: p.name, p.image, od.quantity, od.price)
        */
        $sql = "SELECT od.quantity, od.price, p.name, p.image 
                FROM order_details od 
                JOIN products p ON od.product_id = p.id 
                WHERE od.order_id = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            // Gán lại key cho khớp với vòng lặp trong file view momopayment.php
            $items[] = [
                'name'  => $row['name'],
                'img'   => $row['image'],   // Lấy cột ảnh từ bảng products
                'qty'   => $row['quantity'],// Lấy số lượng từ order_details
                'price' => $row['price']    // Lấy giá từ order_details
            ];
        }
        
        return $items;
    }
}
?>