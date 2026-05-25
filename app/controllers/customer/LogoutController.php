<?php
/**
 * Controller: LogoutController
 * Xử lý đăng xuất khỏi hệ thống
 * Tương ứng với View: app/views/customer/logout.php
 */
class LogoutController
{
    /**
     * Thực hiện đăng xuất: xóa session, cookie, redirect về trang chủ
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Xóa toàn bộ dữ liệu session
        session_unset();
        session_destroy();
        // Xóa cookie remember nếu có
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        // Redirect về trang chủ
        // Dùng đường dẫn tương đối từ views/customer/ lên root project
        header('Location: /N2_Phat_Trien_Web/app/views/customer/TrangChu.php');
        exit;
    }
}