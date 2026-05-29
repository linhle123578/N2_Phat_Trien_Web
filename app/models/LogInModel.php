<?php
class LogInModel {
    private $conn;

    public function __construct() {
        // Cấu hình thông số kết nối TiDB Cloud
        $host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
        $port = 4000;
        $user = "3YHrkxqAKWynehu.root";
        $pass = "BzDRrZAdAT2jLuyd";
        $dbname = "db_web_farm2home";

        $this->conn = mysqli_init();
        if (!$this->conn) {
            die(json_encode(["status" => "error", "message" => "Khởi tạo kết nối thất bại"]));
        }

        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

        $success = @mysqli_real_connect(
            $this->conn,
            $host,
            $user,
            $pass,
            $dbname,
            $port,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        if (!$success) {
            $err = mysqli_connect_error() ?: "Lỗi không xác định";
            echo json_encode(["status" => "error", "message" => "Không thể kết nối với hệ thống Cloud. Chi tiết: " . $err]);
            exit();
        }
        
        $this->conn->query("SET NAMES 'utf8mb4'");
    }

    /**
     * 2. XỬ LÝ KIỂM TRA ĐĂNG NHẬP (Giữ nguyên gốc chuẩn cũ của bạn)
     */
    public function checkCredentials($identity, $password) {
        $identity = $this->conn->real_escape_string($identity);
        $md5_password = md5($password);
        
        // Luồng 1: Khách hàng
        $sql = "SELECT c.customer_id, c.full_name, c.phone, a.email, a.account_password 
                FROM account a
                INNER JOIN customer c ON a.account_id = c.account_id
                WHERE (a.email = '$identity' OR c.phone = '$identity') 
                  AND a.account_password = '$md5_password'
                LIMIT 1";
                
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        // Luồng 2: Quản lý
        $sql_admin = "SELECT adm.admin_id AS customer_id, adm.department AS full_name, NULL AS phone, a.email, a.account_password 
                      FROM account a
                      INNER JOIN admin adm ON a.account_id = adm.account_id
                      WHERE a.email = '$identity' AND a.account_password = '$md5_password'
                      LIMIT 1";

        $result_admin = $this->conn->query($sql_admin);
        if ($result_admin && $result_admin->num_rows > 0) {
            return $result_admin->fetch_assoc();
        }
        
        return false;
    }

    /**
     * 3. KIỂM TRA EMAIL TỒN TẠI (Dùng cho luồng quên mật khẩu/gửi OTP)
     */
    public function getUserByEmail($email) {
        $email = $this->conn->real_escape_string($email);
        
        $sql = "SELECT c.full_name, a.email 
                FROM account a
                INNER JOIN customer c ON a.account_id = c.account_id
                WHERE a.email = '$email' 
                LIMIT 1";
                
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    /**
     * 4. TIẾN HÀNH ĐẶT LẠI MẬT KHẨU MỚI (Mã hóa MD5)
     */
    public function updatePassword($email, $new_password) {
        $email = $this->conn->real_escape_string($email);
        $md5_password = md5($new_password);
        
        $sql = "UPDATE account SET account_password = '$md5_password' WHERE email = '$email'";
        return $this->conn->query($sql);
    }
}
?>
