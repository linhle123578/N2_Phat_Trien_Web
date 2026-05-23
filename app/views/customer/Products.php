<?php
/** @var string $category_filter */
/** @var string $search_filter */
/** @var string $sort_filter */
/** @var int $page */
/** @var int $total_pages */
/** @var int $total_products */
/** @var int $start_product */
/** @var int $end_product */
/** @var int $total_cart_items */
/** @var mysqli_result $cat_result */
/** @var mysqli_result $prod_result */
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
                <img src="Media/Logo.png" alt="Farm2Home" onerror="this.src='https://placehold.co/150x45?text=Farm2Home'">
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
                    <?php if(isset($_SESSION['customer_id'])): ?>
                        <span class="text-dark fw-bold me-2">Hi, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'User') ?></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill">Đăng Xuất</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-login text-decoration-none me-3">Đăng Nhập</a>
                        <a href="signup.php" class="btn btn-register btn-success rounded-pill px-3" style="background-color: #183a1d; border-color: #183a1d;">Đăng Ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="search-banner py-5">
        <div class="container text-center">
            <h2 class="text-white mb-4">Tất cả sản phẩm nông sản sạch</h2>
            <form method="GET" action="products.php" class="search-box mx-auto position-relative" style="max-width: 600px;">                <input type="text" name="search" class="form-control rounded-pill py-3 ps-5" placeholder="Tìm kiếm rau củ, trái cây, đặc sản..." value="<?= htmlspecialchars($search_filter) ?>">
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
                            $is_out_of_stock = ($product['stock'] <= 0);
                            $unit_display = !empty($product['unit']) ? htmlspecialchars($product['unit']) : 'kg';
                        ?>
                            <div class="col-xl-4 col-md-4 col-6">
                                <div class="card h-100 product-card border-0 shadow-sm <?= $is_out_of_stock ? 'opacity-75' : '' ?>">
                                    <div class="position-relative">
                                        <?php if ($is_out_of_stock): ?>
                                            <span class="badge bg-secondary position-absolute top-0 end-0 m-2">Hết hàng</span>
                                        <?php endif; ?>
                                        <a href="../../../app/views/customer/ProductDetail.php?id=<?= $product['product_id'] ?>" class="product-link-wrapper">
                                            <div class="product-img-wrapper" style="width: 100%; aspect-ratio: 1 / 1; overflow: hidden; background-color: #f8f9fa;">
                                                <img src="../../../Media/<?= htmlspecialchars($product['product_image']) ?>" 
                                                    alt="<?= htmlspecialchars($product['product_name']) ?>" 
                                                    class="img-fluid product-thumb" 
                                                    style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                                                    onerror="this.src='https://placehold.co/300x300?text=Farm2Home'">
                                            </div>                                    
                                        </a>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <a href="../../../app/views/customer/ProductDetail.php?id=<?= $product['product_id'] ?>" class="text-decoration-none">
                                            <h6 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h6>
                                        </a>
                                        
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
                                <a class="page-link" href="<?= viewGetPageUrl($page - 1, $category_filter, $search_filter, $sort_filter) ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= viewGetPageUrl($i, $category_filter, $search_filter, $sort_filter) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= viewGetPageUrl($page + 1, $category_filter, $search_filter, $sort_filter) ?>"><i class="fas fa-chevron-right"></i></a>
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
                    <img src="Media/Logo.png" alt="Farm2Home" class="footer-logo mb-3">
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

<?php 
// Hàm sinh đường dẫn phân trang giữ nguyên bộ lọc
function viewGetPageUrl($p, $cat, $search, $sort) {
    $params = [];
    if (!empty($cat)) $params['category'] = $cat;
    if (!empty($search)) $params['search'] = $search;
    if (!empty($sort)) $params['sort'] = $sort;
    $params['page'] = $p;
    
    // Đảm bảo trả về file products.php ở thư mục gốc chạy qua Controller
    return 'products.php?' . http_build_query($params);
}
?>