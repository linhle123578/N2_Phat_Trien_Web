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

// 3. XỬ LÝ LỌC SẢN PHẨM, TÌM KIẾM & SẮP XẾP
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$search_filter = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_filter = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'latest';

$order_by = "p.product_id DESC"; 
if ($sort_filter === 'price_asc') {
    $order_by = "p.price ASC";
} elseif ($sort_filter === 'price_desc') {
    $order_by = "p.price DESC";
}

// --- LOGIC PHÂN TRANG (PAGINATION) ---
$limit = 9; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) AS total FROM product WHERE 1=1";
if (!empty($category_filter)) {
    $count_query .= " AND category_id = '$category_filter'";
}
if (!empty($search_filter)) {
    $count_query .= " AND product_name LIKE '%$search_filter%'";
}
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Truy vấn sản phẩm thực tế
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
$prod_query .= " ORDER BY $order_by LIMIT $limit OFFSET $offset";
$prod_result = mysqli_query($conn, $prod_query);
$current_page_products = $prod_result ? mysqli_num_rows($prod_result) : 0;

$start_product = ($total_products == 0) ? 0 : $offset + 1;
$end_product = min($offset + $limit, $total_products);

// 4. TRUY VẤN GIỎ HÀNG
$cart_count_query = "SELECT SUM(ci.quantity) AS total_qty 
                     FROM cart c 
                     JOIN cartitem ci ON c.cart_id = ci.cart_id 
                     WHERE c.customer_id = '$customer_id'";
$cart_count_res = mysqli_query($conn, $cart_count_query);
$cart_count_row = mysqli_fetch_assoc($cart_count_res);
$total_cart_items = $cart_count_row['total_qty'] ? $cart_count_row['total_qty'] : 0;

