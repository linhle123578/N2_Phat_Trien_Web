<head>
    <meta charset="UTF-8">
    <!-- QUAN TRỌNG: thiếu dòng này gây collapse trên desktop -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="/N2_Phat_Trien_Web/public/assets/css/loginheader.css">
    
</head>

<!-- Cuối <body>: Bootstrap JS (jQuery → Popper → Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<body>
<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
    <div class="container">
        <a class="navbar-brand" href="/N2_Phat_Trien_Web/app/views/customer/TrangChu.php">
            <img src="/N2_Phat_Trien_Web/Media/Logo.png" alt="Farm2Home">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav1">
            <span class="navbar-toggler-icon">
                <i class="fas fa-bars" style="color: #183a1d;"></i>
            </span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav1">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="/N2_Phat_Trien_Web/app/views/customer/TrangChu.php">Trang Chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/N2_Phat_Trien_Web/app/views/customer/Products.php">Sản Phẩm</a>
                </li>
            </ul>

            <div class="nav-right-actions">
                <a href="#" class="action-icon">
                    <i class="far fa-bell"></i>
                    <span class="icon-badge">1</span>
                </a>
                <a href="/N2_Phat_Trien_Web/app/views/customer/cart.php" class="action-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="icon-badge">1</span>
                </a>
                <a href="/N2_Phat_Trien_Web/app/views/customer/ProfileCustomer.php" class="action-icon user-avatar">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </div>
    </div>
</nav>
</body>