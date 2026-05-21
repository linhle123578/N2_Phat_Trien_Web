<?php 
// Lấy header và footer (giữ nguyên cơ chế cũ)
ob_start();
include __DIR__ . '/../layouts/header.php';
$raw_header = ob_get_clean();
if (preg_match('/<(nav|header)[^>]*>.*?<\/\1>/is', $raw_header, $header_matches)) {
    $clean_header = $header_matches[0];
} else {
    $clean_header = $raw_header; 
}

ob_start();
include __DIR__ . '/../layouts/footer.php';
$raw_footer = ob_get_clean();
if (preg_match('/<footer[^>]*>.*?<\/footer>/is', $raw_footer, $footer_matches)) {
    $clean_footer = $footer_matches[0];
} else {
    $clean_footer = $raw_footer;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Farm2Home</title>
    <base href="/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/layout.css">
    <link rel="stylesheet" href="/public/assets/css/checkout.css">
    <script> const DB_SUBTOTAL = <?= (int)($subtotal ?? 0) ?>; </script>
</head>
<body>

<?= $clean_header ?>

<main class="checkout-main" style="margin-top: 50px;">
    <div class="container py-5">
        <h2 class="page-title mb-4">Thanh toán</h2>
        
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                
                <!-- ĐỊA CHỈ NHẬN HÀNG -->
                <section class="box-yellow mb-4">
                    <h3 class="section-title mb-3">📍 ĐỊA CHỈ NHẬN HÀNG</h3>
                    <div class="inner-card p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <?php
                                // Hiển thị tên người nhận (ưu tiên receiver_name từ address, nếu không thì full_name từ customer)
                                $receiver_name = !empty($default_address['receiver_name']) 
                                    ? $default_address['receiver_name'] 
                                    : ($customer_info['full_name'] ?? 'Khách hàng');
                                $phone_display = $customer_info['phone'] ?? '';

                                // Xây dựng địa chỉ đầy đủ từ các thành phần của bảng address
                                $full_address = '';
                                if (!empty($default_address)) {
                                    $parts = [];
                                    if (!empty($default_address['street_address'])) $parts[] = $default_address['street_address'];
                                    if (!empty($default_address['ward'])) $parts[] = $default_address['ward'];
                                    if (!empty($default_address['district'])) $parts[] = $default_address['district'];
                                    if (!empty($default_address['province'])) $parts[] = $default_address['province'];
                                    $full_address = implode(', ', $parts);
                                } else {
                                    $full_address = 'Chưa có địa chỉ mặc định';
                                }
                            ?>
                            <p class="fw-bold mb-1 fs-5" id="display-name">
                                <?= htmlspecialchars($receiver_name) ?> 
                                (+84) <?= htmlspecialchars(preg_replace('/^0/', '', $phone_display)) ?>
                            </p>
                            <p class="text-gray mb-0" id="display-address">
                                <?= htmlspecialchars($full_address) ?>
                            </p>
                        </div>
                        <button class="btn btn-link text-success fw-bold text-decoration-none letter-spacing-1 p-0" id="btn-change-address">THAY ĐỔI</button>
                    </div>
                </section>

                <!-- DANH SÁCH SẢN PHẨM -->
                <section class="mb-4">
                    <h3 class="section-title mb-3">SẢN PHẨM</h3>
                    <div class="d-flex flex-column gap-3">
                        <?php if (!empty($checkout_products)): ?>
                            <?php foreach ($checkout_products as $product): 
                                $img_src = !empty($product['image'])
                                    ? "/Media/" . htmlspecialchars($product['image'])
                                    : "/Media/no-image.png";
                            ?>
                                <div class="inner-card p-3 d-flex align-items-center gap-3 flex-wrap">
                                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img rounded">
                                    <div class="d-flex flex-grow-1 justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="col-name">
                                            <h4 class="fs-6 fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h4>
                                            <small class="text-gray">PHÂN LOẠI: <?= htmlspecialchars($product['unit'] ?? 'Bó/Túi') ?></small>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-gray d-block">Đơn giá</small>
                                            <span class="fw-semibold"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-gray d-block">Số lượng</small>
                                            <span class="fw-semibold"><?= $product['quantity'] ?></span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-gray d-block">Thành tiền</small>
                                            <span class="text-orange fs-5"><?= number_format($product['total_price'], 0, ',', '.') ?>đ</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">Không tìm thấy sản phẩm nào để thanh toán!</div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- PHƯƠNG THỨC VẬN CHUYỂN -->
                <section class="box-beige mb-4">
                    <h3 class="section-title mb-3">🚚 PHƯƠNG THỨC VẬN CHUYỂN</h3>
                    <div class="d-flex flex-column gap-3">
                        <label class="inner-card radio-card active p-3 d-flex align-items-center gap-3 m-0">
                            <input type="radio" name="shipping" value="25000" checked class="form-check-input m-0">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h4 class="fs-6 fw-bold m-0" style="color: #022409;">Giao hàng tiêu chuẩn</h4>
                                        <span class="badge text-dark" style="background-color: #F1BF4F; font-size: 10px;">RẺ NHẤT</span>
                                    </div>
                                    <small class="text-gray">Nhận hàng trong vòng 1 - 2 ngày</small>
                                </div>
                                <span class="fw-bold" style="color: #183A1D;">25.000đ</span>
                            </div>
                        </label>
                        <label class="inner-card radio-card p-3 d-flex align-items-center gap-3 m-0">
                            <input type="radio" name="shipping" value="55000" class="form-check-input m-0">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <h4 class="fs-6 fw-bold mb-1" style="color: #022409;">Giao hàng hỏa tốc</h4>
                                    <small class="text-gray">Nhận hàng trong vòng 2 giờ</small>
                                </div>
                                <span class="fw-bold" style="color: #183A1D;">55.000đ</span>
                            </div>
                        </label>
                    </div>
                </section>

                <!-- PHƯƠNG THỨC THANH TOÁN -->
                <section class="mb-4">
                    <h3 class="section-title mb-3">PHƯƠNG THỨC THANH TOÁN</h3>
                    <div class="d-flex flex-column gap-3">
                        <label class="inner-card radio-card active p-3 d-flex align-items-center gap-3 m-0">
                            <input type="radio" name="payment" value="momo" checked class="form-check-input m-0">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-4">🟣</span>
                                <div>
                                    <h4 class="fs-6 fw-bold mb-1">Thanh toán qua MoMo</h4>
                                    <small class="text-gray">Liên kết với ví MoMo</small>
                                </div>
                            </div>
                        </label>
                        <label class="inner-card radio-card p-3 d-flex align-items-center gap-3 m-0">
                            <input type="radio" name="payment" value="cod" class="form-check-input m-0">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-4">💵</span>
                                <div>
                                    <h4 class="fs-6 fw-bold mb-1">Thanh toán khi nhận hàng (COD)</h4>
                                    <small class="text-gray">Kiểm tra hàng trước khi thanh toán</small>
                                </div>
                            </div>
                        </label>
                    </div>
                </section>
            </div>

            <!-- THANH TỔNG -->
            <div class="col-12 col-lg-4">
                <div class="summary-card p-4 position-sticky" style="top: 100px;">
                    <h3 class="summary-title mb-4">Chi tiết thanh toán</h3>
                    <div class="d-flex justify-content-between mb-3 text-gray">
                        <span>Tổng tiền hàng</span>
                        <span class="fw-bold text-dark" id="subtotal-price"><?= number_format($subtotal ?? 0, 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 text-gray">
                        <span>Phí vận chuyển</span>
                        <span class="fw-bold text-dark" id="shipping-fee-display">25.000đ</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top border-dark-subtle pt-4 mb-4">
                        <span class="fw-bold">TỔNG THANH TOÁN</span>
                        <span class="text-orange fs-3 fw-bold" id="final-total"><?= number_format(($subtotal ?? 0) + 25000, 0, ',', '.') ?>đ</span>
                    </div>
                    <button class="btn-submit w-100 mb-3" id="btn-place-order">ĐẶT HÀNG</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL THAY ĐỔI ĐỊA CHỈ -->
<div class="modal-overlay" id="address-modal">
    <div class="modal-content-custom">
        <h3 class="mb-4 fw-bold" style="font-family: 'Plus Jakarta Sans', sans-serif;">Thay đổi địa chỉ</h3>
        <div class="mb-3">
            <label class="form-label fw-bold">Họ và tên</label>
            <input type="text" class="form-control" id="input-name" value="<?= htmlspecialchars($receiver_name ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Số điện thoại</label>
            <input type="text" class="form-control" id="input-phone" value="<?= htmlspecialchars($phone_display ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Loại địa chỉ</label>
            <select class="form-control form-select" id="input-address-type">
                <option value="Nhà riêng">Nhà riêng</option>
                <option value="Văn phòng">Văn phòng</option>
                <option value="Cơ quan">Cơ quan</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Địa chỉ chi tiết</label>
            <textarea class="form-control" id="input-address" rows="3"><?= htmlspecialchars($full_address) ?></textarea>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-secondary px-4" id="btn-cancel-address">Hủy</button>
            <button class="btn text-white px-4 fw-bold" id="btn-save-address" style="background-color: #022409;">Lưu</button>
        </div>
    </div>
</div>

<?= $clean_footer ?>
<script src="/public/assets/js/Checkout.js"></script>
</body>
</html>