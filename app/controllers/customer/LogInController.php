<?php
// Thêm dấu gạch chéo chuẩn để nạp chính xác file Model
require_once __DIR__ . "/../../models/LogInModel.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../libs/PHPMailer/Exception.php';
require __DIR__ . '/../../libs/PHPMailer/PHPMailer.php';
require __DIR__ . '/../../libs/PHPMailer/SMTP.php';

class LogInController {
    
    // 1. XỬ LÝ ĐĂNG NHẬP THUẦN
    public function login() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $identity = trim($_POST['identity'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identity) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ tài khoản và mật khẩu."]);
            exit();
        }

        // Khởi tạo Model
        $model = new LogInModel();
        
        // Thực hiện kiểm tra thông tin tài khoản
        $user = $model->checkCredentials($identity, $password);

        if ($user) {
            // Đăng nhập thành công, lưu thông tin vào Session
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['full_name']; // Khớp với cột full_name trong DB
            
            echo json_encode(["status" => "success", "message" => "Đăng nhập thành công!"]);
            exit();
        } else {
            // Thông báo khi sai thông tin thông tin đăng nhập
            echo json_encode(["status" => "error", "message" => "Tài khoản hoặc mật khẩu không chính xác. Vui lòng thử lại!"]);
            exit();
        }
    }

    // 2. XỬ LÝ YÊU CẦU QUÊN MẬT KHẨU (GỬI OTP)
    public function forgotPassword() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập địa chỉ Email."]);
            exit();
        }

        $model = new LogInModel();
        $user = $model->getUserByEmail($email);

        if (!$user) {
            echo json_encode(["status" => "error", "message" => "Email này không tồn tại trên hệ thống!"]);
            exit();
        }

        // Tạo mã OTP ngẫu nhiên 6 chữ số
        $otp = rand(100000, 999999);
        $expiry = time() + (5 * 60); // Hết hạn sau 5 phút

        // Lưu thông tin phiên khôi phục vào Session
        $_SESSION['reset_password_session'] = [
            'email' => $email,
            'otp' => $otp,
            'expiry' => $expiry,
            'verified' => false
        ];

        // Gửi Mail qua SMTP Gmail
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'haogiang0401@gmail.com'; // Email cấu hình của bạn
            $mail->Password   = 'vwyb uemh twel hixu';    // App Password ứng dụng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->CharSet    = "UTF-8";

            $mail->setFrom('haogiang0401@gmail.com', 'Farm2Home Support');
            $mail->addAddress($email, $user['full_name']);

            $mail->isHTML(true);
            $mail->Subject = '=?UTF-8?B?'.base64_encode('Mã OTP khôi phục mật khẩu - Farm2Home').'?=';
            $mail->Body    = "Chào <b>{$user['full_name']}</b>,<br><br>Mã OTP để thiết lập lại mật khẩu của bạn là: <b style='font-size:20px; color:#f0a04b;'>$otp</b><br>Mã này có hiệu lực trong vòng 5 phút.<br><br>Trân trọng,<br>Đội ngũ Farm2Home.";

            $mail->send();
            echo json_encode(["status" => "success", "message" => "Mã OTP đã được gửi tới Email của bạn!"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Không thể gửi Email. Lỗi Mailer: " . $mail->ErrorInfo]);
        }
        exit();
    }

    // 3. XỬ LÝ XÁC THỰC MÃ OTP
    public function verifyOTP() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $otp_input = trim($_POST['otp'] ?? '');

        $session_data = $_SESSION['reset_password_session'] ?? null;

        if (!$session_data) {
            echo json_encode(["status" => "error", "message" => "Yêu cầu không hợp lệ hoặc đã hết hạn."]);
            exit();
        }

        if (time() > $session_data['expiry']) {
            unset($_SESSION['reset_password_session']);
            echo json_encode(["status" => "error", "message" => "Mã OTP đã hết thời gian hiệu lực. Vui lòng yêu cầu mã mới."]);
            exit();
        }

        if ($otp_input === (string)$session_data['otp']) {
            $_SESSION['reset_password_session']['verified'] = true; // Đánh dấu đã xác thực thành công
            echo json_encode(["status" => "success", "message" => "Xác thực OTP thành công! Vui lòng nhập mật khẩu mới."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Mã OTP nhập vào không chính xác."]);
        }
        exit();
    }

    // 4. TIẾN HÀNH ĐỔI MẬT KHẨU MỚI
    public function resetPassword() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_new_password'] ?? '';

        $session_data = $_SESSION['reset_password_session'] ?? null;

        if (!$session_data || $session_data['verified'] !== true) {
            echo json_encode(["status" => "error", "message" => "Hành động bị từ chối. Bạn chưa xác thực OTP."]);
            exit();
        }

        if (strlen($new_pass) < 6) {
            echo json_encode(["status" => "error", "message" => "Mật khẩu mới phải từ 6 ký tự trở lên."]);
            exit();
        }

        if ($new_pass !== $confirm_pass) {
            echo json_encode(["status" => "error", "message" => "Xác nhận mật khẩu mới không trùng khớp."]);
            exit();
        }

        $model = new LogInModel();
        $update = $model->updatePassword($session_data['email'], $new_pass);

        if ($update) {
            unset($_SESSION['reset_password_session']); // Xóa sạch session khôi phục
            echo json_encode(["status" => "success", "message" => "Đổi mật khẩu thành công! Vui lòng đăng nhập lại."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Có lỗi xảy ra khi lưu mật khẩu mới."]);
        }
        exit();
    }
}

// ─── PHÂN LUỒNG ROUTING ĐẦU ĐẾN ───
$controller = new LogInController();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        $controller->login();
    } elseif ($action === 'forgot') {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        $controller->forgotPassword();
    } elseif ($action === 'verify') {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        $controller->verifyOTP();
    } elseif ($action === 'reset') {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        $controller->resetPassword();
    }
}
?>