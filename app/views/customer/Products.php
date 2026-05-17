<?php
session_start();

$customer_id = 'CUS001'; 

// 1. KẾT NỐI DATABASE
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
mysqli_set_charset($conn, "utf8mb4");

if (mysqli_connect_errno()) {
    die("Kết nối database thất bại: " . mysqli_connect_error());
}

// 2. LẤY DANH SÁCH CATEGORY ĐỂ LÀM BỘ LỌC SIDEBAR
$cat_query = "SELECT * FROM category ORDER BY name ASC";
$cat_result = mysqli_query($conn, $cat_query);

// 3. XỬ LÝ LỌC SẢN PHẨM & TÌM KIẾM
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$search_filter = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$prod_query = "SELECT p.product_id, p.product_name, p.stock, p.price, p.unit, p.product_image, p.category_id, c.name AS category_name 
               FROM product p 
               LEFT JOIN category c ON p.category_id = c.category_id 
               WHERE 1=1";

if (!empty($category_filter)) {
    $prod_query .= " AND p.category_id = '$category_filter'";
}
if (!empty($search_filter)) {
    $prod_query .= " AND p.product_name LIKE '%$search_filter%'";
}
$prod_query .= " ORDER BY p.product_id DESC";
$prod_result = mysqli_query($conn, $prod_query);

// 4. TRUY VẤN SỐ LƯỢNG ITEM ĐỂ HIỂN THỊ BADGE NAVBAR
$cart_count_query = "SELECT SUM(ci.quantity) AS total_qty 
                     FROM cart c 
                     JOIN cartitem ci ON c.cart_id = ci.cart_id 
                     WHERE c.customer_id = '$customer_id'";
