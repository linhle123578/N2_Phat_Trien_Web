<!-- app/views/layouts/header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Farm2Home</title>

    <link rel="stylesheet"
          href="/N2_Phat_Trien_Web/public/assets/css/layout.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand"
           href="/N2_Phat_Trien_Web/public/index.php?page=TrangChu">
            <img src="/N2_Phat_Trien_Web/Media/Logo.png" alt="Farm2Home">
        </a>

        <!-- MENU TOGGLE -->
        <button class="navbar-toggler"
                type="button"
                data-toggle="collapse"
                data-target="#navbarNav1">
            <span class="navbar-toggler-icon">
                <i class="fas fa-bars" style="color: #183a1d;"></i>
            </span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav1">

            <!-- MENU CENTER -->
            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a class="nav-link"
                       href="/N2_Phat_Trien_Web/app/views/customer/TrangChu.php">
                        Trang Chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link"
                       href="/N2_Phat_Trien_Web/app/views/customer/Products.php">
                        Sản Phẩm
                    </a>
                </li>


            </ul>

            <!-- RIGHT ACTIONS -->
            <div class="nav-right-actions">

                <!-- NOTIFICATION -->
                <a href="#" class="action-icon">
                    <i class="far fa-bell"></i>
                    <span class="icon-badge">0</span>
                </a>

                <!-- CART -->
                <a href="/N2_Phat_Trien_Web/public/Cart.php"
                   class="action-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="icon-badge">
                        <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                    </span>
                </a>

                <div class="nav-divider"></div>

                <!-- LOGIN / ACCOUNT -->
                <?php if (!empty($_SESSION['user'])): ?>

                    <a href="index.php?page=ProfileCustomer"
                       class="btn-login">
                        Tài khoản
                    </a>

                    <a href="/N2_Phat_Trien_Web/app/views/customer/Logout.php"
                       class="btn btn-register">
                        Đăng xuất
                    </a>

                <?php else: ?>

                    <a href="/N2_Phat_Trien_Web/app/views/customer/LogIn.php"
                       class="btn-login">
                        Đăng Nhập
                    </a>

                    <a href="/N2_Phat_Trien_Web/app/views/customer/SignUp.php"
                       class="btn btn-register">
                        Đăng Ký
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>
</nav>