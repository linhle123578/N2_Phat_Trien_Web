<?php
ob_start();
include_once __DIR__ . '/../layouts/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
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

if (!$conn) {
    die("Lỗi kết nối Database: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
$current_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($current_id)) {
    // Tự động lấy sản phẩm đầu tiên trong database để hiển thị
    $query = "SELECT * FROM product LIMIT 1";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);
} else {
    // Lấy đúng sản phẩm có ID được chọn
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

// Lấy các sản phẩm liên quan
$query_related = "SELECT * FROM product WHERE category_id = ? AND product_id != ? LIMIT 4";
$stmt_related = mysqli_prepare($conn, $query_related);
mysqli_stmt_bind_param($stmt_related, "ss", $product['category_id'], $product['product_id']);
mysqli_stmt_execute($stmt_related);
$result_related = mysqli_stmt_get_result($stmt_related);

$related_products = [];
while ($row = mysqli_fetch_assoc($result_related)) {
    $related_products[] = $row;
}

$header_output = ob_get_clean();
$extra_head = '
<title>' . htmlspecialchars($product['product_name']) . ' - Farm2Home</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="../../../public/assets/css/ProductDetail.css" rel="stylesheet"/>
';
$header_output = str_replace('</head>', $extra_head . '</head>', $header_output);
echo $header_output;
?>

<div class="breadcrumb-section">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../../app/views/customer/TrangChu.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="../../../app/views/customer/Products.php">Sản phẩm</a></li>
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
                    <img src="../../../Media/<?= htmlspecialchars($product['product_image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" id="mainImgTag" style="width:100%;height:100%;object-fit:cover;">
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

    <form action="../../../app/controllers/customer/CartController.php"
          method="POST"
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
            <button class="tab-btn active" style="cursor: default;">Mô tả sản phẩm</button>
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
                        <a href="ProductDetail.php?id=<?= urlencode($item['product_id']) ?>" class="product-link">
                            <div class="product-img-wrap">
                                <img src="../../../Media/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        </a>
                        <div class="product-info">
                            <a href="ProductDetail.php?id=<?= urlencode($item['product_id']) ?>" class="product-title-link">
                                <div class="product-name-item"><?= htmlspecialchars($item['product_name']) ?></div>
                            </a>
                            <div class="product-price-item"><?= number_format($item['price'], 0, ',', '.') ?>₫ <span class="product-price-unit">/<?= htmlspecialchars($item['unit']) ?></span></div>
                            <form action="../../../app/controllers/customer/CartController.php" method="POST">
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../public/assets/js/ProductDetail.js"></script>
<script>
function requireLogin() {

    alert("Vui lòng đăng nhập để tiếp tục!");

    // Lưu trang hiện tại
    const currentUrl = window.location.href;

    // Chuyển sang login kèm redirect
    window.location.href =
        "../../../app/views/customer/LogIn.php?redirect="
        + encodeURIComponent(currentUrl);
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
