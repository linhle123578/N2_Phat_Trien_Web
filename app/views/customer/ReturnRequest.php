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
    4000, NULL, MYSQLI_CLIENT_SSL
);
mysqli_set_charset($conn, "utf8mb4");

require_once __DIR__ . '/../../models/ReturnRequestModel.php';
require_once __DIR__ . '/../../controllers/customer/ReturnRequestController.php';

$controller = new ReturnRequestController($conn);
$controller->dispatch();

mysqli_close($conn);

// Render views
if (!empty($controller->view_name)) {
    extract($controller->view_data);
    $e = fn($s) => ReturnRequestController::e((string)$s);

    if ($controller->view_name === 'return_request_form') {
        $price = fn($n) => ReturnRequestController::formatPrice((float)$n);
        $old_val = fn($key, $default = '') => $e($old[$key] ?? $default);

        $extra = '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="../../../public/assets/css/ReturnRequest.css">
        ';
        echo $extra;

        $total = array_sum(array_map(fn($i) => (float)$i['price'] * (int)$i['quantity'], $order_items));
?>
        <div class="rr-wrapper">
            <div class="rr-container">

                <nav class="rr-breadcrumb">
                    <a href="../../../public/index.php">Trang chủ</a>
                    <span>›</span>
                    <a href="../../../app/views/customer/OrderHistory.php">Lịch sử đơn hàng</a>
                    <span>›</span>
                    <span class="current">Yêu cầu đổi/trả hàng</span>
                </nav>

                <div class="rr-page-title">
                    <div class="rr-title-icon"><i class="bi bi-arrow-return-left"></i></div>
                    <div>
                        <h2>Yêu cầu đổi / trả hàng</h2>
                        <p>Mã đơn hàng: <strong><?= $e($order_id) ?></strong></p>
                    </div>
                </div>

                <?php if (!empty($errors['_global'])): ?>
                <div class="rr-alert rr-alert-error">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?= $e($errors['_global']) ?>
                </div>
                <?php endif; ?>

                <div class="rr-layout">

                    <div class="rr-form-col">
                        <form method="POST" action="ReturnRequest.php" id="returnForm" novalidate>
                            <input type="hidden" name="order_id" value="<?= $e($order_id) ?>">

                            <div class="rr-section">
                                <div class="rr-section-header">
                                    <span class="rr-step">1</span>
                                    <h4>Lý do trả hàng</h4>
                                </div>
                                <div class="rr-reason-grid">
                                    <?php foreach ($suggest_reasons as $r): ?>
                                    <label class="rr-reason-chip <?= ($old['reason'] ?? '') === $r ? 'selected' : '' ?>">
                                        <input type="radio" name="reason" value="<?= $e($r) ?>"
                                               <?= ($old['reason'] ?? '') === $r ? 'checked' : '' ?>>
                                        <?= $e($r) ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!empty($errors['reason'])): ?>
                                    <div class="rr-field-error"><?= $e($errors['reason']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="rr-section">
                                <div class="rr-section-header">
                                    <span class="rr-step">2</span>
                                    <h4>Mô tả chi tiết <span class="rr-optional">(tuỳ chọn)</span></h4>
                                </div>
                                <textarea name="description" id="description"
                                          class="rr-textarea <?= !empty($errors['description']) ? 'is-invalid' : '' ?>"
                                          maxlength="1000"
                                          placeholder="Mô tả thêm về tình trạng sản phẩm, lý do đổi/trả..."
                                          rows="4"><?= $old_val('description') ?></textarea>
                                <div class="rr-char-count"><span id="charCount">0</span>/1000</div>
                                <?php if (!empty($errors['description'])): ?>
                                    <div class="rr-field-error"><?= $e($errors['description']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="rr-section">
                                <div class="rr-section-header">
                                    <span class="rr-step">3</span>
                                    <h4>Hình thức xử lý</h4>
                                </div>
                                <div class="rr-type-options">
                                    <label class="rr-type-card <?= ($old['return_type'] ?? '') === 'Đổi hàng' ? 'selected' : '' ?>">
                                        <input type="radio" name="return_type" value="Đổi hàng"
                                               <?= ($old['return_type'] ?? '') === 'Đổi hàng' ? 'checked' : '' ?>>
                                        <i class="bi bi-arrow-left-right"></i>
                                        <strong>Đổi hàng</strong>
                                        <small>Nhận sản phẩm mới thay thế</small>
                                    </label>
                                    <label class="rr-type-card <?= ($old['return_type'] ?? '') === 'Hoàn tiền' ? 'selected' : '' ?>">
                                        <input type="radio" name="return_type" value="Hoàn tiền"
                                               <?= ($old['return_type'] ?? '') === 'Hoàn tiền' ? 'checked' : '' ?>>
                                        <i class="bi bi-cash-coin"></i>
                                        <strong>Hoàn tiền</strong>
                                        <small>Hoàn tiền về tài khoản ngân hàng</small>
                                    </label>
                                </div>
                                <?php if (!empty($errors['return_type'])): ?>
                                    <div class="rr-field-error"><?= $e($errors['return_type']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="rr-section rr-bank-section"
                                 id="bankSection"
                                 style="<?= ($old['return_type'] ?? '') === 'Hoàn tiền' ? '' : 'display:none' ?>">
                                <div class="rr-section-header">
                                    <span class="rr-step">4</span>
                                    <h4>Thông tin tài khoản nhận hoàn tiền</h4>
                                </div>
                                <div class="rr-field-group">
                                    <div class="rr-field">
                                        <label>Tên ngân hàng <span class="rr-required">*</span></label>
                                        <input type="text" name="bank_name"
                                               class="rr-input <?= !empty($errors['bank_name']) ? 'is-invalid' : '' ?>"
                                               placeholder="VD: Vietcombank, Techcombank, BIDV..."
                                               value="<?= $old_val('bank_name') ?>">
                                        <?php if (!empty($errors['bank_name'])): ?>
                                            <div class="rr-field-error"><?= $e($errors['bank_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rr-field">
                                        <label>Số tài khoản <span class="rr-required">*</span></label>
                                        <input type="text" name="bank_account"
                                               class="rr-input <?= !empty($errors['bank_account']) ? 'is-invalid' : '' ?>"
                                               placeholder="Nhập số tài khoản ngân hàng"
                                               value="<?= $old_val('bank_account') ?>">
                                        <?php if (!empty($errors['bank_account'])): ?>
                                            <div class="rr-field-error"><?= $e($errors['bank_account']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rr-field">
                                        <label>Tên chủ tài khoản <span class="rr-required">*</span></label>
                                        <input type="text" name="bank_holder"
                                               class="rr-input <?= !empty($errors['bank_holder']) ? 'is-invalid' : '' ?>"
                                               placeholder="Nhập tên in hoa đúng như trên thẻ"
                                               value="<?= $old_val('bank_holder') ?>">
                                        <?php if (!empty($errors['bank_holder'])): ?>
                                            <div class="rr-field-error"><?= $e($errors['bank_holder']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="rr-submit-row">
                                <a href="../../../app/controllers/customer/OrderDetailController.php?id=<?= $e($order_id) ?>" class="btn-rr btn-rr-outline">
                                    <i class="bi bi-arrow-left me-1"></i>Quay lại
                                </a>
                                <button type="submit" class="btn-rr btn-rr-primary" id="btnSubmit">
                                    <i class="bi bi-send me-1"></i>Gửi yêu cầu
                                </button>
                            </div>

                        </form>
                    </div>

                    <div class="rr-summary-col">
                        <div class="rr-summary-card">
                            <div class="rr-summary-title">
                                <i class="bi bi-receipt me-2"></i>Tóm tắt đơn hàng
                            </div>

                            <div class="rr-summary-meta">
                                <div class="rr-meta-row">
                                    <span>Mã đơn</span>
                                    <strong><?= $e($order_id) ?></strong>
                                </div>
                                <div class="rr-meta-row">
                                    <span>Ngày đặt</span>
                                    <span><?= date('d/m/Y', strtotime($order_info['created_at'])) ?></span>
                                </div>
                                <div class="rr-meta-row">
                                    <span>Thanh toán</span>
                                    <span><?= $e($order_info['payment_method'] ?? '—') ?></span>
                                </div>
                            </div>

                            <div class="rr-summary-items">
                                <?php foreach ($order_items as $item):
                                    $img = '../../../Media/' . $e($item['product_image'] ?? 'default.jpg');
                                ?>
                                <div class="rr-summary-item">
                                    <img src="<?= $img ?>"
                                         alt="<?= $e($item['product_name'] ?? '') ?>"
                                         onerror="this.src='../../../Media/default.jpg'">
                                    <div class="rr-si-info">
                                        <div class="rr-si-name"><?= $e($item['product_name'] ?? '') ?></div>
                                        <div class="rr-si-qty">SL: <?= (int)$item['quantity'] ?></div>
                                    </div>
                                    <div class="rr-si-price">
                                        <?= $price((float)$item['price'] * (int)$item['quantity']) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="rr-summary-total">
                                <span>Tổng tiền</span>
                                <strong><?= $price((float)($order_info['total_amount'] ?? $total)) ?></strong>
                            </div>

                            <div class="rr-policy-box">
                                <div class="rr-policy-title">
                                    <i class="bi bi-shield-check me-1"></i>Chính sách đổi/trả
                                </div>
                                <ul>
                                    <li>Áp dụng trong vòng <strong>3 ngày</strong> kể từ khi nhận hàng.</li>
                                    <li>Sản phẩm phải còn nguyên vẹn, chưa qua sử dụng (trừ lỗi vận chuyển).</li>
                                    <li>Hoàn tiền xử lý trong 3–5 ngày làm việc.</li>
                                    <li>Liên hệ hotline <strong>1800 xxxx</strong> nếu cần hỗ trợ.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div></div>
        </div>

        

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../../../public/assets/js/ReturnRequest.js"></script>
<?php
    } elseif ($controller->view_name === 'return_request_success') {
        $extra = '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="../../../public/assets/css/ReturnRequest.css">
        ';
        echo $extra;
?>
        <div class="rr-wrapper">
            <div class="rr-container rr-success-container">

                <div class="rr-success-card">
                    <div class="rr-success-icon">
                        <svg viewBox="0 0 52 52" class="rr-checkmark">
                            <circle class="rr-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                            <path class="rr-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>

                    <h2 class="rr-success-title">Thông tin Yêu cầu Trả/Đổi hàng</h2>
                    <p class="rr-success-sub">
                        Dưới đây là chi tiết yêu cầu của bạn.<br>
                        Vui lòng chờ xác nhận trong 1–2 ngày làm việc.
                    </p>

                    <div class="rr-success-info">
                        <div class="rr-si-box">
                            <div class="rr-si-label">Mã yêu cầu</div>
                            <div class="rr-si-value highlight"><?= $e($return_id) ?></div>
                        </div>
                        <div class="rr-si-box">
                            <div class="rr-si-label">Mã đơn hàng</div>
                            <div class="rr-si-value"><?= $e($order_id) ?></div>
                        </div>
                        <div class="rr-si-box">
                            <div class="rr-si-label">Lý do</div>
                            <div class="rr-si-value"><?= $e($return_info['reason'] ?? '—') ?></div>
                        </div>
                        <div class="rr-si-box">
                            <div class="rr-si-label">Hình thức</div>
                            <div class="rr-si-value"><?= $e($return_info['return_type'] ?? '—') ?></div>
                        </div>
                        <div class="rr-si-box">
                            <div class="rr-si-label">Trạng thái</div>
                            <div class="rr-si-value">
                                <span class="rr-badge rr-badge-processing">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    <?= $e($return_info['return_status'] ?? 'Đang xử lý') ?>
                                </span>
                            </div>
                        </div>
                        <div class="rr-si-box">
                            <div class="rr-si-label">Ngày gửi</div>
                            <div class="rr-si-value">
                                <?= date('d/m/Y H:i', strtotime($return_info['request_date'] ?? 'now')) ?>
                            </div>
                        </div>
                    </div>

                    <div class="rr-timeline">
                        <div class="rr-tl-item active">
                            <div class="rr-tl-dot"><i class="bi bi-check-lg"></i></div>
                            <div class="rr-tl-text">Đã gửi yêu cầu</div>
                        </div>
                        <div class="rr-tl-line"></div>
                        <div class="rr-tl-item">
                            <div class="rr-tl-dot"><i class="bi bi-search"></i></div>
                            <div class="rr-tl-text">Shop xem xét</div>
                        </div>
                        <div class="rr-tl-line"></div>
                        <div class="rr-tl-item">
                            <div class="rr-tl-dot"><i class="bi bi-box-arrow-in-right"></i></div>
                            <div class="rr-tl-text">Nhận hàng trả</div>
                        </div>
                        <div class="rr-tl-line"></div>
                        <div class="rr-tl-item">
                            <div class="rr-tl-dot">
                                <i class="bi bi-<?= ($return_info['return_type'] ?? '') === 'Hoàn tiền' ? 'cash-coin' : 'arrow-left-right' ?>"></i>
                            </div>
                            <div class="rr-tl-text">
                                <?= $e($return_info['return_type'] ?? 'Xử lý') ?>
                            </div>
                        </div>
                    </div>

                    <div class="rr-success-actions">
                        <a href="../../../app/controllers/customer/OrderDetailController.php?id=<?= $e($order_id) ?>" class="btn-rr btn-rr-outline">
                            <i class="bi bi-bag-check me-1"></i>Xem đơn hàng
                        </a>
                        <a href="../../../app/views/customer/TrangChu.php" class="btn-rr btn-rr-primary">
                            <i class="bi bi-house me-1"></i>Về trang chủ
                        </a>
                    </div>
                </div>

            </div>
        </div>

        
        <?php include_once __DIR__ . '/../layouts/footer.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>
<?php
    }
}
?>
