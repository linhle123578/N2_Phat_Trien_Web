<?php
class LogInModel {
    private $conn;

    public function __construct() {
        // 1. Cấu hình thông số kết nối TiDB Cloud đám mây (GIỮ NGUYÊN GỐC 100%)
        $host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
        $port = 4000;
        $user = "3YHrkxqAKWynehu.root";
        $pass = "BzDRrZAdAT2jLuyd";
        $dbname = "db_web_farm2home";

        $this->conn = mysqli_init();
        if (!$this->conn) {
            die(json_encode(["status" => "error", "message" => "mysqli_init thất bại"]));
        }

        // Bắt buộc cấu hình chứng chỉ SSL đối với cổng kết nối TiDB Cloud
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        
        // Thực hiện kết nối an toàn với Cloud
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
            echo json_encode(["status" => "error", "message" => "Không thể kết nối với hệ thống Cloud: " . mysqli_connect_error()]);
            exit();
        }
        
        $this->conn->query("SET NAMES 'utf8mb4'");
    }

    /**
     * 1. XỬ LÝ KIỂM TRA ĐĂNG NHẬP
     */
    public function checkCredentials($identity, $password) {
        $identity = $this->conn->real_escape_string($identity);
        
        // Mã hóa mật khẩu người dùng nhập vào sang MD5
        $md5_password = md5($password);
        
        // [LUỒNG 1]: KIỂM TRA KHÁCH HÀNG (Giữ nguyên vẹn 100% logic code cũ của bạn không đổi một chữ)
        $sql = "SELECT c.customer_id, c.full_name, c.phone, a.email, a.account_password 
                FROM account a
                INNER JOIN customer c ON a.account_id = c.account_id
                WHERE (a.email = '$identity' OR c.phone = '$identity') 
                  AND a.account_password = '$md5_password'
                LIMIT 1";
                
        $result = $this->conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc(); // Khách hàng khớp MD5 thành công, trả về dữ liệu user
        }

        // [LUỒNG 2]: ĐIỀU CHỈNH CHÍNH XÁC CHO QUẢN LÝ (Chỉ chạy khi luồng khách hàng không tìm thấy dữ liệu)
        // Đã xóa bỏ hoàn toàn trường phone không tồn tại để tránh gây lỗi crash kết nối Cloud
        $sql_admin = "SELECT adm.admin_id AS customer_id, adm.department AS full_name, NULL AS phone, a.email, a.account_password 
                      FROM account a
                      INNER JOIN admin adm ON a.account_id = adm.account_id
                      WHERE a.email = '$identity' AND a.account_password = '$md5_password'
                      LIMIT 1";

        $result_admin = $this->conn->query($sql_admin);

        if ($result_admin && $result_admin->num_rows > 0) {
            return $result_admin->fetch_assoc(); // Quản lý đăng nhập thành công, trả về mảng khớp định dạng cũ
        }
        
        return false; // Sai tài khoản hoặc sai mật khẩu
    }

    /**
     * 2. KIỂM TRA EMAIL TỒN TẠI (Dùng cho luồng quên mật khẩu/gửi OTP) - GIỮ NGUYÊN GỐC
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
     * 3. TIẾN HÀNH ĐẶT LẠI MẬT KHẨU MỚI (Mã hóa MD5) - GIỮ NGUYÊN GỐC
     */
    public function updatePassword($email, $new_password) {
        $email = $this->conn->real_escape_string($email);
        
        // Mã hóa mật khẩu mới sang định dạng MD5 để lưu trữ đồng bộ vào DB
        $md5_password = md5($new_password);
        
        $sql = "UPDATE account SET account_password = '$md5_password' WHERE email = '$email'";
        return $this->conn->query($sql);
    }
}
?>