function getPageUrl($p, $cat, $search, $sort) {
    $params = [];
    if (!empty($cat)) $params['category'] = $cat;
    if (!empty($search)) $params['search'] = $search;
    if (!empty($sort)) $params['sort'] = $sort;
    $params['page'] = $p;
    return 'products.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - Farm2Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/assets/css/Products.css">
</head>
<body>
   
    <nav class="navbar navbar-expand-lg custom-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="../Media/Logo.png" alt="Farm2Home" onerror="this.src='https://placehold.co/150x45?text=Farm2Home'">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav1">
                <span class="navbar-toggler-icon"><i class="fas fa-bars" style="color: #183a1d;"></i></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav1">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang Chủ</a></li>
                    <li class="nav-item active"><a class="nav-link" href="products.php">Sản Phẩm</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Liên Hệ</a></li>
                </ul>
                <div class="nav-right-actions">
                    <a href="#" class="action-icon"><i class="far fa-bell"></i><span class="icon-badge">3</span></a>
                    <a href="cart.php" class="action-icon"><i class="fas fa-shopping-cart"></i><span class="icon-badge" id="cart-count"><?= $total_cart_items ?></span></a>
                    <div class="nav-divider"></div>
                    <a href="#" class="btn-login">Đăng Nhập</a>
                    <button class="btn btn-register">Đăng Ký</button>
                </div>
            </div>
        </div>
    </nav>

    <section class="search-banner py-5">
        <div class="container text-center">
            <h2 class="text-white mb-4">Tất cả sản phẩm nông sản sạch</h2>
            <form method="GET" action="products.php" class="search-box mx-auto position-relative" style="max-width: 600px;">
                <input type="text" name="search" class="form-control rounded-pill py-3 ps-5" placeholder="Tìm kiếm rau củ, trái cây, đặc sản..." value="<?= htmlspecialchars($search_filter) ?>">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <?php if(!empty($category_filter)): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                <?php endif; ?>
                <?php if(!empty($sort_filter)): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_filter) ?>">
                <?php endif; ?>
            </form>
        </div>
    </section>

    <main class="container my-5">
        <div class="row">
            <aside class="col-lg-3">
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">Danh mục nông sản</h6>
                    <div class="list-group sidebar-list">
                        <a href="products.php?search=<?= urlencode($search_filter) ?>&sort=<?= urlencode($sort_filter) ?>" class="list-group-item <?= empty($category_filter) ? 'active' : '' ?>">
                            Tất cả sản phẩm
                        </a>
                        <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                            <a href="products.php?category=<?= $cat['category_id'] ?>&search=<?= urlencode($search_filter) ?>&sort=<?= urlencode($sort_filter) ?>" 
                               class="list-group-item <?= ($category_filter == $cat['category_id']) ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted">
                        Hiển thị: <strong><?= $start_product ?> - <?= $end_product ?></strong> trong số <strong><?= $total_products ?></strong> sản phẩm
                    </span>
                    <select class="form-select w-auto rounded-pill" id="sort-select">
                        <option value="latest" <?= $sort_filter === 'latest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="price_asc" <?= $sort_filter === 'price_asc' ? 'selected' : '' ?>>Giá từ thấp đến cao</option>
                        <option value="price_desc" <?= $sort_filter === 'price_desc' ? 'selected' : '' ?>>Giá từ cao đến thấp</option>
                    </select>
                </div>

                <div class="row g-4" id="product-container">
                    <?php if ($total_products == 0): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Không tìm thấy sản phẩm nông sản nào phù hợp.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        while ($product = mysqli_fetch_assoc($prod_result)): 
                            $image_path = "../Media/p" . $product['product_id'] . ".jpg";
                            $is_out_of_stock = ($product['stock'] <= 0);
                            $unit_display = !empty($product['unit']) ? htmlspecialchars($product['unit']) : 'kg';
                        ?>
                            <div class="col-xl-4 col-md-4 col-6">
                                <div class="card h-100 product-card border-0 shadow-sm <?= $is_out_of_stock ? 'opacity-75' : '' ?>">
                                    <div class="position-relative">
                                        <?php if ($is_out_of_stock): ?>
                                            <span class="badge bg-secondary position-absolute top-0 end-0 m-2">Hết hàng</span>
                                        <?php endif; ?>
                                        <img src="<?= $image_path ?>" class="card-img-top p-3" alt="<?= htmlspecialchars($product['product_name']) ?>" onerror="this.src='https://placehold.co/300x300?text=Farm2Home'">
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h6>
                                        
                                        <p class="product-stock-lbl">
                                            <i class="bi bi-box-seam"></i> <?= $product['stock'] ?>
                                        </p>
                                        
                                        <div class="d-flex align-items-center gap-1 mb-3 mt-auto">
                                            <span class="text-orange"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                                            <span class="text-muted small">/ <?= $unit_display ?></span>
                                        </div>
                                        
                                        <button class="btn btn-outline-dark w-100 btn-add-cart" 
                                                data-product-id="<?= $product['product_id'] ?>"
                                                <?= $is_out_of_stock ? 'disabled' : '' ?>>
                                            <i class="bi bi-cart-plus me-2"></i><?= $is_out_of_stock ? 'Tạm hết hàng' : 'Thêm vào giỏ' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="d-flex align-items-center justify-content-center py-5">
                        <nav aria-label="Page navigation">
                            <ul class="pagination custom-pagination-wrapper mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= getPageUrl($page - 1, $category_filter, $search_filter, $sort_filter) ?>"><i class="fas fa-chevron-left"></i></a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= getPageUrl($i, $category_filter, $search_filter, $sort_filter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= getPageUrl($page + 1, $category_filter, $search_filter, $sort_filter) ?>"><i class="fas fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 col-lg-5 mb-4 mb-lg-0">
                    <img src="../Media/Logo.png" alt="Farm2Home" class="footer-logo mb-3">
                    <p class="footer-desc">Farm2Home mang nông sản sạch, tươi ngon và an toàn đến tận tay bạn.</p>
                </div>
                <div class="col-6 col-md-3 col-lg-3">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Trang Chủ</a></li>
                        <li><a href="products.php">Sản Phẩm</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-4">
                    <h5>Liên hệ</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone-alt"></i> 1800 6868</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 Nguyễn Huệ, Quận 1, TP.HCM</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const currentCategory = '<?= $category_filter ?>';
        const currentSearch = '<?= rawurlencode($search_filter) ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/Products.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>