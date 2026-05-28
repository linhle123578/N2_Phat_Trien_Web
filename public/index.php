<?php
session_start();

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

define(
    'BASE_URL',
    rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/'
);
define('ROOT_PATH', dirname(__DIR__));
/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$page = $_GET['page'] ?? 'TrangChu';

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

if(isset($_SESSION['admin'])){

    include ROOT_PATH . '/app/views/layouts/adminheader.php';

}else{

    include ROOT_PATH . '/app/views/layouts/header.php';

}
/*
|--------------------------------------------------------------------------
| ROUTER
|--------------------------------------------------------------------------
*/

switch($page){

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER
    |--------------------------------------------------------------------------
    */

    case 'TrangChu':
        include ROOT_PATH . '/app/views/customer/TrangChu.php';
    break;

    case 'Products':
    include ROOT_PATH . '/app/controllers/customer/ProductController.php';
break;
    break;

    case 'ProductDetail':
        include ROOT_PATH . '/app/views/customer/ProductDetail.php';
    break;

    case 'Cart':
        include ROOT_PATH . '/app/views/customer/cart.php';
    break;

    case 'Checkout':
        include ROOT_PATH . '/app/views/customer/Checkout.php';
    break;

    case 'LogIn':
        include ROOT_PATH . '/app/views/customer/LogIn.php';
    break;

    case 'SignUp':
        include ROOT_PATH . '/app/views/customer/SignUp.php';
    break;

    case 'Logout':
        include ROOT_PATH . '/app/views/customer/logout.php';
    break;

    case 'ProfileCustomer':
        include ROOT_PATH . '/app/views/customer/ProfileCustomer.php';
    break;

    case 'OrderHistory':
        include ROOT_PATH . '/app/views/customer/OrderHistory.php';
    break;

    case 'OrderDetail':
        include ROOT_PATH . '/app/views/customer/OrderDetail.php';
    break;

    case 'ReturnRequest':
        include ROOT_PATH . '/app/views/customer/ReturnRequest.php';
    break;

    case 'MomoPayment':
        include ROOT_PATH . '/app/views/customer/MomoPayment.php';
    break;

    case 'Chatbot':
        include ROOT_PATH . '/app/views/customer/Chatbot.php';
    break;

    case 'ReadMeVC':
        include ROOT_PATH . '/app/views/customer/ReadMeVC.php';
    break;


    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    case 'Dashboard':
        include ROOT_PATH . '/app/views/admin/Dashboard.php';
    break;

    case 'ProductAdmin':
        include ROOT_PATH . '/app/views/admin/ProductAdmin.php';
    break;

    case 'ControlOrder':
        include ROOT_PATH . '/app/views/admin/ControlOrder.php';
    break;

    case 'ProfileAdmin':
        include ROOT_PATH . '/app/views/admin/ProfileAdmin.php';
    break;

    case 'ProductController':
    include ROOT_PATH . '/app/controllers/customer/ProductController.php';
break;

case 'CartController':
    include ROOT_PATH . '/app/controllers/customer/CartController.php';
break;

case 'CheckoutController':
    include ROOT_PATH . '/app/controllers/customer/CheckoutController.php';
break;

case 'LoginController':
    include ROOT_PATH . '/app/controllers/customer/LoginController.php';
break;

case 'LogoutController':
    include ROOT_PATH . '/app/controllers/customer/LogoutController.php';
break;

case 'OrderHistoryController':
    include ROOT_PATH . '/app/controllers/customer/OrderHistoryController.php';
break;

case 'ProductDetailController':
    include ROOT_PATH . '/app/controllers/customer/ProductDetailController.php';
break;

case 'ReturnRequestController':
    include ROOT_PATH . '/app/controllers/customer/ReturnRequestController.php';
break;

case 'SignUpController':
    include ROOT_PATH . '/app/controllers/customer/SignUpController.php';
break;

case 'MomoPaymentController':
    include ROOT_PATH . '/app/controllers/customer/MomoPaymentController.php';
break;

case 'DashboardController':
    include ROOT_PATH . '/app/controllers/admin/DashboardController.php';
break;

case 'ProductAdminController':
    include ROOT_PATH . '/app/controllers/admin/ProductAdminController.php';
break;

case 'ControlOrderController':
    include ROOT_PATH . '/app/controllers/admin/ControlOrderController.php';
break;

case 'ProfileAdminController':
    include ROOT_PATH . '/app/controllers/admin/ProfileAdminController.php';
break;
    /*
    |--------------------------------------------------------------------------
    | DEFAULT
    |--------------------------------------------------------------------------
    */

    default:
        include ROOT_PATH . '/app/views/customer/TrangChu.php';
    break;
}
