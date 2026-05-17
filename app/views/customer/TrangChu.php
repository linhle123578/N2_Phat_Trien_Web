<?php
session_start();

/*
|--------------------------------------------------------------------------
| CONNECT DATABASE TIDB
|--------------------------------------------------------------------------
*/

$conn = mysqli_init();

mysqli_ssl_set(
    $conn,
    NULL,
    NULL,
    "C:/xampp/htdocs/N2_Phat_Trien_Web/isrgrootx1.pem",
    NULL,
    NULL
);

mysqli_real_connect(
    $conn,
    "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "3YHrkxqAKWynehu.root",
    "BzDRrZAdAT2jLuyd",
    "db_web_farm2home",
    4000,
    NULL,
    MYSQLI_CLIENT_SSL
);

mysqli_set_charset($conn, "utf8mb4");

/*
|--------------------------------------------------------------------------
| QUERY SẢN PHẨM NỔI BẬT
|--------------------------------------------------------------------------
*/

$sql = "
SELECT 
    p.product_id,
    p.product_name,
    p.price,
    p.product_image,
    p.unit,
    p.stock,
    c.name AS category_name,
    COALESCE(SUM(oi.quantity), 0) AS total_sold
FROM product p
LEFT JOIN category c 
    ON p.category_id = c.category_id
LEFT JOIN orderitem oi 
    ON p.product_id = oi.product_id
GROUP BY 
    p.product_id,
    p.product_name,
    p.price,
    p.product_image,
    p.unit,
    p.stock,
    c.name
ORDER BY c.name DESC
LIMIT 6
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die(mysqli_error($conn));
}

$featuredProducts = [];

while ($row = mysqli_fetch_assoc($result)) {
    $featuredProducts[] = $row;
}

