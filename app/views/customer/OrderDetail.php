<?php
/**
 * View: OrderDetail
 * Nhận data từ OrderDetailController qua extract($data)
 * KHÔNG chứa logic DB hay xử lý POST — tất cả đã chuyển sang Controller
 *
 * Các biến được inject:
 *   $order_id, $order, $items,
 *   $payment, $shipment, $address,
 *   $customer_phone, $customer_name,
 *   $order_count, $can_return, $is_returned,
 *   $subtotal, $total_amount, $discount
 */
ob_start();
include_once __DIR__ . '/../layouts/header.php';
$header_output = ob_get_clean();

$extra_head = '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/OrderDetail.css">
';
$header_output = str_replace('</head>', $extra_head . '</head>', $header_output);
echo $header_output;

// Alias ngắn cho helper tĩnh của controller
// (Controller đã được require trước khi render, nên class luôn tồn tại)
$ctrl = 'OrderDetailController';
?>

<div class="container" style="padding-top:80px;">

    <nav class="profile-breadcrumb">
        <a href="../../../app/views/customer/TrangChu.php">Trang chủ</a>
        <span class="sep">›</span>
        <a href="../../../app/views/customer/OrderHistory.php">Lịch sử đơn hàng</a>
        <span class="sep">›</span>
        <span class="current"><?= $ctrl::e($order_id) ?></span>
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
                        <a href="../../../app/views/customer/OrderHistory.php">
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

            <!-- Nút quay lại + trạng thái -->
            <div class="od-topbar">
                <a href="../../../app/views/customer/OrderHistory.php" class="btn-back">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                </a>
                <span class="oh-status-badge <?= $ctrl::statusClass($order['order_status']) ?>">
                    <?= $ctrl::statusLabel($order['order_status']) ?>
                </span>
            </div>

            <!-- ── 1. Thông tin đơn ───────────────── -->
            <div class="section-card od-section">
                <div class="od-section-title">Thông tin đơn</div>
                <div class="od-info-table">
                    <div class="od-info-row">
                        <span class="od-info-label">Mã đơn hàng</span>
                        <span class="od-info-val od-mono"><?= $ctrl::e($order_id) ?></span>
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
                            <strong><?= $ctrl::e($address['receiver_name'] ?? '') ?></strong>
                            - <?= $ctrl::e($customer_phone) ?><br>
                            <?= $ctrl::e($ctrl::buildAddress($address)) ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="od-info-row od-addr-row">
                        <span class="od-info-label">
                            <i class="bi bi-geo-alt-fill od-addr-icon"></i>
                            Giao đến
                        </span>
                        <span class="od-info-val">
                            <strong><?= $ctrl::e($customer_name) ?></strong>
                            - <?= $ctrl::e($customer_phone) ?><br>
                            <em>(Chưa có thông tin địa chỉ chi tiết)</em>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php $pay_method = $payment['payment_method'] ?? 'Thanh toán khi nhận hàng (COD)'; ?>
                    <div class="od-info-row">
                        <span class="od-info-label">Thanh toán</span>
                        <span class="od-info-val"><?= $ctrl::e($pay_method) ?></span>
                    </div>

                    <?php
                    $ship_method = $shipment['shipment_method'] ?? 'Giao hàng tiêu chuẩn';
                    $est_date    = $shipment['estimated_date']  ?? date('Y-m-d', strtotime($order['created_at'] . ' + 3 days'));
                    ?>
                    <div class="od-info-row">
                        <span class="od-info-label">Vận chuyển</span>
                        <span class="od-info-val"><?= $ctrl::e($ship_method) ?></span>
                    </div>
                    <div class="od-info-row">
                        <span class="od-info-label">Dự kiến giao</span>
                        <span class="od-info-val"><?= date('d/m/Y', strtotime($est_date)) ?></span>
                    </div>
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
            $step_keys   = array_keys($steps);
            $os_lower    = mb_strtolower($order['order_status'] ?? '', 'UTF-8');
            $norm_status = match(true) {
                in_array($os_lower, ['chờ xác nhận', 'pending'])                        => 'pending',
                in_array($os_lower, ['đã xác nhận',  'confirmed'])                      => 'confirmed',
                in_array($os_lower, ['đang giao',    'shipping'])                       => 'shipping',
                in_array($os_lower, ['đã giao', 'hoàn thành', 'completed', 'delivered'])=> 'delivered',
                default => ''
            };
            $current_idx  = array_search($norm_status, $step_keys);
            $is_cancelled = in_array($os_lower, ['cancelled', 'đã hủy', 'đã huỷ']);
            ?>

            <?php if (!$is_cancelled): ?>
            <div class="section-card od-section od-timeline-card">
                <div class="od-section-title">
                    Trạng thái
                    <?php if ($current_idx !== false): ?>
                        <span class="oh-status-badge <?= $ctrl::statusClass($order['order_status']) ?> od-status-inline">
                            <?= $ctrl::statusLabel($order['order_status']) ?>
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
                            <?= $done ? '<i class="bi bi-check-lg"></i>' : '<span>' . ($i + 1) . '</span>' ?>
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
                    <img src="<?= $ctrl::e($img_src) ?>"
                         alt="<?= $ctrl::e($item['product_name'] ?? '') ?>"
                         onerror="this.src='../../../Media/default.jpg'">
                    <div class="od-item-info">
                        <div class="od-item-name"><?= $ctrl::e($item['product_name'] ?? '') ?></div>
                        <div class="od-item-price-row">
                            <span class="od-item-price"><?= $ctrl::formatPrice((float)$item['price']) ?></span>
                        </div>
                    </div>
                    <div class="od-item-right">
                        <div class="od-item-qty">x<?= (int)$item['quantity'] ?></div>
                        <div class="od-item-subtotal"><?= $ctrl::formatPrice((float)$item['price'] * (int)$item['quantity']) ?></div>
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
                        <span><?= $ctrl::formatPrice($subtotal) ?></span>
                    </div>
                    <?php if ($discount > 0.01): ?>
                    <div class="od-pay-row discount">
                        <span>Giảm giá</span>
                        <span>- <?= $ctrl::formatPrice($discount) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($total_amount > $subtotal - $discount):
                        $shipping_fee_val = $total_amount - ($subtotal - $discount);
                    ?>
                    <div class="od-pay-row">
                        <span>Phí vận chuyển</span>
                        <span><?= $ctrl::formatPrice($shipping_fee_val) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($payment && !empty($payment['payment_status'])): ?>
                    <div class="od-pay-row">
                        <span>Trạng thái thanh toán</span>
                        <?php
                        $ps       = $payment['payment_status'];
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
                        <span class="od-mono"><?= $ctrl::e($payment['transaction_id']) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="od-pay-row od-pay-total">
                        <span>Tổng cộng</span>
                        <strong><?= $ctrl::formatPrice($total_amount) ?></strong>
                    </div>
                </div>
            </div>

            <!-- ── 5. Actions ────────────────────── -->
            <div class="od-actions-bar">

                <?php if ($order['order_status'] === 'pending'): ?>
                <form method="POST"
                      action="../../../app/controllers/customer/OrderDetailController.php?id=<?= urlencode($order_id) ?>"
                      onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đơn hàng này?');"
                      style="margin:0;">
                    <button type="submit" name="cancel_order" class="btn-od btn-od-cancel">
                        Huỷ đơn hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'shipping'): ?>
                <form method="POST"
                      action="../../../app/controllers/customer/OrderDetailController.php?id=<?= urlencode($order_id) ?>"
                      onsubmit="return confirm('Xác nhận bạn đã nhận được hàng?');"
                      style="margin:0;">
                    <button type="submit" name="confirm_received" class="btn-od btn-od-primary">
                        Đã nhận được hàng
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($is_returned): ?>
                    <a href="../../../app/views/customer/ReturnRequest.php?order_id=<?= urlencode($order_id) ?>"
                       class="btn-od"
                       style="background-color:#ff9800;color:white;border-color:#ff9800;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="bi bi-info-circle me-1"></i>Xem yêu cầu trả hàng
                    </a>
                <?php elseif ($can_return): ?>
                    <a href="../../../app/views/customer/ReturnRequest.php?order_id=<?= urlencode($order_id) ?>"
                       class="btn-od"
                       style="background-color:#dc3545;color:white;border-color:#dc3545;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="bi bi-arrow-return-left me-1"></i>Trả hàng
                    </a>
                <?php endif; ?>

                <?php
                $od_rebuy_items = array_map(
                    fn($it) => ['product_id' => $it['product_id'], 'quantity' => (int)$it['quantity']],
                    $items
                );
                ?>
                <button type="button" class="btn-od btn-od-outline"
                        onclick="rebuyOrder(<?= htmlspecialchars(json_encode($od_rebuy_items), ENT_QUOTES) ?>)">
                    <i class="bi bi-arrow-repeat me-1"></i>Mua lại
                </button>

                <a href="../../../app/views/customer/Products.php"
                   class="btn-od btn-od-secondary">Tiếp tục mua sắm</a>
            </div>

        </div><!-- /.od-main -->
    </div><!-- /.profile-layout -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btnLogout')?.addEventListener('click', (e) => {
    e.preventDefault();
    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
    window.location.href = '../../../app/controllers/customer/LogoutController.php';
    }
});

// Mua lại: thêm từng sản phẩm vào giỏ rồi chuyển sang trang giỏ hàng
function rebuyOrder(items) {
    if (!items || items.length === 0) return;
    var cartUrl     = '../../../app/controllers/customer/CartController.php';
    var cartPageUrl = '../../../app/views/customer/cart.php';
    var total = items.length, done = 0;
    items.forEach(function(item) {
        var fd = new FormData();
        fd.append('product_id', item.product_id);
        fd.append('quantity',   item.quantity);
        fd.append('ajax', '1');
        fetch(cartUrl, { method: 'POST', body: fd })
            .then(function()  { done++; if (done === total) window.location.href = cartPageUrl; })
            .catch(function() { done++; if (done === total) window.location.href = cartPageUrl; });
    });
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
