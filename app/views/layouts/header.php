<?php
// app/views/layouts/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentScript = strtolower(basename($_SERVER['PHP_SELF'], '.php'));
$isHome = ($currentScript === 'trangchu' || $currentScript === 'index');
$isProduct = ($currentScript === 'products' || $currentScript === 'productdetail' || $currentScript === 'productcontroller' || $currentScript === 'productdetailcontroller');

$isLoggedIn = isset($_SESSION['user']) || isset($_SESSION['customer_id']);

// Khởi tạo số đếm giỏ hàng an toàn
$cartCount = 0;
if (isset($_SESSION['customer_id'])) {
    if (!class_exists('ProductModel')) {
        require_once __DIR__ . '/../../models/ProductModel.php';
    }
    $pm = new ProductModel();
    $cartCount = $pm->getCartCount($_SESSION['customer_id']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm2Home</title>

    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- CSS Chuẩn cho Header -->
    <link rel="stylesheet" href="../../../public/assets/css/loginheader.css?v=<?= time() ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
    <div class="container">
        <!-- LOGO -->
        <a class="navbar-brand" href="../../../app/views/customer/TrangChu.php">
            <img src="../../../Media/Logo.png" alt="Farm2Home">
        </a>

        <!-- MENU TOGGLE -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav1">
            <span class="navbar-toggler-icon">
                <i class="fas fa-bars" style="color: #183a1d;"></i>
            </span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav1">
            <!-- MENU CENTER -->
            <ul class="navbar-nav mx-auto navbar-center-custom">
                <li class="nav-item <?= $isHome ? 'active' : '' ?>">
                    <a class="nav-link" href="../../../app/views/customer/TrangChu.php">Trang Chủ</a>
                </li>
                <li class="nav-item <?= $isProduct ? 'active' : '' ?>">
                    <a class="nav-link" href="../../../app/views/customer/Products.php">Sản Phẩm</a>
                </li>
            </ul>

            <!-- RIGHT ACTIONS -->
            <div class="nav-right-actions ml-auto">
                <!-- CART -->
                <a href="../../../app/views/customer/cart.php" class="action-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="icon-badge" id="cart-count"><?= $cartCount ?></span>
                </a>

                <div class="nav-divider"></div>

                <!-- LOGIN / ACCOUNT -->
                <?php if ($isLoggedIn): ?>
                    <a href="../../../app/views/customer/ProfileCustomer.php" class="btn-login">Tài khoản</a>
                    <a href="../../../app/views/customer/logout.php" class="btn btn-register" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi tài khoản không?');">Đăng xuất</a>
                <?php else: ?>
                    <a href="../../../app/views/customer/LogIn.php" class="btn-login">Đăng Nhập</a>
                    <a href="../../../app/views/customer/SignUp.php" class="btn btn-register">Đăng Ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>