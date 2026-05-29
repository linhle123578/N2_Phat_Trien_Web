<?php
/**
 * View: logout.php
 * Đặt tại: app/views/customer/logout.php
 * Entry point xử lý đăng xuất → dùng LogoutController
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/customer/LogoutController.php';
$controller = new LogoutController();
$controller->logout();
exit;