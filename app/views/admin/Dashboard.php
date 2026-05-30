<?php
$start_month = $start_month ?? date('Y-m', strtotime('-5 month'));
$end_month = $end_month ?? date('Y-m');

$overview = $overview ?? [
    'revenue_current' => 0, 'revenue_growth' => 0,
    'orders_total' => 0, 'orders_shipping' => 0, 'orders_completed' => 0,
    'customers_total' => 0, 'customers_new' => 0
];

$analytics = $analytics ?? [
    'trends' => ['months' => [date('m/Y')], 'revenues' => [0], 'order_counts' => [0]],
    'categories' => ['names' => ['Chưa có dữ liệu'], 'percentages' => [100]],
    'top_products' => []
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Farm2Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="stylesheet" href="../../../public/assets/css/AdminSidebar.css">
    <link rel="stylesheet" href="../../../public/assets/css/Dashboard.css">
</head>
<body>

    <?php require_once __DIR__ . '/../layouts/adminsidebar.php'; ?>

    <div class="admin-main-content">
        
        <header class="admin-header">
            <div class="header-left">
                <h5 class="mb-0 font-weight-bold text-dark">Hệ Thống Báo Cáo Kinh Doanh</h5>
            </div>
            <div class="header-right d-flex align-items-center">
                <div class="dropdown">
                    <div class="admin-profile dropdown-toggle d-flex align-items-center" id="profileMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;">
                        <span class="mr-2 font-weight-bold text-muted small">Xin chào, Admin</span>
                        <i class="fas fa-user-circle fa-2x text-primary"></i>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2" aria-labelledby="profileMenu">
                        <a class="dropdown-item py-2" href="#"><i class="fas fa-user-cog mr-2 text-muted"></i> Hồ sơ cá nhân</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item py-2 text-danger" href="#"><i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="admin-dashboard-body">
            <div class="container-fluid p-0">
                
                <div class="row mb-4 align-items-center">
                    <div class="col-sm-6">
                        <h1 class="page-title mb-0"><i class="fas fa-chart-line mr-2"></i>Tổng Quan Kinh Doanh</h1>
                        <p class="text-muted mb-0 small">Dữ liệu phân tích báo cáo thời gian thực của hệ thống.</p>
                    </div>
                    <div class="col-sm-6 text-sm-right mt-3 mt-sm-0">
                        <span class="realtime-badge"><span class="dot"></span>Cập nhật trực tiếp</span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="overview-card shadow-sm border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="card-label">Doanh Thu</span>
                                    <h2 class="card-value mt-1"><?= number_format($overview['revenue_current'], 0, ',', '.') ?>đ</h2>
                                    <div class="card-indicator mt-2">
                                        <?php if ($overview['revenue_growth'] >= 0): ?>
                                            <span class="trend-up"><i class="fas fa-caret-up mr-1"></i>+<?= round($overview['revenue_growth'], 1) ?>%</span>
                                        <?php else: ?>
                                            <span class="trend-down"><i class="fas fa-caret-down mr-1"></i><?= round($overview['revenue_growth'], 1) ?>%</span>
                                        <?php endif; ?>
                                        <span class="text-muted ml-1 small">so với tháng trước</span>
                                    </div>
                                </div>
                                <div class="icon-shape bg-revenue"><i class="fas fa-dollar-sign"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="overview-card shadow-sm border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="card-label">Đơn Hàng</span>
                                    <h2 class="card-value mt-1"><?= number_format($overview['orders_total'], 0, ',', '.') ?> đơn</h2>
                                    <div class="card-indicator mt-2">
                                        <span class="badge badge-shipping font-weight-bold mr-1"><?= $overview['orders_shipping'] ?> đang giao</span>
                                        <span class="badge badge-completed font-weight-bold"><?= $overview['orders_completed'] ?> hoàn tất</span>
                                    </div>
                                </div>
                                <div class="icon-shape bg-order"><i class="fas fa-shopping-bag"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-12 mb-4">
                        <div class="overview-card shadow-sm border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="card-label">Khách Hàng</span>
                                    <h2 class="card-value mt-1"><?= number_format($overview['customers_total'], 0, ',', '.') ?> người</h2>
                                    <div class="card-indicator mt-2">
                                        <span class="trend-new-user"><i class="fas fa-user-plus mr-1"></i>+<?= $overview['customers_new'] ?> mới</span>
                                        <span class="text-muted ml-1 small">tháng này</span>
                                    </div>
                                </div>
                                <div class="icon-shape bg-customer"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="filter-panel card border-0 shadow-sm mb-4">
                    <div class="card-body py-3">
                        <form method="GET" action="" class="form-row align-items-end">
                            <div class="col-lg-5 col-md-4 mb-3 mb-md-0">
                                <label class="filter-label"><i class="far fa-calendar-alt mr-1"></i>Từ tháng / năm</label>
                                <input type="month" class="form-control filter-input" name="start_month" value="<?= htmlspecialchars($start_month) ?>" required>
                            </div>
                            <div class="col-lg-5 col-md-4 mb-3 mb-md-0">
                                <label class="filter-label"><i class="far fa-calendar-alt mr-1"></i>Đến tháng / năm</label>
                                <input type="month" class="form-control filter-input" name="end_month" value="<?= htmlspecialchars($end_month) ?>" required>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <button type="submit" class="btn btn-search-filter btn-block"><i class="fas fa-filter mr-2"></i>Lọc dữ liệu</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="chart-container-card card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="chart-section-title mb-4"><i class="fas fa-chart-line text-success mr-2"></i>Xu Hướng Kinh Doanh (Doanh thu & Đơn hàng)</h5>
                                <div class="chart-viewport">
                                    <canvas id="trendChartElement"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="chart-container-card card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="chart-section-title mb-4"><i class="fas fa-chart-pie text-orange mr-2"></i>Doanh Thu Theo Danh Mục</h5>
                                <div class="chart-viewport-pie">
                                    <canvas id="pieChartElement"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-12">
                        <div class="table-container-card card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <h5 class="chart-section-title mb-0"><i class="fas fa-crown text-warning mr-2"></i>Top 3 Sản Phẩm Bán Chạy Nhất</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-borderless table-align-middle mb-0">
                                        <thead>
                                            <tr class="table-header-row text-muted text-uppercase small">
                                                <th style="width: 50%">Chi tiết sản phẩm</th>
                                                <th class="text-center" style="width: 25%">Số lượng đã bán</th>
                                                <th class="text-right" style="width: 25%">Tổng doanh thu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($analytics['top_products'])): ?>
                                                <?php foreach ($analytics['top_products'] as $index => $product): ?>
                                                    <tr class="product-data-row">
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="product-rank-badge mr-3 rank-<?= $index + 1 ?>"><?= $index + 1 ?></div>
                                                                <img src="../../../Media/<?= htmlspecialchars($product['product_image']) ?>" class="product-thumb mr-3" onerror="this.src='https://placehold.co/100x100?text=Farm2Home'">
                                                                <div class="product-info-text">
                                                                    <h6 class="p-name mb-0"><?= htmlspecialchars($product['product_name']) ?></h6>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="sales-count-pill"><?= number_format($product['total_sold'], 0, ',', '.') ?> sản phẩm</span>
                                                        </td>
                                                        <td class="text-right font-weight-bold text-revenue-color">
                                                            <?= number_format($product['total_revenue'], 0, ',', '.') ?>đ
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">Không tìm thấy dữ liệu bán hàng trong khoảng thời gian được chọn.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Đồng bộ mảng dữ liệu từ PHP sang Javascript cho file js xử lý
        const trendLabels = <?= json_encode($analytics['trends']['months']) ?>;
        const trendRevenues = <?= json_encode($analytics['trends']['revenues']) ?>;
        const trendOrders = <?= json_encode($analytics['trends']['order_counts']) ?>;

        const pieLabels = <?= json_encode($analytics['categories']['names']) ?>;
        const pieData = <?= json_encode($analytics['categories']['percentages']) ?>;
    </script>
    <script src="../../../public/assets/js/Dashboard.js"></script>
</body>
</html>