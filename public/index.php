<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
========================================
TIME ZONE
========================================
*/
date_default_timezone_set('Asia/Ho_Chi_Minh');

/*
========================================
BASE URL
========================================
*/
define('BASE_URL', '/N2_Phat_Trien_Web/public/');

/*
========================================
LẤY PAGE
========================================
*/
$page = $_GET['page'] ?? 'TrangChu';

/*
========================================
DEBUG PAGE
========================================
*/
// echo $page;

/*
========================================
LOGOUT
========================================
*/
if ($page == 'logout') {

    session_destroy();

    header("Location: index.php?page=login");

    exit;
}

/*
========================================
CHECK LOGIN
========================================
*/
$isLoggedIn = isset($_SESSION['user']);

/*
========================================
HEADER
========================================
*/
if ($isLoggedIn) {

    include __DIR__ . '/../app/views/layouts/header.php';

} else {

    include __DIR__ . '/../app/views/layouts/loginheader.php';
}

/*
========================================
ROUTER
========================================
*/
switch ($page) {

    case 'TrangChu':

        include __DIR__ . '/../app/views/customer/TrangChu.php';

    break;

    case 'products':

        include __DIR__ . '/../app/views/customer/Products.php';

    break;

    case 'productdetail':

        include __DIR__ . '/../app/views/customer/ProductDetail.php';

    break;

    case 'cart':

        include __DIR__ . '/../app/views/customer/cart.php';

    break;

    case 'checkout':

        include __DIR__ . '/../app/views/customer/Checkout.php';

    break;

    case 'login':

        include __DIR__ . '/../app/views/customer/LogIn.php';

    break;

    case 'signup':

        include __DIR__ . '/../app/views/customer/SignUp.php';

    break;

    case 'profile':

        include __DIR__ . '/../app/views/customer/ProfileCustomer.php';

    break;

    case 'orders':

        include __DIR__ . '/../app/views/customer/OrderHistory.php';

    break;

    case 'orderdetail':

        include __DIR__ . '/../app/views/customer/OrderDetail.php';

    break;

    case 'return':

        include __DIR__ . '/../app/views/customer/ReturnRequest.php';

    break;

    case 'momo':

        include __DIR__ . '/../app/views/customer/MomoPayment.php';

    break;

    default:

        echo "<h1>404 - Page Not Found</h1>";

    break;
}

/*
========================================
CHATBOT
========================================
*/
if ($page == 'TrangChu') {

    $chatbot = __DIR__ . '/../app/views/customer/Chatbot.php';

    if (file_exists($chatbot)) {

        include $chatbot;
    }
}

/*
========================================
FOOTER
========================================
*/
include __DIR__ . '/../app/views/layouts/footer.php';

?>