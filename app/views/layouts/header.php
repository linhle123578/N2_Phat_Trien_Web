<!-- app/views/layouts/header.php -->

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? '';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Farm2Home</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet"
          href="<?= BASE_URL ?>assets/css/layout.css">
</head>

<body>

<nav class="navbar navbar-expand-lg fixed-top custom-navbar">

    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand"
           href="<?= BASE_URL ?>index.php?page=TrangChu">

            <img src="<?= BASE_URL ?>../Media/Logo.png"
                 alt="Farm2Home">

        </a>

        <!-- TOGGLE -->
        <button class="navbar-toggler"
                type="button"
                data-toggle="collapse"
                data-target="#navbarNav1"
                aria-controls="navbarNav1"
                aria-expanded="false"
                aria-label="Toggle navigation">

            <span class="navbar-toggler-icon">
                <i class="fas fa-bars" style="color:#183a1d;"></i>
            </span>

        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse" id="navbarNav1">

            <!-- CENTER -->
            <ul class="navbar-nav mx-auto">

                <li class="nav-item <?= ($page == 'TrangChu') ? 'active' : '' ?>">

                    <a class="nav-link"
                       href="<?= BASE_URL ?>index.php?page=TrangChu">

                        Trang Chủ

                    </a>

                </li>

                <li class="nav-item <?= ($page == 'Products') ? 'active' : '' ?>">

                    <a class="nav-link"
                       href="<?= BASE_URL ?>index.php?page=Products">

                        Sản Phẩm

                    </a>

                </li>

            </ul>

            <!-- RIGHT -->
            <div class="nav-right-actions">

                <!-- Notification -->
                <a href="#"
                   class="action-icon">

                    <i class="far fa-bell"></i>

                    <span class="icon-badge">0</span>

                </a>

                <!-- Cart -->
                <a href="<?= BASE_URL ?>index.php?page=Cart"
                   class="action-icon">

                    <i class="fas fa-shopping-cart"></i>

                    <span class="icon-badge">
                        <?= isset($_SESSION['cart'])
                            ? count($_SESSION['cart'])
                            : 0 ?>
                    </span>

                </a>

                <div class="nav-divider"></div>

                <?php if (isset($_SESSION['customer_id'])): ?>

                    <!-- USER ICON -->
                    <a href="<?= BASE_URL ?>index.php?page=ProfileCustomer"
                       class="action-icon user-avatar">

                        <i class="fas fa-user"></i>

                    </a>

                    <!-- LOGOUT -->
                    <a href="<?= BASE_URL ?>index.php?page=Logout"
                       class="btn btn-register ml-3">

                        Đăng xuất

                    </a>

                <?php else: ?>

                    <!-- LOGIN -->
                    <a href="<?= BASE_URL ?>index.php?page=LogIn"
                       class="btn-login">

                        Đăng Nhập

                    </a>

                    <!-- REGISTER -->
                    <a href="<?= BASE_URL ?>index.php?page=SignUp"
                       class="btn btn-register">

                        Đăng Ký

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</nav>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>