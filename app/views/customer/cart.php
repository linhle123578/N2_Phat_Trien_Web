<?php
session_start();

// Kiểm tra đăng nhập
//if (!isset($_SESSION['customer_id'])) {
//    header("Location: login.php");
//    exit();
//}

$customer_id = 'CUS001'; //ví dụ vì chưa có login

// Kết nối DB
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
mysqli_set_charset($conn, "utf8");

// TRUY VẤN: Lấy các sản phẩm trong giỏ hàng của KH
$sql = "SELECT
            ci.cart_item_id,
            ci.quantity,
            ci.unit_price,
            p.product_name,
            p.product_image,
            p.stock
        FROM cart c
        JOIN cartitem ci ON c.cart_id = ci.cart_id
        JOIN product p   ON ci.product_id = p.product_id
        WHERE c.customer_id = '$customer_id'
        ORDER BY ci.cart_item_id";

$result = mysqli_query($conn, $sql);
$items  = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}
$total_items = count($items);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ Hàng - Farm2Home</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../../public/assets/css/Giỏ hàng.css">
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg custom-navbar fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">
        <img src="../../../Media/Logo.png" alt="Farm2Home">
      </a>
      <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarMain">
        <i class="fas fa-bars" style="color:#183a1d;"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarMain">
        <ul class="navbar-nav mx-auto mt-2 mt-lg-0">
          <li class="nav-item active"><a class="nav-link" href="#">Trang Chủ</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Sản Phẩm</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Blog</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Về Chúng Tôi</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Liên Hệ</a></li>
        </ul>
        <div class="nav-right-actions mt-2 mt-lg-0">
          <a href="#" class="action-icon">
            <i class="far fa-bell"></i>
            <span class="icon-badge">3</span>
          </a>
          <a href="#" class="action-icon">
            <i class="fas fa-shopping-cart"></i>
            <span class="icon-badge" id="cart-count"><?= $total_items ?></span>
          </a>
          <div class="nav-divider d-none d-lg-block"></div>
          <a href="#" class="action-icon user-avatar-icon">
            <i class="far fa-user"></i>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- MAIN -->
  <main class="cart-main">
    <div class="container">
      <div class="row">

        <!-- LEFT: CART ITEMS -->
        <div class="col-12 col-lg-8 mb-4 mb-lg-0">
          <h1 class="cart-page-title mb-4">
            Giỏ hàng của bạn
            <small class="cart-page-count">(<?= $total_items ?> sản phẩm)</small>
          </h1>

          <div id="cart-items">
            <?php if (empty($items)): ?>
              <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3" style="color:#ccc;"></i>
                <p style="color:#6c757d; font-size:1rem;">Giỏ hàng của bạn đang trống.</p>
                <a href="index.php" class="back-link">
                  <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
              </div>

            <?php else: ?>
              <?php foreach ($items as $item):
                $thanh_tien = $item['unit_price'] * $item['quantity'];
                $img_src    = !empty($item['product_image'])
                              ? htmlspecialchars($item['product_image'])
                              : '../../../Media/no-image.png';
              ?>

              <div class="cart-item"
                   data-id="<?= $item['cart_item_id'] ?>"
                   data-price="<?= $item['unit_price'] ?>"
                   data-qty="<?= $item['quantity'] ?>"
                   data-stock="<?= $item['stock'] ?>">
                <div class="row no-gutters align-items-start">

                  <!-- Checkbox chọn mua -->
                  <div class="col-auto pr-3">
                    <input type="checkbox" checked class="item-checkbox">
                  </div>

                  <!-- Ảnh sản phẩm -->
                  <div class="col-auto pr-3">
                    <img src="<?= $img_src ?>"
                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                         class="cart-item-img">
                  </div>

                  <!-- Thông tin -->
                  <div class="col">
                    <div class="cart-item-info">

                      <!-- Tên + nút xóa -->
                      <div class="cart-item-header">
                        <div>
                          <p class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></p>
                          <p class="cart-item-origin">
                            <i class="fas fa-tag"></i> Còn <?= $item['stock'] ?> sản phẩm
                          </p>
                        </div>
                        <button class="delete-btn"
                                data-id="<?= $item['cart_item_id'] ?>"
                                title="Xóa khỏi giỏ hàng">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>

                      <!-- Số lượng + giá -->
                      <div class="cart-item-footer row align-items-center">
                        <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                          <div class="qty-control">
                            <button class="qty-minus"><i class="fas fa-minus"></i></button>
                            <span class="qty-display"><?= $item['quantity'] ?></span>
                            <button class="qty-plus"><i class="fas fa-plus"></i></button>
                          </div>
                        </div>
                        <div class="col-12 col-sm text-sm-right">
                          <div class="cart-item-price">
                            <p class="unit-price"><?= number_format($item['unit_price'], 0, ',', '.') ?>đ</p>
                            <p class="item-total"><?= number_format($thanh_tien, 0, ',', '.') ?>đ</p>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <a href="index.php" class="back-link mt-3">
            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
          </a>
        </div>

        <!-- RIGHT: SUMMARY -->
        <div class="col-12 col-lg-4">
          <div class="summary-sticky mt-4 mt-lg-0">
            <div class="summary-card">
              <h2 class="summary-title">Tóm tắt đơn hàng</h2>

              <!-- Coupon -->
              <div class="mb-4">
                <label class="summary-label-small">Mã giảm giá</label>
                <div class="row no-gutters coupon-row">
                  <div class="col-12 col-sm">
                    <input class="coupon-input" placeholder="Nhập mã..." type="text">
                  </div>
                  <div class="col-12 col-sm-auto mt-2 mt-sm-0">
                    <button class="btn-coupon w-100">Áp dụng</button>
                  </div>
                </div>
              </div>

              <!-- Breakdown -->
              <div class="summary-breakdown">
                <div class="summary-row">
                  <span id="summary-label">Tạm tính (0 món)</span>
                  <span id="summary-subtotal">0đ</span>
                </div>
                <div class="summary-row">
                  <span>Phí vận chuyển</span>
                  <span id="summary-shipping">30.000đ</span>
                </div>
              </div>

              <!-- Total -->
              <div class="summary-total-row">
                <span class="summary-total-label">Tổng cộng</span>
                <span class="summary-total-value" id="summary-total">30.000đ</span>
              </div>

              <!-- Form đặt hàng -->
              <form method="post" action="../../../models/ProductModel.php" id="form-checkout">
                <input type="hidden" name="action" value="dat_hang">
                <div id="selected-inputs">
                  <!-- JS inject hidden inputs vào đây khi submit -->
                </div>
                <button type="submit" class="btn-checkout">Tiến hành thanh toán</button>
              </form>
            </div>

            <!-- Trust badges -->
            <div class="trust-badges mt-3">
              <div class="trust-badge">
                <i class="fas fa-certificate"></i><span>VietGAP</span>
              </div>
              <div class="trust-badge">
                <i class="fas fa-leaf"></i><span>OCOP</span>
              </div>
              <div class="trust-badge">
                <i class="fas fa-truck"></i><span>Giao 24h</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="footer-custom">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6 col-lg-5 mb-4 mb-lg-0">
          <img src="../Media/Logo-trang.png" alt="Farm2Home" class="footer-logo mb-3">
          <p class="footer-desc">
            Farm2Home mang nông sản sạch, tươi ngon và an toàn đến tận tay bạn,
            để mỗi bữa ăn luôn trọn vẹn sự an tâm và chất lượng.
          </p>
          <form class="subscribe-form">
            <div class="row no-gutters subscribe-row">
              <div class="col-12 col-sm">
                <input type="email" class="form-control subscribe-input" placeholder="Email của bạn...">
              </div>
              <div class="col-12 col-sm-auto mt-2 mt-sm-0">
                <button type="button" class="btn btn-subscribe w-100">Đăng ký</button>
              </div>
            </div>
          </form>
        </div>
        <div class="col-6 col-md-3 col-lg-3 mb-4 mb-md-0">
          <h5>Liên kết</h5>
          <ul class="list-unstyled">
            <li><a href="#">Trang Chủ</a></li>
            <li><a href="#">Sản Phẩm</a></li>
            <li><a href="#">Về Chúng Tôi</a></li>
            <li><a href="#">Liên Hệ</a></li>
            <li><a href="#">Chính Sách Bảo Mật</a></li>
            <li><a href="#">Điều Khoản Sử Dụng</a></li>
          </ul>
        </div>
        <div class="col-6 col-md-3 col-lg-4">
          <h5>Liên hệ</h5>
          <ul class="list-unstyled mb-4">
            <li><i class="fas fa-phone-alt"></i> 1800 6868</li>
            <li><i class="far fa-envelope"></i> support@farm2home.vn</li>
            <li><i class="fas fa-map-marker-alt"></i> 123 Nguyễn Huệ, Quận 1, TP.HCM</li>
          </ul>
          <div>
            <span class="footer-badge">VietGAP</span>
            <span class="footer-badge">GlobalGAP</span>
            <span class="footer-badge">OCOP</span>
            <span class="footer-badge">ISO 22000</span>
          </div>
        </div>
      </div>
      <hr class="footer-divider">
      <div class="row align-items-center footer-bottom">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
          <div class="social-icons text-center text-md-left">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
        <div class="col-12 col-md-4 mb-3 mb-md-0 text-center">
          &copy; 2026 Farm2Home. Tất cả quyền được bảo lưu.
        </div>
        <div class="col-12 col-md-4 text-center text-md-right">
          <span>Thanh toán an toàn:</span>
          <span class="footer-badge ml-1" style="font-size:.75rem;">MoMo</span>
          <span class="footer-badge" style="font-size:.75rem;">VNPay</span>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../public/assets/js/Giỏ hàng.js"></script>

  <script>
  // ── Nút Tiến hành thanh toán: inject hidden inputs từ checkbox đang checked ──
  document.getElementById('form-checkout').addEventListener('submit', function(e) {
    const container = document.getElementById('selected-inputs');
    container.innerHTML = '';

    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
      e.preventDefault();
      alert('Vui lòng chọn ít nhất một sản phẩm để đặt hàng.');
      return;
    }

    checked.forEach(function(chk) {
      const item = chk.closest('.cart-item');

      // cart_item_id
      const inp = document.createElement('input');
      inp.type  = 'hidden';
      inp.name  = 'selected[]';
      inp.value = item.dataset.id;
      container.appendChild(inp);

      // số lượng hiện tại (đã được JS giohang.js cập nhật vào data-qty)
      const inpQty  = document.createElement('input');
      inpQty.type   = 'hidden';
      inpQty.name   = 'qty[' + item.dataset.id + ']';
      inpQty.value  = item.dataset.qty;
      container.appendChild(inpQty);
    });
  });

  // ── Nút xóa: gửi POST fetch → reload để giohang.js không cần sửa ──
  document.getElementById('cart-items').addEventListener('click', function(e) {
    const btn = e.target.closest('.delete-btn');
    if (!btn) return;
    if (!confirm('Xóa sản phẩm này khỏi giỏ hàng?')) return;

    fetch('../../../models/ProductModel.php', {
      method : 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body   : 'action=xoa&cart_item_id=' + btn.dataset.id
    }).then(() => location.reload());
  });
  </script>

</body>
</html>
<?php mysqli_close($conn); ?>
