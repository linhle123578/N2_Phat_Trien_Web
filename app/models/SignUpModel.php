<?php
class SignUpModel {
    private $conn;

    public function __construct() {
        // Cấu hình kết nối đám mây TiDB Cloud
        $host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
        $port = 4000;
        $user = "3YHrkxqAKWynehu.root";
        $pass = "BzDRrZAdAT2jLuyd";
        $dbname = "db_web_farm2home";

        $this->conn = mysqli_init();
        if (!$this->conn) {
            die(json_encode(["status" => "error", "message" => "mysqli_init thất bại"]));
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
            echo json_encode(["status" => "error", "message" => "Kết nối TiDB Cloud thất bại: " . mysqli_connect_error()]);
            exit();
        }

        mysqli_set_charset($this->conn, "utf8mb4");

        $this->conn->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
    }

    // 1. Kiểm tra số điện thoại
    public function isPhoneExists($phone) {
        $phone = $this->conn->real_escape_string($phone);
        $sql = "SELECT customer_id FROM customer WHERE phone = '$phone' LIMIT 1";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0);
    }

    // 2. Kiểm tra email
    public function isEmailExists($email) {
        $email = $this->conn->real_escape_string($email);
        $sql = "SELECT account_id FROM account WHERE email = '$email' LIMIT 1";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0);
    }

    // 3. Đăng ký tài khoản khách hàng mới
    public function registerCustomer($fullname, $gender, $phone, $email, $password) {
        $fullname = $this->conn->real_escape_string($fullname);
        $gender   = $this->conn->real_escape_string($gender);
        $phone    = $this->conn->real_escape_string($phone);
        $email    = $this->conn->real_escape_string($email);
        
        $hashed_password = md5($password);
        
        // TỰ ĐỘNG TẠO ACCOUNT_ID TĂNG DẦN
        $account_id = 'ACC001'; 
        $res_max_acc = $this->conn->query("SELECT account_id FROM account WHERE account_id LIKE 'ACC%' ORDER BY account_id DESC LIMIT 1");
        if ($res_max_acc && $res_max_acc->num_rows > 0) {
            $row = $res_max_acc->fetch_assoc();
            $max_id = $row['account_id']; 
            $num = (int)substr($max_id, 3); 
            $next_num = $num + 1; 
            $account_id = 'ACC' . str_pad($next_num, 3, '0', STR_PAD_LEFT); 
        }

        //TỰ ĐỘNG TẠO CUSTOMER_ID TĂNG DẦN
        $customer_id = 'CUS001'; 
        $res_max_cus = $this->conn->query("SELECT customer_id FROM customer WHERE customer_id LIKE 'CUS%' ORDER BY customer_id DESC LIMIT 1");
        if ($res_max_cus && $res_max_cus->num_rows > 0) {
            $row = $res_max_cus->fetch_assoc();
            $max_id = $row['customer_id'];
            $num = (int)substr($max_id, 3);
            $next_num = $num + 1; 
            $customer_id = 'CUS' . str_pad($next_num, 3, '0', STR_PAD_LEFT); 
        }

        $this->conn->begin_transaction();

        try {
            // Lưu tài khoản đăng nhập 
            $sql_account = "INSERT INTO account (account_id, email, account_password, account_role) 
                            VALUES ('$account_id', '$email', '$hashed_password', 'customer')";

            if (!$this->conn->query($sql_account)) {
                throw new Exception("Lỗi bảng account: " . $this->conn->error);
            }

            // Lưu thông tin cá nhân khách hàng
            $sql_customer = "INSERT INTO customer (customer_id, full_name, phone, gender, account_id) 
                             VALUES ('$customer_id', '$fullname', '$phone', '$gender', '$account_id')";
            
            if (!$this->conn->query($sql_customer)) {
                throw new Exception("Lỗi bảng customer: " . $this->conn->error);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            
            echo json_encode(["status" => "error", "message" => "Lỗi thực thi dữ liệu: " . $e->getMessage()]);
            exit();
        }
    }
}
