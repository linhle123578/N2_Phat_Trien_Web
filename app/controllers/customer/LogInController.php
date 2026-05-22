<?php
require_once __DIR__ . "../../models/LogInModel.php";

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

        $model = new LogInModel();
        $user = $model->checkCredentials($identity, $password);

        if ($user) {
            // Đăng nhập thành công, lưu thông tin vào Session
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['fullname'];
            
            echo json_encode(["status" => "success", "message" => "Đăng nhập thành công! Đang chuyển hướng..."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Tài khoản hoặc mật khẩu không chính xác."]);
        }
        exit();
    }

    // 2. YÊU CẦU QUÊN MẬT KHẨU - GỬI MÃ QUA GMAIL
    public function forgotPassword() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập định dạng Email hợp lệ."]);
            exit();
        }

        $model = new LogInModel();
        $user = $model->getUserByEmail($email);

        if (!$user) {
            echo json_encode(["status" => "error", "message" => "Email này không tồn tại trên hệ thống Farm2Home."]);
            exit();
        }

        // Tạo mã OTP khôi phục ngẫu nhiên gồm 6 chữ số
        $otp = rand(100000, 999999);
        $_SESSION['reset_password_session'] = [
            'email' => $email,
            'otp' => $otp,
            'expire' => time() + (5 * 60) // Hiệu lực 5 phút
        ];

        // Gửi Mail qua SMTP của Google
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'email_cua_ban@gmail.com'; // Gmail ứng dụng của bạn
            $mail->Password   = 'xxxx xxxx xxxx xxxx';      // Mật khẩu ứng dụng 16 ký tự Google cấp
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('email_cua_ban@gmail.com', 'Farm2Home Support');
            $mail->addAddress($email, $user['fullname']);

            $mail->isHTML(true);
            $mail->Subject = '[Farm2Home] Yêu cầu khôi phục mật khẩu tài khoản';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #f0a04b; border-radius: 10px; max-width: 500px;'>
                    <h2 style='color: #183a1d;'>Xin chào {$user['fullname']},</h2>
                    <p>Chúng tôi nhận được yêu cầu cấp lại mật khẩu cho tài khoản của bạn.</p>
                    <p>Mã xác thực khôi phục mật khẩu (OTP) là:</p>
                    <div style='background-color: #fefbe9; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; color: #f0a04b; border-radius: 5px; letter-spacing: 5px;'>
                        {$otp}
                    </div>
                    <p style='color: #6c757d; font-size: 13px; margin-top: 15px;'>Mã này có hiệu lực trong vòng 5 phút. Vui lòng không tiết lộ mã này cho bất kỳ ai.</p>
                </div>
            ";

            $mail->send();
            echo json_encode(["status" => "otp_sent", "message" => "Mã xác thực khôi phục mật khẩu đã gửi đến Gmail của bạn."]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Không thể gửi Email. Lỗi: " . $mail->ErrorInfo]);
        }
        exit();
    }

    // 3. XÁC NHẬN OTP VÀ CẬP NHẬT MẬT KHẨU MỚI
    public function resetPassword() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $otp = trim($_POST['otp'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!isset($_SESSION['reset_password_session'])) {
            echo json_encode(["status" => "error", "message" => "Yêu cầu khôi phục không còn hiệu lực. Vui lòng lấy lại mã mới."]);
            exit();
        }

        $session_data = $_SESSION['reset_password_session'];

        if (time() > $session_data['expire']) {
            unset($_SESSION['reset_password_session']);
            echo json_encode(["status" => "error", "message" => "Mã OTP đã quá hạn 5 phút. Vui lòng lấy lại mã."]);
            exit();
        }

        if ((int)$otp !== (int)$session_data['otp']) {
            echo json_encode(["status" => "error", "message" => "Mã xác thực OTP không chính xác."]);
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

        // Cập nhật vào DB qua Model
        $model = new LogInModel();
        $update = $model->updatePassword($session_data['email'], $new_pass);

        if ($update) {
            unset($_SESSION['reset_password_session']); // Xóa sạch dấu vết khôi phục mật khẩu
            echo json_encode(["status" => "success", "message" => "Đổi mật khẩu thành công! Vui lòng đăng nhập lại."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Có lỗi xảy ra khi lưu mật khẩu mới."]);
        }
        exit();
    }
}

// ─── PHÂN LUỒNG ROUTING ───
$controller = new LogInController();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        if (ob_get_length()) ob_clean(); header('Content-Type: application/json');
        $controller->login();
    } elseif ($action === 'forgot') {
        if (ob_get_length()) ob_clean(); header('Content-Type: application/json');
        $controller->forgotPassword();
    } elseif ($action === 'reset') {
        if (ob_get_length()) ob_clean(); header('Content-Type: application/json');
        $controller->resetPassword();
    }
}
?>