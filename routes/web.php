<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$page = $_GET['page'] ?? 'TrangChu';

$isLoggedIn = isset($_SESSION['user']);

/*
========================================
HEADER
========================================
*/
$headerUser = __DIR__ . '/../app/views/layouts/header.php';
$headerGuest = __DIR__ . '/../app/views/layouts/loginheader.php';

if ($isLoggedIn && file_exists($headerUser)) {
    include $headerUser;
} else {
    include $headerGuest;
}

/*
========================================
ROUTER
========================================
*/
switch($page){

    case 'TrangChu':
        include __DIR__ . '/../app/views/customer/TrangChu.php';
    break;

    case 'products':                              // hoặc 'Products' tuỳ link header
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

    case 'logout':
        session_destroy();
        header("Location: ?page=login");
        exit;

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
    $chatbotFile = __DIR__ . '/../app/views/customer/Chatbot.php';
    if (file_exists($chatbotFile)) {
        include $chatbotFile;
    }
}

/*
========================================
FOOTER
========================================
*/
$footerFile = __DIR__ . '/../app/views/layouts/footer.php';
if (file_exists($footerFile)) {
    include $footerFile;
}
?>