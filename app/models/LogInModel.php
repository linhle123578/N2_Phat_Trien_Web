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

    //2. XỬ LÝ KIỂM TRA ĐĂNG NHẬP
    public function checkCredentials($identity, $password) {
        $identity = $this->conn->real_escape_string($identity);
        $md5_password = md5($password);
        
        // Truy vấn chung cho cả admin và customer, lấy role từ bảng account
        $sql = "
            SELECT 
                a.account_id, 
                a.email, 
                a.account_role,
                adm.admin_id,
                adm.full_name AS admin_name,
                c.customer_id,
                c.full_name AS customer_name
            FROM account a
            LEFT JOIN admin adm ON a.account_id = adm.account_id
            LEFT JOIN customer c ON a.account_id = c.account_id
            WHERE (a.email = '$identity' OR adm.phone = '$identity' OR c.phone = '$identity')
              AND a.account_password = '$md5_password'
            LIMIT 1
        ";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Xác định hệ thống vai trò nội bộ dựa vào cột role của account
            $db_role = mb_strtolower(trim((string)($row['account_role'] ?? '')), 'UTF-8');
            
            // Các role thuộc khối quản trị
            $isAdminRole = (strpos($db_role, 'quản trị') !== false || 
                            strpos($db_role, 'quản lý') !== false || 
                            strpos($db_role, 'giám đốc') !== false || 
                            strpos($db_role, 'admin') !== false ||
                            $db_role === 'director' || 
                            $db_role === 'manager');

            if ($isAdminRole || !empty($row['admin_id'])) {
                $row['uid'] = $row['admin_id'];
                $row['full_name'] = $row['admin_name'];
                $row['system_role'] = 'admin';
            } else {
                $row['uid'] = $row['customer_id'];
                $row['full_name'] = $row['customer_name'];
                $row['system_role'] = 'customer';
            }
            return $row;
        }
        
        return false;
    }

    //3. KIỂM TRA EMAIL TỒN TẠI 
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

    //4. ĐẶT LẠI MẬT KHẨU MỚI
    public function updatePassword($email, $new_password) {
        $email = $this->conn->real_escape_string($email);
        $md5_password = md5($new_password);
        
        $sql = "UPDATE account SET account_password = '$md5_password' WHERE email = '$email'";
        return $this->conn->query($sql);
    }
}
