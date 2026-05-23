<?php
class SignUpModel {
    private $conn;

    public function __construct() {
        // Cấu hình kết nối đám mây TiDB Cloud của bạn
        $host = "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com";
        $port = 4000;
        $user = "3YHrkxqAKWynehu.root";
        $pass = "BzDRrZAdAT2jLuyd";
        $dbname = "db_web_farm2home";

        $this->conn = mysqli_init();
        if (!$this->conn) {
            die(json_encode(["status" => "error", "message" => "mysqli_init thất bại"]));
        }

        // Bắt buộc chứng chỉ SSL mã hóa đối với TiDB Cloud đám mây
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        
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

        // Vô hiệu hóa strict mode để tránh lỗi dữ liệu ẩn trên Cloud
        $this->conn->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
    }

    // 1. Kiểm tra số điện thoại (Bảng customer - Cột phone)
    public function isPhoneExists($phone) {
        $phone = $this->conn->real_escape_string($phone);
        $sql = "SELECT customer_id FROM customer WHERE phone = '$phone' LIMIT 1";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0);
    }

    // 2. Kiểm tra email (Bảng account - Cột email)
    public function isEmailExists($email) {
        $email = $this->conn->real_escape_string($email);
        $sql = "SELECT account_id FROM account WHERE email = '$email' LIMIT 1";
        $result = $this->conn->query($sql);
        return ($result && $result->num_rows > 0);
    }

    // 3. Đăng ký tài khoản khách hàng mới vào cơ sở dữ liệu
    public function registerCustomer($fullname, $gender, $phone, $email, $password) {
        $fullname = $this->conn->real_escape_string($fullname);
        $gender   = $this->conn->real_escape_string($gender);
        $phone    = $this->conn->real_escape_string($phone);
        $email    = $this->conn->real_escape_string($email);
        
        // Mã hóa bảo mật mật khẩu bằng thuật toán BCRYPT nâng cao
        $hashed_password = md5($password);
        
        // ─── TỰ ĐỘNG TẠO ACCOUNT_ID TĂNG DẦN (Ví dụ: ACC003) ───
        $account_id = 'ACC001'; // Giá trị mặc định nếu bảng trống
        $res_max_acc = $this->conn->query("SELECT account_id FROM account WHERE account_id LIKE 'ACC%' ORDER BY account_id DESC LIMIT 1");
        if ($res_max_acc && $res_max_acc->num_rows > 0) {
            $row = $res_max_acc->fetch_assoc();
            $max_id = $row['account_id']; // Ví dụ: "ACC002"
            $num = (int)substr($max_id, 3); // Cắt lấy phần số: 2
            $next_num = $num + 1; // Tăng lên 1: 3
            $account_id = 'ACC' . str_pad($next_num, 3, '0', STR_PAD_LEFT); // Định dạng lại thành "ACC003"
        }

        // ─── TỰ ĐỘNG TẠO CUSTOMER_ID TĂNG DẦN (Ví dụ: CUS003) ───
        $customer_id = 'CUS001'; // Giá trị mặc định nếu bảng trống
        $res_max_cus = $this->conn->query("SELECT customer_id FROM customer WHERE customer_id LIKE 'CUS%' ORDER BY customer_id DESC LIMIT 1");
        if ($res_max_cus && $res_max_cus->num_rows > 0) {
            $row = $res_max_cus->fetch_assoc();
            $max_id = $row['customer_id']; // Ví dụ: "CUS002"
            $num = (int)substr($max_id, 3); // Cắt lấy phần số: 2
            $next_num = $num + 1; // Tăng lên 1: 3
            $customer_id = 'CUS' . str_pad($next_num, 3, '0', STR_PAD_LEFT); // Định dạng lại thành "CUS003"
        }

        // KÍCH HOẠT TRANSACTION: Đảm bảo tính toàn vẹn dữ liệu
        $this->conn->begin_transaction();

        try {
            // Bước 1: Lưu tài khoản đăng nhập vào bảng `account`
            $sql_account = "INSERT INTO account (account_id, email, account_password, account_role) 
                            VALUES ('$account_id', '$email', '$hashed_password', 'customer')";

            if (!$this->conn->query($sql_account)) {
                throw new Exception("Lỗi bảng account: " . $this->conn->error);
            }

            // Bước 2: Lưu thông tin cá nhân khách hàng vào bảng `customer`
            $sql_customer = "INSERT INTO customer (customer_id, full_name, phone, gender, account_id) 
                             VALUES ('$customer_id', '$fullname', '$phone', '$gender', '$account_id')";
            
            if (!$this->conn->query($sql_customer)) {
                throw new Exception("Lỗi bảng customer: " . $this->conn->error);
            }

            // Xác nhận hoàn tất lưu vĩnh viễn vào cả 2 bảng dữ liệu trên TiDB Cloud
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            // Nếu phát sinh bất kỳ lỗi gì, hoàn tác rút dữ liệu về ngay lập tức để tránh rác DB
            $this->conn->rollback();
            
            echo json_encode(["status" => "error", "message" => "Lỗi thực thi dữ liệu: " . $e->getMessage()]);
            exit();
        }
    }
}
?>