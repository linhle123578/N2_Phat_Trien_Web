<?php
class SignUpModel {
    private $conn;

    public function __construct() {
        // Khởi tạo kết nối cho database đám mây TiDB Cloud giống hệ thống Cart cũ
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        
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

        if (!$success) {
            die("Kết nối database thất bại: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->conn, "utf8");
    }

    // Kiểm tra số điện thoại đã tồn tại trong hệ thống chưa
    public function isPhoneExists($phone) {
        $phone = $this->conn->real_escape_string($phone);
        $sql = "SELECT customer_id FROM customer WHERE phone = '$phone' LIMIT 1";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0);
    }

    // Kiểm tra email trùng lặp bổ sung
    public function isEmailExists($email) {
        $email = $this->conn->real_escape_string($email);
        $sql = "SELECT customer_id FROM customer WHERE email = '$email' LIMIT 1";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0);
    }

    // Đăng ký tài khoản khách hàng mới vào cơ sở dữ liệu
    public function registerCustomer($fullname, $gender, $phone, $email, $password) {
        $fullname = $this->conn->real_escape_string($fullname);
        $gender = $this->conn->real_escape_string($gender);
        $phone = $this->conn->real_escape_string($phone);
        $email = $this->conn->real_escape_string($email);
        
        // Mã hóa bảo mật mật khẩu bằng thuật toán BCRYPT nâng cao
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Tạo chuỗi mã ID Khách hàng ngẫu nhiên duy nhất (Ví dụ: CUS-F8A2D35B)
        $customer_id = 'CUS-' . strtoupper(substr(uniqid(), -8));
        
        $sql = "INSERT INTO customer (customer_id, fullname, gender, phone, email, password, created_at) 
                VALUES ('$customer_id', '$fullname', '$gender', '$phone', '$email', '$hashed_password', NOW())";
                
        return $this->conn->query($sql);
    }
}
?>