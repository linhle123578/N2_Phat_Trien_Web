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
mysqli_set_charset($conn, "utf8mb4");

require_once __DIR__ . '/../../models/OrderHistoryModel.php';

// ... Lấy dữ liệu cơ bản ...
$order_id = $_GET['id'] ?? '';
if (!$order_id) {
    die("Mã đơn hàng không hợp lệ.");
}

// Lấy customer_id từ session
$customer_id = $_SESSION['customer_id'] ?? 'KH001';
$model = new OrderHistoryModel($conn);
$orders = $model->getOrders($customer_id, 'all');

$order = null;
foreach ($orders as $o) {
    if ($o['order_id'] === $order_id) {
        $order = $o;
        break;
    }
}
if (!$order) {
    die("Đơn hàng không tồn tại hoặc không thuộc về bạn.");
}

// Lấy sản phẩm
$all_items = $model->getOrderItems([$order_id]);
$items = $all_items[$order_id] ?? [];

// Lấy thông tin thanh toán, giao hàng
$stmt = mysqli_prepare($conn, "SELECT * FROM payment WHERE order_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $order_id);
mysqli_stmt_execute($stmt);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT * FROM shipment WHERE order_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $order_id);
mysqli_stmt_execute($stmt);
$shipment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT * FROM address WHERE address_id = ? LIMIT 1");
$addr_id = $order['address_id'] ?? '';
mysqli_stmt_bind_param($stmt, 's', $addr_id);
mysqli_stmt_execute($stmt);
$address = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Tính số lượng đơn hàng (badge)
$counts = $model->getOrderCounts($customer_id);
$order_count = $counts['all'];

// Xử lý XÁC NHẬN ĐÃ NHẬN HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_received'])) {
    $stmt = mysqli_prepare($conn, "UPDATE `order` SET order_status = 'delivered' WHERE order_id = ?");
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: OrderDetail.php?id=" . urlencode($order_id));
    exit;
}

// Xử lý HUỶ ĐƠN HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if ($order['order_status'] === 'pending') {
        $stmt = mysqli_prepare($conn, "UPDATE `order` SET order_status = 'cancelled' WHERE order_id = ?");
        mysqli_stmt_bind_param($stmt, 's', $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: OrderDetail.php?id=" . urlencode($order_id));
        exit;
    }
}

// Helper functions
function e($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
function format_price(float $val): string {
    return number_format($val, 0, ',', '.') . ' đ';
}
function status_class($st): string {
    $st = mb_strtolower((string)$st, 'UTF-8');
    return match($st) {
        'pending', 'chờ xác nhận'   => 'status-pending',
        'confirmed', 'đã xác nhận' => 'status-confirmed',
        'shipping', 'đang giao'    => 'status-shipping',
        'delivered', 'đã giao', 'hoàn thành', 'completed' => 'status-delivered',
        'cancelled', 'đã hủy', 'đã huỷ'  => 'status-cancelled',
        default => 'status-pending'
    };
}
function status_label($st): string {
    $st = mb_strtolower((string)$st, 'UTF-8');
    return match($st) {
        'pending', 'chờ xác nhận'   => 'Chờ xác nhận',
        'confirmed', 'đã xác nhận' => 'Đã xác nhận',
        'shipping', 'đang giao'    => 'Đang giao',
        'delivered', 'đã giao', 'hoàn thành', 'completed' => 'Hoàn thành',
        'cancelled', 'đã hủy', 'đã huỷ'  => 'Đã huỷ',
        default => ucfirst($st)
    };
}

$subtotal = 0;
foreach ($items as $itm) {
    $subtotal += (float)$itm['price'] * (int)$itm['quantity'];
}
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
$extra_head = '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/OrderDetail.css">
';
echo $extra_head;
?>

<div class="container" style="padding-top:80px;">

    <nav class="profile-breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span class="sep">›</span>
<<<<<<< HEAD
        <a href="../../../app/views/customer/OrderHistory.php">Lịch sử đơn hàng</a>
=======
        <a href="index.php?page=orders">Lịch sử đơn hàng</a>
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
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
                        <a href="index.php?page=profile">
                            <i class="bi bi-person-circle"></i>
                            Thông tin cá nhân
                        </a>
                    </li>
                    <li class="active">
<<<<<<< HEAD
                        <a href="../../../app/views/customer/OrderHistory.php">
=======
                        <a href="index.php?page=orders">
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
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
<<<<<<< HEAD
                <a href="../../../app/views/customer/OrderHistory.php" class="btn-back">
=======
                <a href="index.php?page=orders" class="btn-back">
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
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
            $os_lower     = mb_strtolower($order['order_status'] ?? '', 'UTF-8');
            $norm_status  = $os_lower;
            
            if ($os_lower === 'chờ xác nhận' || $os_lower === 'pending') $norm_status = 'pending';
            elseif ($os_lower === 'đã xác nhận' || $os_lower === 'confirmed') $norm_status = 'confirmed';
            elseif ($os_lower === 'đang giao' || $os_lower === 'shipping') $norm_status = 'shipping';
            elseif ($os_lower === 'đã giao' || $os_lower === 'hoàn thành' || $os_lower === 'completed' || $os_lower === 'delivered') $norm_status = 'delivered';
            
            $current_idx  = array_search($norm_status, $step_keys);
            $is_cancelled = ($os_lower === 'cancelled' || $os_lower === 'đã hủy' || $os_lower === 'đã huỷ');
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
                      onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đơn hàng này?');"
                      style="margin:0;">
                    <button type="submit" name="cancel_order" class="btn-od btn-od-cancel">
                        Huỷ đơn hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'shipping'): ?>
                <form method="POST"
                      onsubmit="return confirm('Xác nhận bạn đã nhận được hàng?');"
                      style="margin:0;">
                    <button type="submit" name="confirm_received" class="btn-od btn-od-primary">
                        Đã nhận được hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php
                $od_rebuy_items = array_map(fn($it) => ['product_id' => $it['product_id'], 'quantity' => (int)$it['quantity']], $items);
                ?>
                <button type="button" class="btn-od btn-od-outline"
                        onclick="rebuyOrder(<?= htmlspecialchars(json_encode($od_rebuy_items), ENT_QUOTES) ?>)">
                    <i class="bi bi-arrow-repeat me-1"></i>Mua lại
                </button>

                <a href="index.php" class="btn-od btn-od-secondary">Tiếp tục mua sắm</a>
            </div>

        </div> <!-- /.od-main -->
    </div> <!-- /.profile-layout -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btnLogout')?.addEventListener('click', (e) => {
    e.preventDefault();
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        window.location.href = '../../../app/controllers/customer/LogOutController.php';
    }
});

// Mua lại: thêm từng sản phẩm vào giỏ rồi chuyển sang trang giỏ hàng
function rebuyOrder(items) {
    if (!items || items.length === 0) return;
    var cartUrl = '../app/controllers/customer/CartController.php';
<<<<<<< HEAD
    var cartPageUrl = '../../../app/views/customer/cart.php';
=======
    var cartPageUrl = 'index.php?page=cart';
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
    var total = items.length;
    var done = 0;
    items.forEach(function(item) {
        var fd = new FormData();
        fd.append('product_id', item.product_id);
        fd.append('quantity', item.quantity);
        fetch(cartUrl, { method: 'POST', body: fd })
            .then(function() { done++; if (done === total) window.location.href = cartPageUrl; })
            .catch(function() { done++; if (done === total) window.location.href = cartPageUrl; });
    });
}
</script>
<<<<<<< HEAD

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
=======
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