$isLoggedIn = isset($_SESSION['customer_id']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Farm2Home - Nông Sản Sạch</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <link rel="stylesheet"
          href="http://localhost/N2_Phat_Trien_Web/public/assets/css/Trang_chu.css"/>
</head>

<body>

<!-- HEADER -->

<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
    <div class="container">

        <a class="navbar-brand" href="#">
            <img src="../Media/Logo.png" alt="Farm2Home">
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

                <?php if ($isLoggedIn): ?>

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

<main>

    <!-- HERO -->

    <section class="hero-section">

        <div id="carousel-container" class="h-100">

            <div class="carousel-item-custom active">

                <img class="hero-bg"
     src="/N2_Phat_Trien_Web/Media/canh_dong_3.jpg"
     alt="">

                <div class="hero-overlay">

                    <div class="hero-card shadow-lg">

                        <h1 class="font-headline">
                            Sạch từ tâm,<br>
                            <span style="color: var(--accent-green)">
                                Tươi tận gốc
                            </span>
                        </h1>

                        <p class="py-3 text-secondary">
                            Farm2Home mang nông sản Việt chất lượng cao từ những cánh đồng tận tụy đến bàn ăn gia đình bạn.
                        </p>

                    </div>

                </div>

            </div>

            <div class="carousel-item-custom">

                <img class="hero-bg"
                     src="/N2_Phat_Trien_Web/Media/canh_dong_2.jpg"
                     alt="">

                <div class="hero-overlay">

                    <div class="hero-card shadow-lg">

                        <h1 class="font-headline">
                            Nguồn gốc<br>
                            <span style="color: var(--orange-sub)">
                                minh bạch
                            </span>
                        </h1>

                        <p class="py-3 text-secondary">
                            Truy xuất nguồn gốc 100% qua QR code, đảm bảo chất lượng từng sản phẩm sạch.
                        </p>

                    </div>

                </div>

            </div>

        </div>

        <div class="dots-wrapper">

            <button class="dot-custom active"
                    onclick="goToSlide(0)">
            </button>

            <button class="dot-custom"
                    onclick="goToSlide(1)">
            </button>

        </div>

    </section>

    <!-- VỀ CHÚNG TÔI -->

    <section class="container py-section">

        <div class="row align-items-center">

            <div class="col-lg-6 mb-5 mb-lg-0">

                <div class="about-collage">

                    <div class="about-small-imgs">

                        <img src="/N2_Phat_Trien_Web/Media/canh_dong_1.jpg"
                             alt=""
                             class="img-fluid rounded-20 shadow-sm"
                             style="height: 240px; object-fit: cover; width: 100%;">

                        <img src="/N2_Phat_Trien_Web/Media/canh_dong_5.jpg"
                             alt=""
                             class="img-fluid rounded-20 shadow-sm"
                             style="height: 240px; object-fit: cover; width: 100%;">

                    </div>

                    <div class="about-large-img">

                        <img src="/N2_Phat_Trien_Web/Media/canh_dong_4.jpg"
                             alt=""
                             class="img-fluid rounded-20 shadow-lg"
                             style="height: 495px; object-fit: cover; width: 100%;">

                    </div>

                </div>

            </div>

            <div class="col-lg-6 pl-lg-5 text-left">

                <h2 class="font-headline font-weight-bold display-4 mb-4">
                    Về chúng tôi
                </h2>

                <p class="text-muted mb-5"
                   style="line-height: 1.8; font-size: 1.05rem;">

                    Farm2Home ra đời với sứ mệnh là cầu nối trực tiếp,
                    phá vỡ mọi rào cản trung gian để đưa nông sản tươi ngon
                    từ tận vườn đến bàn ăn của mọi gia đình.

                </p>

            </div>

        </div>

    </section>

    <!-- SẢN PHẨM NỔI BẬT -->

    <section class="container py-section">

        <div class="d-flex justify-content-between align-items-end mb-5">

            <h2 class="font-headline font-weight-bold mb-0">
                Sản phẩm nổi bật
            </h2>

            <a href="#"
               class="font-weight-bold text-dark border-bottom"
               style="text-decoration: none;">

                Xem tất cả →

            </a>

        </div>

        <div class="row">

            <?php if (!empty($featuredProducts)): ?>

                <?php foreach ($featuredProducts as $product): ?>

                    <div class="col-md-4 mb-4">

                        <div class="product-card shadow-sm">

                            <div class="product-img-wrap">

                                <span class="badge-custom"
                                      style="background: var(--orange-sub);">

                                    <?= htmlspecialchars($product['category_name']) ?>

                                </span>

                                <img
    src="/N2_Phat_Trien_Web/Media/<?= htmlspecialchars($product['product_image']) ?>"
    alt="<?= htmlspecialchars($product['product_name']) ?>"
    style="width:100%; height:250px; object-fit:cover;"
>

                            </div>

                            <div class="p-4 text-left">

                                <h6 class="font-weight-bold">

                                    <?= htmlspecialchars($product['product_name']) ?>

                                </h6>

                                <p class="small text-muted mb-2">

                                    Đơn vị:
                                    <?= htmlspecialchars($product['unit']) ?>

                                </p>

                                <p class="small text-muted mb-2">

                                    Tồn kho:
                                    <?= (int)$product['stock'] ?>

                                </p>

                                <p class="small text-muted mb-4">

                                    Đã bán:
                                    <?= (int)$product['total_sold'] ?>

                                </p>

                                <div class="d-flex justify-content-between align-items-center">

                                    <span style="color: #8b5000;
                                                 font-weight: 800;
                                                 font-size: 1.3rem;">

                                        <?= number_format($product['price'], 0, ',', '.') ?>đ

                                        <small class="text-muted">
                                            /<?= htmlspecialchars($product['unit']) ?>
                                        </small>

                                    </span>

                                    <button class="btn-cart-round shadow-sm">

                                        <span class="material-symbols-outlined">
                                            add_shopping_cart
                                        </span>

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center">

                    <h5 class="text-muted">
                        Không có sản phẩm nổi bật
                    </h5>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<!-- FOOTER -->

<footer class="footer-custom">

    <div class="container-fluid px-lg-5">

        <div class="row">

            <div class="col-lg-5 col-md-12 mb-4 mb-lg-0">

                <img src="../Media/Logo-trang.png"
                     alt="Farm2Home"
                     class="mb-3"
                     style="max-width: 180px; filter: brightness(0) invert(1);">

                <p class="pr-lg-4 mb-4"
                   style="font-size: 0.95rem; line-height: 1.6; color: rgba(254, 251, 233, 0.9);">

                    Farm2Home mang nông sản sạch, tươi ngon và an toàn đến tận tay bạn.

                </p>

            </div>

        </div>

        <hr class="footer-divider">

        <div class="row align-items-center footer-bottom">

            <div class="col-md-4 mb-3 mb-md-0 social-icons text-center text-md-left">

                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>

            </div>

            <div class="col-md-4 mb-3 mb-md-0 text-center">

                &copy; 2026 Farm2Home. Tất cả quyền được bảo lưu.

            </div>

        </div>

    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

    let currentSlide = 0;

    const slides = document.querySelectorAll('.carousel-item-custom');

    const dots = document.querySelectorAll('.dot-custom');

    function goToSlide(index) {

        slides[currentSlide].classList.remove('active');

        dots[currentSlide].classList.remove('active');

        currentSlide = (index + slides.length) % slides.length;

        slides[currentSlide].classList.add('active');

        dots[currentSlide].classList.add('active');
    }

    setInterval(() => {

        goToSlide(currentSlide + 1);

    }, 5000);

</script>

</body>
</html>