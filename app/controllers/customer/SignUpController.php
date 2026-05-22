<?php
require_once __DIR__ . "/../../models/SignUpModel.php";

class SignUpController {
    
    // 1. HÀM XỬ LÝ ĐĂNG KÝ THÀNH VIÊN
    public function register() {
        $fullname = trim($_POST['fullname'] ?? '');
        $gender   = trim($_POST['gender'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($fullname) || empty($gender) || empty($phone) || empty($email) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "Vui lòng điền đầy đủ các thông tin."]);
            exit();
        }

        $model = new SignUpModel();

        if ($model->isPhoneExists($phone)) {
            echo json_encode(["status" => "error", "message" => "Số điện thoại này đã được đăng ký!"]);
            exit();
        }

        if ($model->isEmailExists($email)) {
            echo json_encode(["status" => "error", "message" => "Địa chỉ Email này đã tồn tại!"]);
            exit();
        }

        $result = $model->registerCustomer($fullname, $gender, $phone, $email, $password);

        if ($result) {
            echo json_encode(["status" => "success", "message" => "Đăng ký thành công! Đang chuyển hướng..."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi: Không thể lưu dữ liệu tài khoản."]);
        }
        exit();
    }

    // 2. HÀM KIỂM TRA TRÙNG SỐ ĐIỆN THOẠI (Bị lỗi do thiếu hoặc sai hàm này trước đó)
    public function checkPhone() {
        // Lấy số điện thoại từ request POST gửi lên
        $phone = trim($_POST['phone'] ?? '');

        if (empty($phone)) {
            echo json_encode(["exists" => false, "message" => "Số điện thoại trống."]);
            exit();
        }

        $model = new SignUpModel();
        
        // Gọi hàm kiểm tra từ Model
        if ($model->isPhoneExists($phone)) {
            echo json_encode([
                "exists" => true, 
                "message" => "Số điện thoại này đã được đăng ký trước đó!"
            ]);
        } else {
            echo json_encode([
                "exists" => false, 
                "message" => "Số điện thoại khả dụng."
            ]);
        }
        exit(); // Ngắt luồng ngay sau khi trả dữ liệu về cho AJAX
    }
}

// ─── ĐIỀU HƯỚNG REQUEST (ROUTING) Ở CUỐI FILE ───

$controller = new SignUpController();

// Kiểm tra xem Javascipt có truyền tham số action=checkPhone trên URL hay không
if (isset($_GET['action']) && $_GET['action'] === 'checkPhone') {
    // Làm sạch bộ đệm đầu ra để chuỗi JSON trả về không bị dính khoảng trắng lỗi
    if (ob_get_length()) ob_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    // Gọi hàm checkPhone trong đối tượng vừa tạo
    $controller->checkPhone();
} else {
    // Nếu không phải action checkPhone thì xử lý luồng tạo tài khoản bình thường
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $controller->register();
}
?>