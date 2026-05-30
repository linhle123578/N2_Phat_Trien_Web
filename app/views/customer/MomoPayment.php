<?php
ob_start();
include_once __DIR__ . '/../layouts/header.php';
$extra_head = '
    <title>Thanh toán MoMo - Farm2Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/Momo.css">
    <script>
        const MOMO_ORDER_DATA = ' . json_encode([
            'order_id'     => $order_id      ?? null,
            'products'     => $order_products ?? [],
            'subtotal'     => $subtotal       ?? 0,
            'shipping_fee' => $shipping_fee   ?? 0,
            'total_amount' => $total_amount   ?? 0,
            'live_mode'    => true,
        ]) . ';
    </script>
';
echo $extra_head;
?>

    <main class="momo-main" style="margin-top: 76px;">
        <div class="container">

            <!-- Title -->
            <div class="text-center mb-2">
                <h1 class="momo-page-title">Thanh toán đơn hàng</h1>
                <p class="momo-page-subtitle">Vui lòng sử dụng ứng dụng MoMo để quét mã</p>
            </div>

            <!-- Bento Grid -->
            <div class="momo-bento">

                <!-- ── LEFT: Chi tiết đơn hàng ── -->
                <div class="momo-order-panel">

                    <div class="momo-order-heading">
                        <div class="heading-icon">
                            <svg width="18" height="20" viewBox="0 0 18 20" fill="none">
                                <path d="M14 1H4C2.34315 1 1 2.34315 1 4V17C1 18.1046 1.89543 19 3 19H15C16.1046 19 17 18.1046 17 17V4C17 2.34315 15.6569 1 14 1Z" stroke="#022409" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M5 7H13M5 11H10" stroke="#022409" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h2>Chi tiết đơn hàng</h2>
                    </div>

                    <!-- Items -->
                    <div class="momo-order-items">
                        <?php if (!empty($order_products)): ?>
                            <?php foreach ($order_products as $prod): ?>
                                <?php
                                    $img = !empty($prod['product_image'])
                                         ? '../../../Media/' . htmlspecialchars($prod['product_image'])
                                        : '../../../Media/no-image.png';
                                ?>
                                <div class="momo-order-item">
                                    <div class="item-img-wrap">
                                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($prod['product_name'] ?? '') ?>">
                                    </div>
                                    <div class="item-info">
                                        <div class="item-name"><?= htmlspecialchars($prod['product_name'] ?? 'Sản phẩm') ?></div>
                                        <div class="item-meta">x<?= (int)($prod['quantity'] ?? 1) ?><?= !empty($prod['unit']) ? ' ' . htmlspecialchars($prod['unit']) : '' ?></div>
                                    </div>
                                    <div class="item-price">
                                        <?= number_format(($prod['price'] ?? 0) * ($prod['quantity'] ?? 1), 0, ',', '.') ?>đ
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="momo-order-item">
                                <div class="item-info">
                                    <div class="item-name" style="color:#9CA3AF;">Không có dữ liệu sản phẩm</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Summary -->
                    <div class="momo-summary-block">
                        <div class="summary-row">
                            <span class="label">Tổng tiền hàng</span>
                            <span class="value"><?= number_format($subtotal ?? 0, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Phí vận chuyển</span>
                            <span class="value"><?= number_format($shipping_fee ?? 0, 0, ',', '.') ?>đ</span>
                        </div>
                        <hr class="summary-divider">
                        <div class="summary-total-row">
                            <span class="total-label">Tổng thanh toán</span>
                            <span class="total-value"><?= number_format($total_amount ?? 0, 0, ',', '.') ?>đ</span>
                        </div>
                    </div>

                </div>

                <!-- ── RIGHT: QR Code ── -->
                <div class="momo-qr-panel">

                    <div class="qr-card-frame" id="qr-card-frame">
                        <div class="qr-placeholder" id="qr-placeholder">
                            <div class="qr-icon">📱</div>
                            <p>Đang tạo mã QR...</p>
                        </div>

                        <img id="qr-img" src="" alt="MoMo QR Code" style="display:none; width:100%; height:100%; object-fit:contain;">

                        <div class="qr-loading-overlay" id="qr-loading">
                            <div class="qr-spinner"></div>
                        </div>

                        <div class="qr-status-overlay success-overlay" id="qr-success-overlay">
                            <span class="status-icon">✅</span>
                            <p class="status-msg">Thanh toán thành công!</p>
                        </div>

                        <div class="qr-status-overlay expired-overlay" id="qr-expired-overlay">
                            <span class="status-icon">⏰</span>
                            <p class="status-msg">Mã QR đã hết hạn</p>
                        </div>
                    </div>

                    <p class="qr-instruction">
                        Mở ứng dụng MoMo → Chọn <strong>Quét mã</strong><br>
                        và quét mã QR này để thanh toán
                    </p>

                    <div class="qr-timer-badge" id="qr-timer-badge">
                        <span class="timer-icon">⏱</span>
                        <span class="timer-text" id="qr-timer-text">10:00</span>
                    </div>

                    <button class="btn-refresh-qr" id="btn-refresh-qr" onclick="refreshQR()">
                        🔄 Làm mới mã QR
                    </button>

                </div>

            </div>

            <!-- Back -->
            <div class="momo-action-row">
                <a href="/app/controllers/customer/CheckoutController.php" class="btn-back-link">
                    <span class="back-arrow">←</span>
                    Quay lại
                </a>
            </div>

        </div>
    </main>

=======
    <?php include __DIR__ . '/../layouts/footer.php'; ?>
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f

    <!-- Success Modal -->
    <div class="momo-success-modal" id="momo-success-modal">
        <div class="success-modal-card">
            <div class="success-check-circle">✅</div>
            <h2 class="success-modal-title">Đặt hàng thành công!</h2>
            <p class="success-modal-sub">Cảm ơn bạn đã tin tưởng Farm2Home</p>
            <p class="success-modal-sub">Mã đơn hàng của bạn:</p>
            <div class="success-order-id" id="modal-order-id">—</div>
            <a href="../../../app/views/customer/TrangChu.php" class="btn-success-home">Về trang chủ</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/Momo.js"></script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>