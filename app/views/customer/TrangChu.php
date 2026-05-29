<?php
ob_start();
include_once __DIR__ . '/../layouts/header.php';

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
    NULL,
    NULL,
    NULL
);

// Bỏ qua xác thực chứng chỉ SSL (fix lỗi XAMPP Windows)
mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet"
          href="../../../public/assets/css/Trang_chu.css"/>

<title>Farm2Home - Nông Sản Sạch</title>


<<<<<<< HEAD
=======
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <link rel="stylesheet"
          href="../../../public/assets/css/Trang_chu.css"/>
</head>
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
<?php include __DIR__ . '/Chatbot.php'; ?>

<main>

    <!-- HERO -->

    <section class="hero-section">

        <div id="carousel-container" class="h-100">

            <div class="carousel-item-custom active">

                <img class="hero-bg"
     src="../../../Media/canh_dong_3.jpg"
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
                     src="../../../Media/canh_dong_2.jpg"
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

                        <img src="../../../Media/canh_dong_1.jpg"
                             alt=""
                             class="img-fluid rounded-20 shadow-sm"
                             style="height: 240px; object-fit: cover; width: 100%;">

                        <img src="../../../Media/canh_dong_5.jpg"
                             alt=""
                             class="img-fluid rounded-20 shadow-sm"
                             style="height: 240px; object-fit: cover; width: 100%;">

                    </div>

                    <div class="about-large-img">

                        <img src="../../../Media/canh_dong_4.jpg"
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

<<<<<<< HEAD
            <a href="../../../app/views/customer/Products.php"
=======
            <a href="../../../public/index.php?page=products"
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
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

<<<<<<< HEAD
                                <a href="../../../app/views/customer/ProductDetail.php?id=<?= $product['product_id'] ?>">
=======
                                <a href="../../../public/index.php?page=productdetail&id=<?= $product['product_id'] ?>">
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
                                    <img
        src="../../../Media/<?= htmlspecialchars($product['product_image']) ?>"
        alt="<?= htmlspecialchars($product['product_name']) ?>"
        style="width:100%; height:250px; object-fit:cover;"
    >
                                </a>

                            </div>

                            <div class="card-body p-4 text-left">

                                <h6 class="font-weight-bold">
<<<<<<< HEAD
                                    <a href="../../../app/views/customer/ProductDetail.php?id=<?= $product['product_id'] ?>" style="color: inherit; text-decoration: none;" class="product-title">
=======
                                    <a href="../../../public/index.php?page=productdetail&id=<?= $product['product_id'] ?>" style="color: inherit; text-decoration: none;">
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </a>
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

                                    <button class="btn-cart-round shadow-sm btn-add-cart" data-product-id="<?= $product['product_id'] ?>">

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

<<<<<<< HEAD
</script>
<script src="../../../public/assets/js/Products.js"></script>
<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
=======
</script>
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
