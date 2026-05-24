<?php
require_once __DIR__ . "/../../models/LogInModel.php";

class LogInController {
    
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
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['customer_name'] = $user['full_name'];
            
            echo json_encode(["status" => "success", "message" => "Đăng nhập thành công!"]);
            exit();
        } else {
            echo json_encode(["status" => "error", "message" => "Tài khoản hoặc mật khẩu không chính xác. Vui lòng thử lại!"]);
            exit();
        }
    }

    // ĐIỀU CHỈNH: GỬI MÃ OTP QUA API RESEND (TRẢ VỀ ĐÚNG "otp_sent" ĐỂ KÍCH HOẠT JS)
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
        $expiry = time() + (5 * 60); // Hiệu lực trong 5 phút

        $_SESSION['reset_password_session'] = [
            'email' => $email,
            'otp' => $otp,
            'expiry' => $expiry
        ];

        // API Key Resend của bạn
        $resendApiKey = 're_9RcihmAg_DQZySswhAec3UFN7JsmCgSRQ'; 
        
        $subject = 'Mã OTP khôi phục mật khẩu - Farm2Home';
        $body = "Chào <b>{$user['full_name']}</b>,<br><br>Mã OTP để thiết lập lại mật khẩu của bạn là: <b style='font-size:20px; color:#f0a04b;'>$otp</b><br>Mã này có hiệu lực trong vòng 5 phút.<br><br>Trân trọng,<br>Đội ngũ Farm2Home.";

        $data = [
            'from' => 'Farm2Home Support <onboarding@resend.dev>',
            'to' => [$email],
            'subject' => $subject,
            'html' => $body
        ];

        // Khởi tạo cURL gọi API Resend
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $resendApiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            // ĐỔI LẠI THÀNH "otp_sent" ĐỂ KHỚP VỚI KHỐI XỬ LÝ HIỂN THỊ TRONG LogIn.js CỦA BẠN
            echo json_encode(["status" => "otp_sent", "message" => "Mã OTP đã được gửi tới Email của bạn!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Không thể gửi mail qua Resend API. Mã phản hồi: " . $httpCode]);
        }
        exit();
    }

    // KIỂM TRA MÃ OTP VÀ LƯU LẠI MẬT KHẨU MỚI
    public function resetPassword() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $otp_input = trim($_POST['otp'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? ''; // Khớp hoàn toàn với trường nhận của dữ liệu AJAX gửi lên

        $session_data = $_SESSION['reset_password_session'] ?? null;

        if (!$session_data) {
            echo json_encode(["status" => "error", "message" => "Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn."]);
            exit();
        }

        if (time() > $session_data['expiry']) {
            unset($_SESSION['reset_password_session']);
            echo json_encode(["status" => "error", "message" => "Mã OTP đã hết thời gian hiệu lực. Vui lòng yêu cầu mã mới."]);
            exit();
        }

        if ($otp_input !== (string)$session_data['otp']) {
            echo json_encode(["status" => "error", "message" => "Mã OTP nhập vào không chính xác."]);
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
            unset($_SESSION['reset_password_session']); 
            echo json_encode(["status" => "success", "message" => "Đổi mật khẩu thành công! Bạn đang được đưa quay lại màn hình đăng nhập."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Có lỗi xảy ra khi cập nhật mật khẩu mới."]);
        }
        exit();
    }
}

// ─── PHÂN LUỒNG ROUTING ĐẦU ĐẾN
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
    } elseif ($action === 'reset') { // JavaScript gọi action=reset khi submit formReset
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        $controller->resetPassword();
    }
}
?>