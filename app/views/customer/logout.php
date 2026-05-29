<?php
/**
 * Logout entry point
 * Route: index.php?page=Logout
 */
<<<<<<< HEAD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
=======

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
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