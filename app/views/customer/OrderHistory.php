<?php
ob_start();
include __DIR__ . '/../layouts/loginheader.php';
if (!isset($tab)) {
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
    mysqli_set_charset($conn, "utf8mb4");

    require_once __DIR__ . '/../../controllers/customer/OrderHistoryController.php';
    $controller = new OrderHistoryController($conn);

    if (isset($_GET['action']) && $_GET['action'] === 'rebuy') {
        $controller->rebuy();
    } else {
        $controller->index();
    }

    mysqli_close($conn);
    exit;
}

use function OrderHistoryController as OH;

// Alias helpers
$e     = fn($s) => OrderHistoryController::e((string)$s);
$label = fn($s) => OrderHistoryController::statusLabel($s);
$cls   = fn($s) => OrderHistoryController::statusClass($s);
$icon  = fn($s) => OrderHistoryController::statusIcon($s);
$price = fn($n) => OrderHistoryController::formatPrice((float)$n);

$BASE_URL = '/n2_phat_trien_web';

// ── Header ─────────────────────────────────────────────
ob_start();
include '../../../app/views/layouts/loginheader.php';
$header_output = ob_get_clean();

$extra_head = '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/OrderHistory.css">
';
$header_output = str_replace('</head>', $extra_head . '</head>', $header_output);
echo $header_output;
?>

