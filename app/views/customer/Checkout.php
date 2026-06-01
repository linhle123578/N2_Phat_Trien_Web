<?php
ob_start();
include_once __DIR__ . '/../layouts/header.php';
    $extra_head = '
    <link href="https://unpkg.com/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/checkout.css">
    <script>
        const DB_SUBTOTAL = ' . ($subtotal ?? 0) . ';
    </script>
    ';
    echo $extra_head;


    $display_name    = htmlspecialchars($customer_info['fullname'] ?? 'Khách hàng');
    $display_phone   = htmlspecialchars(ltrim($customer_info['phone'] ?? '', '0'));
    $display_address = htmlspecialchars($customer_info['address'] ?? 'Vui lòng cập nhật địa chỉ');
?>

    <main class="checkout-main" style="margin-top: 50px;">
        <div class="container py-5">
            <h2 class="page-title mb-4">Thanh toán</h2>
            
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    
                    <section class="box-yellow mb-4">
                        <h3 class="section-title mb-3">📍 ĐỊA CHỈ NHẬN HÀNG</h3>
                        <div class="inner-card p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <p class="fw-bold mb-1 fs-5" id="display-name"
                                   data-name="<?= $display_name ?>"
                                   data-phone="<?= $display_phone ?>">
                                    <?= $display_name ?>
                                    <?php if ($display_phone): ?>
                                        (+84) <?= $display_phone ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-gray mb-0" id="display-address"
                                   data-address="<?= $display_address ?>">
                                    <?= $display_address ?>
                                </p>
                            </div>
                            <button class="btn btn-link text-success fw-bold text-decoration-none letter-spacing-1 p-0" id="btn-change-address">THAY ĐỔI</button>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="section-title mb-3">SẢN PHẨM</h3>
                        <div class="d-flex flex-column gap-3">
                            
                            <?php if (!empty($checkout_products)): ?>
                                <?php foreach ($checkout_products as $product): 
                                    $img_src = !empty($product['image'])
                                        ? "../../../Media/" . htmlspecialchars($product['image'])
                                        : "../../../Media/no-image.png";
                                ?>
                                    <div class="inner-card p-3">
                                        <div class="row align-items-center g-2">
                                            <!-- Ảnh -->
                                            <div class="col-auto">
                                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img rounded">
                                            </div>
                                            <!-- Tên sản phẩm -->
                                            <div class="col">
                                                <h4 class="fs-6 fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h4>
                                                <?php if (!empty($product['unit'])): ?>
                                                <small class="text-gray">PHÂN LOẠI: <?= htmlspecialchars($product['unit']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Đơn giá -->
                                            <div class="col-auto text-center" style="min-width:100px">
                                                <small class="text-gray d-block">Đơn giá</small>
                                                <span class="fw-semibold"><?= number_format($product['price'], 0, ',', '.') ?>đ</span>
                                            </div>
                                            <!-- Số lượng -->
                                            <div class="col-auto text-center" style="min-width:80px">
                                                <small class="text-gray d-block">Số lượng</small>
                                                <span class="fw-semibold"><?= $product['quantity'] ?></span>
                                            </div>
                                            <!-- Thành tiền -->
                                            <div class="col-auto text-end" style="min-width:110px">
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

                    <section class="box-beige mb-4">
                        <h3 class="section-title mb-3">🚚 PHƯƠNG THỨC VẬN CHUYỂN</h3>
                        <div class="d-flex flex-column gap-3">
                            <?php if(!empty($shipments)): ?>
                                <?php foreach($shipments as $index => $shp): 
                                    $price = (float)$shp['price'];
                                    $isChecked = ($index === 0) ? 'checked' : '';
                                    $isActive = ($index === 0) ? 'active' : '';
                                ?>
                                <label class="inner-card radio-card <?= $isActive ?> p-3 d-flex align-items-center gap-3 m-0">
                                    <input type="radio" name="shipping" value="<?= $shp['shipment_id'] ?>" data-price="<?= $price ?>" <?= $isChecked ?>>
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h4 class="fs-6 fw-bold m-0" style="color: #022409;"><?= htmlspecialchars($shp['shipment_method']) ?></h4>
                                                <?php if($index === 0): ?>
                                                <span class="badge text-dark" style="background-color: #F1BF4F; font-size: 10px;">RẺ NHẤT</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-gray"><?= htmlspecialchars($shp['description'] ?? '') ?></small>
                                        </div>
                                        <span class="fw-bold" style="color: #183A1D;"><?= number_format($price, 0, ',', '.') ?>đ</span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">Không có phương thức giao hàng nào!</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h3 class="section-title mb-3">PHƯƠNG THỨC THANH TOÁN</h3>
                        <div class="d-flex flex-column gap-3">
                            <label class="inner-card radio-card active p-3 d-flex align-items-center gap-3 m-0">
                                <input type="radio" name="payment" value="momo" checked>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-4">🟣</span>
                                    <div>
                                        <h4 class="fs-6 fw-bold mb-1">Thanh toán qua MoMo</h4>
                                        <small class="text-gray">Liên kết với ví MoMo</small>
                                    </div>
                                </div>
                            </label>
                            <label class="inner-card radio-card p-3 d-flex align-items-center gap-3 m-0">
                                <input type="radio" name="payment" value="cod">
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

    <div class="modal-overlay" id="address-modal">
        <div class="modal-content-custom" style="max-height: 90vh; overflow-y: auto; max-width: 600px;">
            <h3 class="mb-4 fw-bold" style="font-family: 'Plus Jakarta Sans', sans-serif;">Thay đổi địa chỉ</h3>

            <!-- Danh sách địa chỉ đã lưu -->
            <div id="address-list-view">
                <?php if (!empty($all_addresses)): ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($all_addresses as $addr): 
                            $full_str = implode(', ', array_filter([$addr['street_address']??'', $addr['ward']??'', $addr['district']??'', $addr['province']??'']));
                            $is_selected = ($addr['address_id'] === ($customer_info['address_id'] ?? ''));
                        ?>
                        <label class="inner-card radio-card p-3 d-flex align-items-start gap-3 m-0 <?= $is_selected ? 'active' : '' ?>">
                            <input type="radio" name="selected_address_id" value="<?= htmlspecialchars($addr['address_id']) ?>" <?= $is_selected ? 'checked' : '' ?>
                                   data-name="<?= htmlspecialchars($addr['receiver_name']) ?>"
                                   data-phone=""
                                   data-full="<?= htmlspecialchars($full_str) ?>">
                            <div class="w-100">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h4 class="fs-6 fw-bold m-0" style="color: #022409;"><?= htmlspecialchars($addr['receiver_name']) ?></h4>
                                    <?php if ($addr['is_default']): ?>
                                        <span class="badge bg-danger" style="font-size: 10px;">Mặc định</span>
                                    <?php endif; ?>
                                    <?php if (!empty($addr['address_type'])): ?>
                                        <span class="badge bg-secondary" style="font-size: 10px;"><?= htmlspecialchars($addr['address_type']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-gray d-block"><?= htmlspecialchars($full_str) ?></small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Bạn chưa có địa chỉ giao hàng nào.</div>
                <?php endif; ?>
                
                <button type="button" class="btn btn-outline-success w-100 fw-bold mb-4" id="btn-show-add-form">
                    + Thêm địa chỉ mới
                </button>
                
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-secondary px-4" id="btn-cancel-address">Hủy</button>
                    <button class="btn text-white px-4 fw-bold" id="btn-save-address" style="background-color: #022409;">Lưu</button>
                </div>
            </div>

            <!-- Form thêm địa chỉ mới -->
            <div id="address-form-view" style="display: none;">
                <div class="mb-3">
                    <label class="form-label fw-bold">Người nhận <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="new-addr-name" placeholder="Tên người nhận">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="new-addr-phone" placeholder="Số điện thoại người nhận">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Loại địa chỉ</label>
                    <select class="form-control form-select" id="new-addr-type">
                        <option value="Nhà riêng">Nhà riêng</option>
                        <option value="Văn phòng">Văn phòng</option>
                        <option value="Cơ quan">Cơ quan</option>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-bold">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-addr-province" placeholder="VD: TP. Hồ Chí Minh">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold">Quận / Huyện</label>
                        <input type="text" class="form-control" id="new-addr-district" placeholder="VD: Quận 1">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold">Phường / Xã</label>
                        <input type="text" class="form-control" id="new-addr-ward" placeholder="VD: Phường Bến Nghé">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold">Số nhà, tên đường <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new-addr-street" placeholder="VD: 123 Nguyễn Huệ">
                    </div>
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="new-addr-default" value="1">
                    <label class="form-check-label" for="new-addr-default">Đặt làm địa chỉ mặc định</label>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-secondary px-4" id="btn-cancel-add-form">Trở lại</button>
                    <button class="btn text-white px-4 fw-bold" id="btn-confirm-add-address" style="background-color: #022409;">Xác nhận</button>
                </div>
            </div>

        </div>
    </div>

    <?php include __DIR__ . '/../layouts/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/checkout.js"></script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
