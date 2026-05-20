<?php
class UserModel {
    private $conn;

    // Khởi tạo kết nối DB y hệt file Cart.php của má
    public function __construct() {
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3YHrkxqAKWynehu.root",
            "BzDRrZAdAT2jLuyd",
            "db_web_farm2home",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );
        mysqli_set_charset($this->conn, "utf8");
        
        if ($this->conn->connect_error) {
            die("Kết nối database thất bại: " . $this->conn->connect_error);
        }
    }

    // Hàm lấy thông tin Khách hàng + Địa chỉ mặc định
    public function getUserById($id) {
        $safe_id = $this->conn->real_escape_string($id);
        
        // Dùng LEFT JOIN để kết nối bảng customer và address
        // Dùng CONCAT để nối các cột địa chỉ lại với nhau thành 1 dòng duy nhất
        // Lọc a.is_default = 1 để chỉ lấy địa chỉ mặc định
        $sql = "SELECT 
                    c.full_name AS fullname, 
                    c.phone, 
                    CONCAT(a.street_address, ', ', a.ward, ', ', a.district, ', ', a.province) AS address,
                    a.address_type 
                FROM customer c
                LEFT JOIN address a ON c.customer_id = a.customer_id AND a.is_default = 1
                WHERE c.customer_id = '$safe_id'";
        
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null; 
    }
}
?>