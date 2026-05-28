<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

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

if (!$conn) {
    die("Lỗi kết nối Database: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
$current_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($current_id)) {
    // Nếu không truyền ID trên URL, tự động lấy sản phẩm đầu tiên trong database để hiển thị
    $query = "SELECT * FROM product LIMIT 1";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);
} else {
    // Ngược lại, lấy đúng sản phẩm có ID được chọn (dùng Prepared Statement để bảo mật)
    $query = "SELECT * FROM product WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $current_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
}

// Nếu không tìm thấy sản phẩm nào tương ứng trong DB
if (!$product) {
    die("Sản phẩm không tồn tại hoặc cơ sở dữ liệu chưa có dữ liệu!");
}

// 3. Lấy các sản phẩm liên quan (Cùng danh mục category_id và loại trừ sản phẩm hiện tại)
$query_related = "SELECT * FROM product WHERE category_id = ? AND product_id != ? LIMIT 4";
$stmt_related = mysqli_prepare($conn, $query_related);
mysqli_stmt_bind_param($stmt_related, "ss", $product['category_id'], $product['product_id']);
mysqli_stmt_execute($stmt_related);
$result_related = mysqli_stmt_get_result($stmt_related);

$related_products = [];
while ($row = mysqli_fetch_assoc($result_related)) {
    $related_products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['product_name']) ?> - Farm2Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout.css">
    <link href="<?= BASE_URL ?>assets/css/ProductDetail.css" rel="stylesheet">
</head>
<body>


<div class="breadcrumb-section">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php?page=TrangChu">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>index.php?page=Products">Sản phẩm</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['product_name']) ?></li>
        </ol>
    </div>
</div>

<main>
<div class="page-wrapper">
<div class="container">

    <div class="product-card">
        <div class="row m-0 container-split">
            <div class="col-lg-5 col-md-6 p-4 left-image-panel">
                <div class="gallery-main" id="mainImg">
                    <img src="<?= BASE_URL ?>Media/<?= htmlspecialchars($product['product_image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" id="mainImgTag" style="width:100%;height:100%;object-fit:cover;">
                </div>
            </div>

            <div class="col-lg-7 col-md-6 p-4 right-info-panel">
                <h1 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h1>

                <div class="price-row mt-3">
                    <span class="price-main"><?= number_format($product['price'], 0, ',', '.') ?>₫</span>
                    <span class="price-unit">/ <?= htmlspecialchars($product['unit']) ?></span>
                </div>

                <div class="d-flex align-items-center mt-4 mb-4" style="gap:12px;">
                    <span style="font-size:0.88rem;color:#6c757d;font-weight:500;">Số lượng:</span>
                    <div class="qty-control">
                        <button class="qty-btn" onclick="changeQty(-1)">−</button>
                        <input class="qty-input" id="qtyInput" type="number" value="1" min="1" max="<?= htmlspecialchars($product['stock']) ?>">
                        <button class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    <span style="font-size:0.82rem;color:#6c757d;">Còn <?= htmlspecialchars($product['stock']) ?> <?= htmlspecialchars($product['unit']) ?></span>
                </div>

                <div class="action-buttons">

<?php if (isset($_SESSION['customer_id'])): ?>

    <!-- ĐÃ ĐĂNG NHẬP -->

    <form action="<?= BASE_URL ?>index.php?page=Cart" method="POST"
          class="w-100 d-flex"
          style="gap:12px;">

        <input type="hidden"
               name="product_id"
               value="<?= htmlspecialchars($product['product_id']) ?>">

        <input type="hidden"
               name="quantity"
               id="cartQuantity"
               value="1">

        <button type="submit"
                class="btn-cart">

            <i class="fas fa-shopping-cart mr-2"></i>
            Thêm vào giỏ hàng

        </button>

        <button type="submit"
                name="buy_now"
                value="1"
                class="btn-buynow">

            Mua ngay

        </button>

    </form>

<?php else: ?>

    <!-- CHƯA ĐĂNG NHẬP -->

    <button class="btn-cart"
            onclick="requireLogin()">

        <i class="fas fa-shopping-cart mr-2"></i>
        Thêm vào giỏ hàng

    </button>

    <button class="btn-buynow"
            onclick="requireLogin()">

        Mua ngay

    </button>

<?php endif; ?>

