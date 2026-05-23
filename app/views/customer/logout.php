<?php
/**
 * View: logout.php
 * Đặt tại: app/views/customer/logout.php
 * Entry point xử lý đăng xuất → dùng LogoutController
 */
// Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Load và gọi controller
require_once __DIR__ . '/../../controllers/customer/LogoutController.php';
$controller = new LogoutController();
$controller->logout();
exit;