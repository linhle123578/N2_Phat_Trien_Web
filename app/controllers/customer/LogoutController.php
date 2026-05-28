<?php
/**
 * Controller: LogoutController
 * Xử lý đăng xuất khỏi hệ thống
 * Tương ứng với View: app/views/customer/logout.php
 */
class LogoutController
{
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Xoá toàn bộ session data
        $_SESSION = [];
        session_unset();
        session_destroy();

        // 2. Xoá cookie session (QUAN TRỌNG)
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 3. Xoá remember token nếu có
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // 4. Redirect VỀ INDEX (QUAN TRỌNG NHẤT)
        header('Location: ' . BASE_URL . '/index.php?page=TrangChu');
        exit;
    }

}