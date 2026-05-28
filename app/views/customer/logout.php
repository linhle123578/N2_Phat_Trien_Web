<?php
/**
 * Logout entry point
 * Route: index.php?page=Logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controllers/customer/LogoutController.php';

$controller = new LogoutController();
$controller->logout();

/*
|-------------------------------------------------
| Redirect về trang chủ
|-------------------------------------------------
*/

header("Location: /index.php?page=TrangChu");
exit;