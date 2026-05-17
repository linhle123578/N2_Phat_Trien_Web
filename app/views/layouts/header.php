<!-- app/views/layouts/header.php -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <title>Farm2Home</title>

    <link rel="stylesheet"
          href="/N2_Phat_Trien_Web/public/assets/css/layout.css">

</head>

<body>

<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
    <div class="container">

        <a class="navbar-brand" href="#">
            <img src="/N2_Phat_Trien_Web/Media/Logo.png" alt="Farm2Home">
        </a>

        <button class="navbar-toggler"
                type="button"
                data-toggle="collapse"
                data-target="#navbarNav1">

            <span class="navbar-toggler-icon">
                <i class="fas fa-bars" style="color: #183a1d;"></i>
            </span>

        </button>

        <div class="collapse navbar-collapse" id="navbarNav1">

            <ul class="navbar-nav mx-auto">

                <li class="nav-item active">
                    <a class="nav-link" href="#">
                        Trang Chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        Sản Phẩm
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">
                        Liên Hệ
                    </a>
                </li>

            </ul>

            <div class="nav-right-actions">

                <a href="#" class="action-icon">
                    <i class="far fa-bell"></i>
                    <span class="icon-badge">0</span>
                </a>

                <a href="#" class="action-icon">
                    <i class="fas fa-shopping-cart"></i>

                    <span class="icon-badge">
                        <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                    </span>
                </a>

                <div class="nav-divider"></div>

                <?php if (isset($isLoggedIn) && $isLoggedIn): ?>

                    <a href="#" class="btn-login">
                        Tài khoản
                    </a>

                <?php else: ?>

                    <a href="#" class="btn-login">
                        Đăng Nhập
                    </a>

                    <button class="btn btn-register">
                        Đăng Ký
                    </button>

                <?php endif; ?>

            </div>

        </div>

    </div>
</nav>
</body>