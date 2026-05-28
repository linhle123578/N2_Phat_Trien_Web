<?php
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

$cat_result = mysqli_query($conn, "SELECT category_id, name FROM category ORDER BY name ASC");
$categories = [];
while ($c = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $c;
}

$prod_query = "
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.stock,
        p.unit,
        p.product_image,
        p.category_id,
        c.name AS category_name,
        COALESCE(SUM(oi.quantity), 0) AS sold
    FROM product p
    LEFT JOIN category c ON p.category_id = c.category_id
    LEFT JOIN orderitem oi ON p.product_id = oi.product_id
    GROUP BY p.product_id, c.name
    ORDER BY p.product_id ASC
";
$prod_result = mysqli_query($conn, $prod_query);
$products = [];
while ($p = mysqli_fetch_assoc($prod_result)) {
    $products[] = $p;
}
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm - Farm2Home Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/assets/css/ProductAdmin.css">
</head>
<body>

<div class="admin-wrapper d-flex">

    <!-- SIDEBAR -->
    <aside class="sidebar text-white">
        <?php include __DIR__ . '/../layouts/adminsidebar.php'; ?>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content flex-grow-1 d-flex flex-column">

        <!-- HEADER -->
        <header class="admin-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="toggleSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold">Quản lý sản phẩm</h5>
                    <nav aria-label="breadcrumb" class="mt-1">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="#">Trang chủ</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Sản phẩm</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <i class="bi bi-bell fs-5 text-muted"></i>
                    <span class="notification-dot"></span>
                </div>
                <img src="../../../Media/user_1.jpg" alt="Admin" class="admin-avatar"
                     onerror="this.src='https://placehold.co/36x36?text=A'">
            </div>
        </header>

        <!-- CONTENT -->
        <div class="p-4 flex-grow-1">

            <!-- ALERT COUNTERS -->
            <div class="alert-counters-row">
                <div class="alert-card alert-card-warning">
                    <div class="alert-icon alert-icon-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="alert-card-number" id="countLowStock">0</div>
                        <div class="alert-card-label">Sản phẩm sắp hết hàng</div>
                    </div>
                </div>
                <div class="alert-card alert-card-danger">
                    <div class="alert-icon alert-icon-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div>
                        <div class="alert-card-number" id="countOutOfStock">0</div>
                        <div class="alert-card-label">Sản phẩm đã hết hàng</div>
                    </div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar-wrapper">
                <div class="input-group" style="max-width:300px;">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0"
                           id="searchInput" placeholder="Tìm kiếm sản phẩm...">
                </div>

                <select class="form-select w-auto" id="filterCategory" style="min-width:160px;">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="btn btn-add-product ms-auto" id="btnAddProduct">
                    <i class="bi bi-plus-lg me-2"></i>Thêm sản phẩm mới
                </button>
            </div>

            <!-- TABLE CARD -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead class="table-header-custom">
                                <tr>
                                    <th class="ps-4" style="width:80px;">Mã SP</th>
                                    <th>Sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th class="text-center">Giá bán</th>
                                    <th class="text-center" style="width:130px;">Tồn kho</th>
                                    <th class="text-center" style="width:90px;">Đã bán</th>
                                    <th class="text-center pe-3" style="width:140px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody"></tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <div class="p-3 border-top d-flex flex-wrap justify-content-between align-items-center bg-white"
                         style="border-radius:0 0 16px 16px;">
                        <span class="text-muted small" id="paginationInfo"></span>
                        <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                    </div>
                </div>
            </div>

        </div><!-- /p-4 -->
    </div><!-- /main-content -->
</div><!-- /admin-wrapper -->


<!-- ===== MODAL: ADD / EDIT ===== -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="productModalLabel">Thêm sản phẩm mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-0">
                <input type="hidden" id="editProductId">

                <div class="row g-3">
                    <!-- LEFT COLUMN -->
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted small">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editName"
                                   placeholder="Nhập tên sản phẩm">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted small">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCategory">
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col">
                                <label class="form-label fw-medium text-muted small">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editPrice" placeholder="15000" min="0">
                            </div>
                            <div class="col">
                                <label class="form-label fw-medium text-muted small">Số lượng kho</label>
                                <input type="number" class="form-control" id="editStock" placeholder="100" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium text-muted small">Đơn vị tính</label>
                            <select class="form-select" id="editUnit">
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="chai">chai</option>
                                <option value="hũ">hũ</option>
                                <option value="bó">bó</option>
                                <option value="trái">trái</option>
                                <option value="túi">túi</option>
                                <option value="hộp">hộp</option>
                                <option value="gói">gói</option>
                            </select>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN — IMAGE UPLOAD -->
                    <div class="col-md-5">
                        <label class="form-label fw-medium text-muted small">Ảnh sản phẩm</label>
                        <div class="image-upload-area" id="editImageArea">
                            <input type="file" id="editImageFile" accept="image/*">
                            <img src="" alt="Preview" class="image-upload-preview" id="editImagePreview">
                            <div class="image-upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                            <div class="image-upload-text">Kéo thả hoặc nhấp để chọn ảnh</div>
                            <div style="font-size:.72rem;color:#D1D5DB;margin-top:4px;">PNG, JPG, WEBP tối đa 5MB</div>
                        </div>
                        <!-- Fallback: type filename manually -->
                        <div class="mt-2">
                            <input type="text" class="form-control form-control-sm" id="editImageName"
                                   placeholder="Hoặc nhập tên file ảnh, VD: raumuong.jpg">
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer border-0 px-4 pt-2 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-save rounded-pill px-4" id="saveProductBtn">
                    <i class="bi bi-check-lg me-1"></i>Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ===== MODAL: DELETE ===== -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow text-center">
            <div class="modal-body py-5 px-4">
                <div class="mb-3" style="font-size:3rem;color:#EF4444;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h5 class="fw-bold mb-2">Xác nhận xóa</h5>
                <p class="text-muted mb-1" style="font-size:.9rem;">Bạn có chắc muốn xóa sản phẩm</p>
                <p class="fw-semibold mb-4" id="deleteProductName" style="color:#D97706;">---</p>
                <input type="hidden" id="deleteProductId">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4"
                            id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i>Đồng ý xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const PRODUCTS_FROM_DB   = <?= json_encode($products,    JSON_UNESCAPED_UNICODE) ?>;
    const CATEGORIES_FROM_DB = <?= json_encode($categories,  JSON_UNESCAPED_UNICODE) ?>;
    const MEDIA_PATH = '../../../Media/';
</script>
<script src="../../../public/assets/js/ProductAdmin.js"></script>
</body>
</html>