<div class="container" style="padding-top:80px;">

    <nav class="profile-breadcrumb">
        <a href="../../../index.php">Trang chủ</a>
        <span class="sep">›</span>
        <span class="current">Lịch sử đơn hàng</span>
    </nav>

    <div class="profile-layout">

        <!-- ── SIDEBAR ──────────────────────────────── -->
        <aside class="profile-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-title">MENU TÀI KHOẢN</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="../../../app/views/customer/ProfileCustomer.php">
                            <i class="bi bi-person-circle"></i>
                            Thông tin cá nhân
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class="bi bi-bag-check"></i>
                            Lịch sử đơn hàng
                            <?php if ($counts['all'] > 0): ?>
                                <span class="sidebar-badge"><?= $counts['all'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="sidebar-divider"></div>
                <div class="sidebar-logout">
                    <a href="#" id="btnLogout">
                        <i class="bi bi-box-arrow-right"></i>
                        Đăng xuất
                    </a>
                </div>
            </div>
        </aside>

        <!-- ── MAIN ─────────────────────────────────── -->
        <div class="oh-main">

            <div class="oh-page-header">
                <div class="oh-page-title">
                    <div class="sec-icon"><i class="bi bi-bag-check-fill"></i></div>
                    <h3>Lịch sử đơn hàng</h3>
                </div>
            </div>

            <!-- Tab filter -->
            <div class="oh-tabs">
                <?php
                $tab_labels = [
                    'all'       => 'Tất cả',
                    'pending'   => 'Chờ xác nhận',
                    'confirmed' => 'Đã xác nhận',
                    'shipping'  => 'Đang giao',
                    'delivered' => 'Đã giao',
                    'completed' => 'Hoàn thành',
                    'cancelled' => 'Đã huỷ',
                ];
                foreach ($tab_labels as $key => $lbl):
                    $active = $tab === $key ? 'active' : '';
                    $cnt = $counts[$key] ?? 0;
                ?>
                <a href="?tab=<?= $key ?>" class="oh-tab <?= $active ?>">
                    <?= $lbl ?>
                    <?php if ($cnt > 0): ?>
                        <span class="oh-tab-badge"><?= $cnt ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Order list -->
            <?php if (empty($orders)): ?>
                <div class="oh-empty">
                    <i class="bi bi-bag-x"></i>
                    <p>Không có đơn hàng nào.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order):
                    $items  = $order_items[$order['order_id']] ?? [];
                    $status = $order['order_status'] ?? '';
                    $oid    = $order['order_id'];

                    // Đơn đã giao: kiểm tra còn trong 3 ngày không
                    $can_return    = $return_eligible[$oid] ?? false;
                    $is_returned   = $has_returned[$oid] ?? false;
                    // Tính số giờ còn lại để hiển thị countdown (nếu đang eligible)
                    $hours_left    = null;
                    if (($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành') && !empty($order['created_at'])) {
                        $delivered_ts = strtotime($order['created_at']);
                        $deadline_ts  = $delivered_ts + 3 * 86400;
                        $diff_sec     = $deadline_ts - time();
                        if ($diff_sec > 0) {
                            $hours_left = ceil($diff_sec / 3600);
                        }
                    }
                ?>
                <div class="oh-order-card <?= ($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành') && $can_return ? 'returnable' : '' ?>">

                    <!-- Order header -->
                    <div class="oh-order-head">
                        <div class="oh-order-id"><?= $e($oid) ?></div>
                        <span class="oh-status-badge <?= $cls($status) ?>">
                            <i class="bi <?= $icon($status) ?> me-1"></i>
                            <?= $label($status) ?>
                        </span>
                        <div class="oh-order-date">
                            <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                        </div>
                    </div>

                    <?php if (($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành') && $can_return && $hours_left !== null): ?>
                    <!-- Return deadline banner -->
                    <div class="oh-return-banner">
                        <i class="bi bi-clock-history me-2"></i>
                        Còn <strong><?= $hours_left ?> giờ</strong> để yêu cầu đổi/trả hàng
                        <span class="oh-return-deadline-bar">
                            <span class="oh-return-deadline-fill"
                                  style="width:<?= min(100, round(($hours_left / 72) * 100)) ?>%"></span>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Items -->
                    <div class="oh-items">
                        <?php foreach ($items as $item):
                            $img_src = '../../../Media/' . $e($item['product_image'] ?? 'default.jpg');
                        ?>
                        <div class="oh-item">
                            <img src="<?= $img_src ?>"
                                 alt="<?= $e($item['product_name'] ?? '') ?>"
                                 onerror="this.src='../../../Media/default.jpg'">
                            <div class="oh-item-info">
                                <div class="oh-item-name"><?= $e($item['product_name'] ?? '') ?></div>
                                <div class="oh-item-qty">x<?= (int)$item['quantity'] ?></div>
                            </div>
                            <div class="oh-item-price">
                                <?= $price((float)$item['price'] * (int)$item['quantity']) ?>
                            </div>
                            <?php if ($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành'): ?>
                            <a href="../../../app/views/customer/ProductDetail.php?id=<?= $e($item['product_id']) ?>"
                               class="btn-review">
                                <i class="bi bi-star me-1"></i>Đánh giá
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order footer -->
                    <div class="oh-order-foot">
                        <div class="oh-payment-info">
                            <?php if (!empty($order['payment_method'])): ?>
                                <span><?= $e($order['payment_method']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="oh-foot-right">
                            <div class="oh-total">
                                Tổng tiền:
                                <strong><?= $price((float)($order['total_amount'] ?? 0)) ?></strong>
                            </div>
                            <div class="oh-actions">

                                <!-- NÚT TRẢ HÀNG: chỉ hiện nếu đã giao trong vòng 3 ngày -->
                                <?php if (($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành') && $can_return): ?>
                                <a href="ReturnRequest.php?order_id=<?= $e($oid) ?>"
                                   class="btn-oh btn-oh-return">
                                    <i class="bi bi-arrow-return-left me-1"></i>Trả hàng
                                </a>
                                <?php elseif (($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành') && $is_returned): ?>
                                <!-- Đã có yêu cầu trả hàng -->
                                <a href="ReturnRequest.php?order_id=<?= $e($oid) ?>"
                                   class="btn-oh" style="background-color: #ff9800; color: white; border-color: #ff9800;">
                                    <i class="bi bi-info-circle me-1"></i>Xem yêu cầu trả hàng
                                </a>
                                <?php elseif (($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành') && !$can_return && !$is_returned): ?>
                                <!-- Đã hết hạn -->
                                <span class="btn-oh btn-oh-disabled" title="Đã hết thời hạn">
                                    <i class="bi bi-x-circle me-1"></i>Hết hạn trả hàng
                                </span>
                                <?php endif; ?>

                                <?php if ($status === 'delivered' || $status === 'Đã giao' || $status === 'Hoàn thành'): ?>
                                <form method="POST" action="OrderHistory.php?action=rebuy" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $e($oid) ?>">
                                    <button type="submit" class="btn-oh btn-oh-outline btn-rebuy">
                                        <i class="bi bi-arrow-repeat me-1"></i>Mua lại
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if ($status === 'pending'): ?>
                                <form method="POST"
                                      action="../../../app/views/customer/CancelOrder.php"
                                      style="display:inline;"
                                      onsubmit="return confirm('Huỷ đơn hàng này?')">
                                    <input type="hidden" name="order_id" value="<?= $e($oid) ?>">
                                    <button type="submit" class="btn-oh btn-oh-outline btn-cancel-order">
                                        Huỷ đơn
                                    </button>
                                </form>
                                <?php endif; ?>

                                <a href="../../../app/views/customer/OrderDetail.php?id=<?= $e($oid) ?>"
                                   class="btn-oh btn-oh-primary">
                                    Chi tiết
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div><!-- /.oh-main -->
    </div><!-- /.profile-layout -->
</div>

<?php include '../../../app/views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../public/assets/js/OrderHistory.js"></script>

<!-- Modal đăng xuất -->
<div id="logoutOverlay" style="
    display:none;position:fixed;top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.55);z-index:99999;
    align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;padding:36px 28px 28px;max-width:360px;
                width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.25);text-align:center;
                font-family:'Plus Jakarta Sans',sans-serif;position:relative;z-index:100000;">
        <div style="width:60px;height:60px;border-radius:50%;background:#fef2f2;
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 16px;font-size:1.6rem;color:#c0392b;">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        <div style="font-size:1.08rem;font-weight:800;color:#1a2e1c;margin-bottom:8px;">Đăng xuất?</div>
        <div style="font-size:0.88rem;color:#6b7c6e;margin-bottom:24px;line-height:1.6;">
            Bạn có chắc muốn đăng xuất khỏi tài khoản không?
        </div>
        <div style="display:flex;gap:10px;">
            <button id="btnLogoutCancel"
                    style="flex:1;padding:11px;border-radius:999px;border:1.5px solid #dde8da;
                           background:none;font-weight:600;font-size:0.9rem;color:#6b7c6e;
                           cursor:pointer;font-family:inherit;">Huỷ</button>
            <a href="../../../app/views/customer/logout.php"
               style="flex:1;padding:11px;border-radius:999px;border:none;background:#c0392b;
                      color:#fff;font-weight:700;font-size:0.9rem;text-decoration:none;
                      display:flex;align-items:center;justify-content:center;">Đăng xuất</a>
        </div>
    </div>
</div>
</body>
</html>