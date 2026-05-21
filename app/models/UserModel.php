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
    // Lấy thông tin khách hàng theo customer_id
    public function getCustomerById($customer_id) {
        $stmt = $this->conn->prepare("SELECT customer_id, full_name, phone, gender FROM customer WHERE customer_id = ?");
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Lấy địa chỉ mặc định của khách hàng (kèm receiver_name)
    public function getDefaultAddress($customer_id) {
        $stmt = $this->conn->prepare("SELECT address_id, receiver_name, province, district, ward, street_address FROM address WHERE customer_id = ? AND is_default = 1 LIMIT 1");
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Nếu cần lấy tất cả địa chỉ
    public function getAddresses($customer_id) {
        $stmt = $this->conn->prepare("SELECT * FROM address WHERE customer_id = ?");
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>