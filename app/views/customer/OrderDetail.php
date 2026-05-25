<?php
ob_start();
include __DIR__ . '/../layouts/loginheader.php';
$BASE_URL = '/n2_phat_trien_web';

// ── DB ────────────────────────────────────────────────────
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect(
    $conn,
    "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "3YHrkxqAKWynehu.root",
    "BzDRrZAdAT2jLuyd",
    "db_web_farm2home",
    4000, NULL, MYSQLI_CLIENT_SSL
);
mysqli_set_charset($conn, "utf8mb4");

$session_customer_id = $_SESSION['customer_id'] ?? 'CUS001';

$order_id = trim($_GET['id'] ?? '');
if (empty($order_id)) {
    header("Location: OrderHistory.php");
    exit;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function format_price(float $n): string { return number_format($n, 0, ',', '.') . ' ₫'; }

function status_label(string $s): string {
    return match($s) {
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'delivered' => 'Đã giao hàng',
        'cancelled' => 'Đã huỷ',
        default     => $s,
    };
}
function status_class(string $s): string {
    return match($s) {
        'pending'   => 'status-pending',
        'confirmed' => 'status-confirmed',
        'shipping'  => 'status-shipping',
        'delivered' => 'status-delivered',
        'cancelled' => 'status-cancelled',
        default     => '',
    };
}

// ── Load order ────────────────────────────────────────────
$order = null;
try {
    $s = mysqli_prepare($conn,
        "SELECT order_id, customer_id, address_id, order_status,
                total_quantity_order, created_at
         FROM `order` WHERE order_id = ? AND customer_id = ? LIMIT 1");
    mysqli_stmt_bind_param($s, 'ss', $order_id, $session_customer_id);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $order = mysqli_fetch_assoc($r);
    mysqli_stmt_close($s);
} catch (Exception $e) {}

if (!$order) {
    mysqli_close($conn);
    header("Location: OrderHistory.php");
    exit;
}

// ── Load items ────────────────────────────────────────────
$items = [];
try {
    $s = mysqli_prepare($conn,
        "SELECT oi.order_item_id, oi.product_id, oi.quantity, oi.price,
                pr.product_name, pr.product_image, pr.unit
         FROM orderitem oi
         LEFT JOIN product pr ON oi.product_id = pr.product_id
         WHERE oi.order_id = ?");
    mysqli_stmt_bind_param($s, 's', $order_id);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    while ($row = mysqli_fetch_assoc($r)) $items[] = $row;
    mysqli_stmt_close($s);
} catch (Exception $e) {}

// ── Load address ──────────────────────────────────────────
$address = null;
if (!empty($order['address_id'])) {
    try {
        $s = mysqli_prepare($conn,
            "SELECT receiver_name, address_type,
                    street_address, ward, district, province
             FROM address WHERE address_id = ? LIMIT 1");
        mysqli_stmt_bind_param($s, 's', $order['address_id']);
        mysqli_stmt_execute($s);
        $r = mysqli_stmt_get_result($s);
        $address = mysqli_fetch_assoc($r);
        mysqli_stmt_close($s);
    } catch (Exception $e) {}
}

// ── Load payment ──────────────────────────────────────────
$payment = null;
try {
    $s = mysqli_prepare($conn,
        "SELECT payment_id, payment_method, payment_status,
                total_amount, transaction_id, payment_date
         FROM payment WHERE order_id = ? LIMIT 1");
    mysqli_stmt_bind_param($s, 's', $order_id);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $payment = mysqli_fetch_assoc($r);
    mysqli_stmt_close($s);
} catch (Exception $e) {}

// ── Load shipment ─────────────────────────────────────────
$shipment = null;
try {
    $s = mysqli_prepare($conn,
        "SELECT shipment_method, shipment_status, estimated_date
         FROM shipment WHERE order_id = ? LIMIT 1");
    mysqli_stmt_bind_param($s, 's', $order_id);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $shipment = mysqli_fetch_assoc($r);
    mysqli_stmt_close($s);
} catch (Exception $e) {}

// ── Sidebar order count ───────────────────────────────────
$order_count = 0;
try {
    $s = mysqli_prepare($conn, "SELECT COUNT(*) FROM `order` WHERE customer_id = ?");
    mysqli_stmt_bind_param($s, 's', $session_customer_id);
    mysqli_stmt_execute($s);
    mysqli_stmt_bind_result($s, $order_count);
    mysqli_stmt_fetch($s);
    mysqli_stmt_close($s);
} catch (Exception $e) {}

mysqli_close($conn);

// ── Tính tiền ─────────────────────────────────────────────
$subtotal = 0;
foreach ($items as $item) $subtotal += (float)$item['price'] * (int)$item['quantity'];
$total_amount = $payment ? (float)$payment['total_amount'] : $subtotal;
$discount = $subtotal > $total_amount ? $subtotal - $total_amount : 0;

// ── Build address string ──────────────────────────────────
function build_address(array $addr): string {
    $parts = array_filter([
        $addr['street_address'] ?? '',
        $addr['ward']           ?? '',
        $addr['district']       ?? '',
        $addr['province']       ?? '',
    ]);
    return implode(', ', $parts);
}

// ── Header ────────────────────────────────────────────────
/*ob_start();
include '../../../app/views/layouts/header.php';
$header_output = ob_get_clean();*/
$extra_head = '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/OrderDetail.css">
';
//$header_output = str_replace('</head>', $extra_head . '</head>', $header_output);
//echo $header_output;
?>

<div class="container" style="padding-top:80px;">

    <nav class="profile-breadcrumb">
        <a href="../../../index.php">Trang chủ</a>
        <span class="sep">›</span>
        <a href="OrderHistory.php">Lịch sử đơn hàng</a>
        <span class="sep">›</span>
        <span class="current"><?= e($order_id) ?></span>
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
                        <a href="OrderHistory.php">
                            <i class="bi bi-bag-check"></i>
                            Lịch sử đơn hàng
                            <?php if ($order_count > 0): ?>
                                <span class="sidebar-badge"><?= (int)$order_count ?></span>
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
        <div class="od-main">

            <!-- Nút quay lại + tiêu đề -->
            <div class="od-topbar">
                <a href="OrderHistory.php" class="btn-back">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
                <span class="oh-status-badge <?= status_class($order['order_status']) ?>">
                    <?= status_label($order['order_status']) ?>
                </span>
            </div>

            <!-- ── 1. Thông tin đơn ───────────────── -->
            <div class="section-card od-section">
                <div class="od-section-title">Thông tin đơn</div>
                <div class="od-info-table">
                    <div class="od-info-row">
                        <span class="od-info-label">Mã đơn hàng</span>
                        <span class="od-info-val od-mono"><?= e($order_id) ?></span>
                    </div>
                    <div class="od-info-row">
                        <span class="od-info-label">Ngày đặt</span>
                        <span class="od-info-val">
                            <?= date('l', strtotime($order['created_at'])) ?>
                            &nbsp;•&nbsp;
                            <?= date('d/m/Y', strtotime($order['created_at'])) ?>
                        </span>
                    </div>
                    <?php if ($address): ?>
                    <div class="od-info-row od-addr-row">
                        <span class="od-info-label">
                            <i class="bi bi-geo-alt-fill od-addr-icon"></i>
                            Giao đến
                        </span>
                        <span class="od-info-val">
                            <strong><?= e($address['receiver_name'] ?? '') ?></strong><br>
                            <?= e(build_address($address)) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($payment && !empty($payment['payment_method'])): ?>
                    <div class="od-info-row">
                        <span class="od-info-label">Thanh toán</span>
                        <span class="od-info-val"><?= e($payment['payment_method']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($shipment && !empty($shipment['shipment_method'])): ?>
                    <div class="od-info-row">
                        <span class="od-info-label">Vận chuyển</span>
                        <span class="od-info-val"><?= e($shipment['shipment_method']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($shipment && !empty($shipment['estimated_date'])): ?>
                    <div class="od-info-row">
                        <span class="od-info-label">Dự kiến giao</span>
                        <span class="od-info-val"><?= date('d/m/Y', strtotime($shipment['estimated_date'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── 2. Timeline ────────────────────── -->
            <?php
            $steps = [
                'pending'   => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'shipping'  => 'Đang giao',
                'delivered' => 'Giao thành công',
            ];
            $step_keys    = array_keys($steps);
            $current_idx  = array_search($order['order_status'], $step_keys);
            $is_cancelled = $order['order_status'] === 'cancelled';
            ?>
            <?php if (!$is_cancelled): ?>
            <div class="section-card od-section od-timeline-card">
                <div class="od-section-title">
                    Trạng thái
                    <?php if ($current_idx !== false): ?>
                        <span class="oh-status-badge <?= status_class($order['order_status']) ?> od-status-inline">
                            <?= status_label($order['order_status']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="od-timeline">
                    <?php foreach ($step_keys as $i => $key):
                        $done    = $current_idx !== false && $i <= $current_idx;
                        $current = $current_idx !== false && $i === $current_idx;
                    ?>
                    <div class="od-step <?= $done ? 'done' : '' ?> <?= $current ? 'current' : '' ?>">
                        <div class="od-step-dot">
                            <?= $done ? '<i class="bi bi-check-lg"></i>' : '<span>' . ($i+1) . '</span>' ?>
                        </div>
                        <?php if ($i < count($step_keys) - 1): ?>
                            <div class="od-step-line <?= ($current_idx !== false && $i < $current_idx) ? 'done' : '' ?>"></div>
                        <?php endif; ?>
                        <div class="od-step-label"><?= $steps[$key] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="section-card od-section od-cancelled-banner">
                <i class="bi bi-x-circle-fill me-2"></i>Đơn hàng này đã bị huỷ
            </div>
            <?php endif; ?>

            <!-- ── 3. Sản phẩm ───────────────────── -->
            <div class="section-card od-section">
                <div class="od-section-title">Sản phẩm</div>
                <?php foreach ($items as $item):
                    $img_src = '../../../Media/' . ($item['product_image'] ?? 'default.jpg');
                ?>
                <div class="od-item">
                    <img src="<?= e($img_src) ?>"
                         alt="<?= e($item['product_name'] ?? '') ?>"
                         onerror="this.src='../../../Media/default.jpg'">
                    <div class="od-item-info">
                        <div class="od-item-name"><?= e($item['product_name'] ?? '') ?></div>
                        <div class="od-item-price-row">
                            <span class="od-item-price"><?= format_price((float)$item['price']) ?></span>
                        </div>
                    </div>
                    <div class="od-item-right">
                        <div class="od-item-qty">x<?= (int)$item['quantity'] ?></div>
                        <div class="od-item-subtotal"><?= format_price((float)$item['price'] * (int)$item['quantity']) ?></div>
                        <?php if ($order['order_status'] === 'delivered'): ?>
                        <a href="../../../app/views/customer/ProductDetail.php?id=<?= e($item['product_id']) ?>"
                           class="btn-review">
                            <i class="bi bi-star me-1"></i>Đánh giá
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── 4. Thanh toán ─────────────────── -->
            <div class="section-card od-section">
                <div class="od-section-title">Thanh toán</div>
                <div class="od-payment-table">
                    <div class="od-pay-row">
                        <span>Tạm tính</span>
                        <span><?= format_price($subtotal) ?></span>
                    </div>
                    <?php if ($discount > 0.01): ?>
                    <div class="od-pay-row discount">
                        <span>Giảm giá</span>
                        <span>- <?= format_price($discount) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($payment && !empty($payment['payment_status'])): ?>
                    <div class="od-pay-row">
                        <span>Trạng thái thanh toán</span>
                        <?php
                        $ps = $payment['payment_status'];
                        $ps_class = match($ps) {
                            'paid'    => 'pill-green',
                            'pending' => 'pill-yellow',
                            'failed'  => 'pill-red',
                            default   => 'pill-gray',
                        };
                        $ps_label = match($ps) {
                            'paid'    => 'Đã thanh toán',
                            'pending' => 'Chờ thanh toán',
                            'failed'  => 'Thất bại',
                            default   => $ps,
                        };
                        ?>
                        <span class="status-pill <?= $ps_class ?>"><?= $ps_label ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($payment && !empty($payment['transaction_id'])): ?>
                    <div class="od-pay-row">
                        <span>Mã giao dịch</span>
                        <span class="od-mono"><?= e($payment['transaction_id']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="od-pay-row od-pay-total">
                        <span>Tổng cộng</span>
                        <strong><?= format_price($total_amount) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="od-actions-bar">
                <?php if ($order['order_status'] === 'pending'): ?>
                <form method="POST"
                      action="../../../app/views/customer/CancelOrder.php"
                      onsubmit="return confirm('Huỷ đơn hàng này?')">
                    <input type="hidden" name="order_id" value="<?= e($order_id) ?>">
                    <button type="submit" class="btn-od btn-od-danger">
                        <i class="bi bi-x-circle me-1"></i>Huỷ đơn
                    </button>
                </form>
                <?php endif; ?>
                <?php if ($order['order_status'] === 'delivered'): ?>
                <form method="POST"
                      action="../../../app/views/customer/ReturnRequest.php">
                    <input type="hidden" name="order_id" value="<?= e($order_id) ?>">
                    <button type="submit" class="btn-od btn-od-outline">
                        <i class="bi bi-arrow-return-left me-1"></i>Đổi/Trả
                    </button>
                </form>
                <?php endif; ?>
            </div>

        </div><!-- /.od-main -->
    </div><!-- /.profile-layout -->
</div>

<?php include '../../../app/views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../public/assets/js/OrderDetail.js"></script>

<!-- Modal đăng xuất -->
<div id="logoutOverlay" style="
    display:none;position:fixed;top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.55);z-index:99999;
    align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;padding:36px 28px 28px;
                max-width:360px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.25);
                text-align:center;font-family:'Plus Jakarta Sans',sans-serif;
                position:relative;z-index:100000;">
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
               style="flex:1;padding:11px;border-radius:999px;background:#c0392b;
                      color:#fff;font-weight:700;font-size:0.9rem;text-decoration:none;
                      display:flex;align-items:center;justify-content:center;">Đăng xuất</a>
        </div>
    </div>
</div>
</body>
</html>