<?php
class UserModel {
    private $conn;

    // Khởi tạo kết nối DB y hệt file Cart.php của má
    public function __construct() {
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
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

    // Lấy tất cả địa chỉ của khách hàng
    public function getAddresses($customer_id) {
        $stmt = $this->conn->prepare("SELECT * FROM address WHERE customer_id = ? ORDER BY is_default DESC, address_id ASC");
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Thêm địa chỉ mới
    public function addAddress($customer_id, $receiver_name, $address_type, $province, $district, $ward, $street_address, $is_default = 0) {
        if ($is_default == 1) {
            // Set all existing to 0
            $su = $this->conn->prepare("UPDATE address SET is_default = 0 WHERE customer_id = ?");
            $su->bind_param("s", $customer_id);
            $su->execute();
        }

        $address_id = 'ADDR_' . uniqid();
        $stmt = $this->conn->prepare(
            "INSERT INTO address (address_id, customer_id, receiver_name, address_type, province, district, ward, street_address, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssssssi", $address_id, $customer_id, $receiver_name, $address_type, $province, $district, $ward, $street_address, $is_default);
        if ($stmt->execute()) {
            return $address_id;
        }
        return false;
    }
}
