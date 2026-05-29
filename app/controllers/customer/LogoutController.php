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
<<<<<<< HEAD
        header('Location: ../../../app/views/customer/TrangChu.php');
=======
        header('Location: ../../../public/index.php?page=TrangChu');
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
        exit;
    }
}