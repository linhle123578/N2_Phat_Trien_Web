<?php
class LogInModel {
    private $conn;

    public function __construct() {
        // Khởi tạo kết nối cho database đám mây TiDB Cloud (yêu cầu SSL)
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

    // Kiểm tra tài khoản bằng Email hoặc Số điện thoại
    public function checkCredentials($identity, $password) {
        $identity = $this->conn->real_escape_string($identity);
        
        // Tìm kiếm xem user nhập SĐT hoặc Email
        $sql = "SELECT * FROM customer WHERE phone = '$identity' OR email = '$identity' LIMIT 1";
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Nếu bạn dùng mã hóa password bằng password_hash() thì dùng: password_verify($password, $user['password'])
            // Còn nếu đang lưu text thuần hoặc MD5 để làm bài tập lớn:
            if ($password === $user['password']) {
                return $user; // Trả về thông tin user nếu khớp mật khẩu
            }
        }
        return false;
    }

    // Kiểm tra Email có tồn tại trong hệ thống không (Dùng cho Quên mật khẩu)
    public function getUserByEmail($email) {
        $email = $this->conn->real_escape_string($email);
        $sql = "SELECT * FROM customer WHERE email = '$email' LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    // Cập nhật mật khẩu mới
    public function updatePassword($email, $new_password) {
        $email = $this->conn->real_escape_string($email);
        $new_password = $this->conn->real_escape_string($new_password);
        
        $sql = "UPDATE customer SET password = '$new_password' WHERE email = '$email'";
        return $this->conn->query($sql);
    }
}
?>