$cart_count_res = mysqli_query($conn, $cart_count_query);
$cart_count_row = mysqli_fetch_assoc($cart_count_res);
$total_cart_items = $cart_count_row['total_qty'] ? $cart_count_row['total_qty'] : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục sản phẩm - Farm2Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/Products.css">
</head>
<body>
   
    <nav class="navbar navbar-expand-lg custom-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="../Media/Logo.png" alt="Farm2Home" onerror="this.src='https://placehold.co/150x45?text=Farm2Home'">
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars" style="color: #183a1d;"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang Chủ</a></li>
                    <li class="nav-item active"><a class="nav-link" href="products.php">Sản Phẩm</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Về Chúng Tôi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Liên Hệ</a></li>
                </ul>
                
                <div class="nav-right-actions d-flex align-items-center mt-2 mt-lg-0">
                    <a href="#" class="action-icon">
                        <i class="far fa-bell"></i>
                        <span class="icon-badge">3</span>
                    </a>
                    <a href="cart.php" class="action-icon mx-3">
                        <i class="bi bi-cart3"></i>
                        <span class="icon-badge" id="cart-count"><?= $total_cart_items ?></span>
                    </a>
                    <div class="nav-divider d-none d-lg-block"></div>
                    <a href="#" class="action-icon user-avatar-icon ms-2">
                        <i class="bi bi-person-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="catalog-section" style="margin-top: 100px;">
        <div class="container">
            <div class="row">
                
                <aside class="col-12 col-lg-3 mb-4">
                    <div class="filter-sidebar p-4 shadow-sm">
                        <h5 class="filter-heading mb-3">Tìm kiếm</h5>
                        <form method="GET" action="products.php" class="mb-4">
                            <div class="input-group search-box">
                                <input type="text" name="search" class="form-control" placeholder="Tìm sản phẩm..." value="<?= htmlspecialchars($search_filter) ?>">
                                <button class="btn btn-search" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                            <?php if(!empty($category_filter)): ?>
                                <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                            <?php endif; ?>
                        </form>

                        <h5 class="filter-heading mb-3">Danh Mục Nông Sản</h5>
                        <div class="category-filter-list d-flex flex-column gap-2">
                            <a href="products.php?search=<?= urlencode($search_filter) ?>" 
                               class="category-item-link <?= empty($category_filter) ? 'active' : '' ?>">
                                Tất cả sản phẩm <i class="fas fa-chevron-right float-end mt-1"></i>
                            </a>
                            <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                            <a href="products.php?category=<?= $cat['category_id'] ?>&search=<?= urlencode($search_filter) ?>" 
                            class="category-item-link <?= ($category_filter == $cat['category_id']) ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?> <i class="fas fa-chevron-right float-end mt-1"></i>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </aside>

                <section class="col-12 col-lg-9">
                    <div class="catalog-header mb-4 d-flex justify-content-between align-items-center">
                        <h2 class="section-title mb-0 fw-bold" style="color: #183a1d;">
                            Nông Sản Tươi Sạch 
                            <span class="products-count" style="font-size: 1rem; font-weight: normal; color: #6c757d;">(<?= $prod_result ? mysqli_num_rows($prod_result) : 0 ?> sản phẩm)</span>
                        </h2>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 g-4" id="products-grid">
                        <?php if (!$prod_result || mysqli_num_rows($prod_result) == 0): ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Không tìm thấy nông sản nào phù hợp với bộ lọc hiện tại.</p>
                                <a href="products.php" class="btn btn-reset-filter btn-sm">Xóa bộ lọc</a>
                            </div>
                        <?php else: ?>
                            <?php while ($product = mysqli_fetch_assoc($prod_result)): 
                                $is_out_of_stock = ($product['stock'] <= 0);
                                $unit_display = !empty($product['unit']) ? htmlspecialchars($product['unit']) : 'kg';
                                
                                $image_path = "../Media/p" . $product['product_id'] . ".jpg";
                            ?>
                                <div class="col">
                                    <div class="product-card-item h-100 <?= $is_out_of_stock ? 'out-of-stock' : '' ?>">
                                        <div class="product-img-box position-relative">
                                            <img src="<?= $image_path ?>" class="img-fluid product-thumb" alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.src='https://placehold.co/300x300?text=Farm2Home'">
                                            <?php if ($is_out_of_stock): ?>
                                                <div class="oos-overlay">Hết hàng</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info-box p-3 d-flex flex-column flex-grow-1">
                                            <h5 class="product-item-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                                            
                                            <p class="product-stock-lbl">
                                                <i class="bi bi-box-seam"></i> <?= $product['stock'] ?>
                                            </p>
                                            
                                            <div class="product-price-action d-flex justify-content-between align-items-center mt-auto">
                                                <div>
                                                    <span class="product-price-txt"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                                                    <span class="text-muted small">/<?= $unit_display ?></span>
                                                </div>
                                                <button class="btn btn-add-to-cart-ajax" 
                                                        data-product-id="<?= $product['product_id'] ?>"
                                                        <?= $is_out_of_stock ? 'disabled' : '' ?>>
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <footer class="footer-custom mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4 mb-4 mb-md-0">
                    <h5 class="fw-bold mb-3">Farm2Home</h5>
                    <p class="footer-desc">Mang nông sản sạch, tươi ngon và an toàn đến tận tay bạn.</p>
                </div>
                <div class="col-6 col-md-3 col-lg-4">
                    <h5>Danh mục</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.php">Trang Chủ</a></li>
                        <li><a href="products.php">Sản Phẩm</a></li>
                        <li><a href="#">Về Chúng Tôi</a></li>
                        <li><a href="#">Liên Hệ</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-4">
                    <h5>Liên hệ</h5>
                    <ul class="list-unstyled footer-contact">
                        <li><i class="fas fa-phone-alt"></i> 1800 6868</li>
                        <li><i class="far fa-envelope"></i> support@farm2home.vn</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Nguyễn Huệ, Quận 1, TP.HCM</li>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="row align-items-center footer-bottom">
                <div class="col-12 col-md-6 text-center text-md-start">
                    &copy; 2026 Farm2Home. Tất cả quyền được bảo lưu.
                </div>
                <div class="col-12 col-md-6 text-center text-md-end item-small">
                    <span>Thanh toán an toàn qua MoMo / VNPAY</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/Products.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>