</div>

                <div class="weight-note mt-4">
                    <i class="fas fa-info-circle"></i> Quy cách đóng gói: <strong><?= htmlspecialchars($product['unit']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-card">
        <div class="nav-tabs-custom">
            <button class="tab-btn active">Mô tả sản phẩm</button>
        </div>
        <div class="tab-content-panel active">
            <p><?= nl2br(htmlspecialchars($product['product_description'])) ?></p>
        </div>
    </div>

    <div class="related-card">
        <div class="section-title">Sản phẩm liên quan</div>
        <div class="row">
            <?php if (!empty($related_products)): ?>
                <?php foreach ($related_products as $item): ?>
                <div class="col-6 col-md-3 mb-3">
                    <div class="product-item">
                        <a href="<?= BASE_URL ?>index.php?page=ProductDetail&id=<?= urlencode($item['product_id']) ?>" class="product-link">
                            <div class="product-img-wrap">
                                <img src="<?= BASE_URL ?>Media/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        </a>
                        <div class="product-info">
                            <a href="<?= BASE_URL ?>index.php?page=ProductDetail&id=<?= urlencode($item['product_id']) ?>" class="product-title-link">
                                <div class="product-name-item"><?= htmlspecialchars($item['product_name']) ?></div>
                            </a>
                            <div class="product-price-item"><?= number_format($item['price'], 0, ',', '.') ?>₫ <span class="product-price-unit">/<?= htmlspecialchars($item['unit']) ?></span></div>
                            <form action="<?= BASE_URL ?>index.php?page=Cart" method="POST">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn-add-cart-sm mt-1">Thêm vào giỏ</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted text-center">Không có sản phẩm liên quan nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
</main>

<footer class="footer-custom">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 col-md-12 mb-4 mb-lg-0">
                <div style="margin-bottom: 12px;">
                    <img src="<?= BASE_URL ?>Media/logo_white.png" alt="Farm2Home" style="max-height:40px;">
                </div>
                <p class="pr-lg-4 mb-4" style="font-size: 0.95rem; line-height: 1.6; color: rgba(254,251,233,0.9);">
                    Farm2Home mang nông sản sạch, tươi ngon và an toàn đến tận tay bạn, để mỗi bữa ăn luôn trọn vẹn sự an tâm và chất lượng.
                </p>
                <div class="form-inline subscribe-form">
                    <div class="form-group w-100 flex-nowrap">
                        <input type="email" class="form-control w-100 mr-2" placeholder="Email của bạn...">
                        <button type="button" class="btn btn-subscribe flex-shrink-0">Đăng ký</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5>Liên kết</h5>
                <ul class="list-unstyled">
                    <li><a href="Trang_chu.html">Trang Chủ</a></li>
                    <li><a href="Sản phẩm.html">Sản Phẩm</a></li>
                    <li><a href="#">Về Chúng Tôi</a></li>
                    <li><a href="#">Liên Hệ</a></li>
                    <li><a href="#">Chính Sách Bảo Mật</a></li>
                    <li><a href="#">Điều Khoản Sử Dụng</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6">
                <h5>Liên hệ</h5>
                <ul class="list-unstyled mb-4">
                    <li><i class="fas fa-phone-alt"></i> 1800 6868</li>
                    <li><i class="far fa-envelope"></i> <a href="#" style="color:#fff;">contact@farm2home.vn</a></li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Nguyễn Huệ, Quận 1, TP.HCM</li>
                </ul>
                <div class="mb-2">
                    <span class="footer-badge">VietGAP</span>
                    <span class="footer-badge">GlobalGAP</span>
                </div>
                <div>
                    <span class="footer-badge">OCOP</span>
                    <span class="footer-badge">ISO 22000</span>
                </div>
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
            <div class="col-md-4 text-center text-md-right">
                <span>Thanh toán an toàn:</span>
                <span class="footer-badge mb-0 ml-2" style="font-size: 0.75rem;">MoMo</span>
                <span class="footer-badge mb-0" style="font-size: 0.75rem;">VNPay</span>
            </div>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/ProductDetail.js">

</script>

<script>
function requireLogin() {

    alert("Vui lòng đăng nhập để tiếp tục!");

    // Lưu trang hiện tại
    const currentUrl = window.location.href;

    // Chuyển sang login kèm redirect
   window.location.href =
    "<?= BASE_URL ?>index.php?page=LogIn&redirect=" + encodeURIComponent(currentUrl);
}
</script>

</body>
</html>