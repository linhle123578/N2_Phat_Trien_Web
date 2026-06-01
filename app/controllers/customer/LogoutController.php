<?php

class LogoutController
{
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
        header('Location: ../../../app/views/customer/TrangChu.php');
        exit;
    }
}
