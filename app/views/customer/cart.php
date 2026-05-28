<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../../public/index.php?page=login");
    exit();
}


require_once __DIR__ . "/../../models/CartModel.php";



// 1. XỬ LÝ AJAX XÓA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'])) {
    $cartModel = new CartModel();
    $result = $cartModel->deleteItem($_POST['cart_item_id']);
    if ($result) { http_response_code(200); echo "Xóa thành công"; }
    else { http_response_code(500); echo "Lỗi xóa"; }
    exit();
}

// 2. LẤY DỮ LIỆU TỪ DB 
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../../public/index.php?page=login");
    exit();
}
$customer_id = $_SESSION['customer_id'];

$cartModel = new CartModel();
$items = $cartModel->getCartItems($customer_id);
$total_items = count($items);

?>
<link rel="stylesheet" href="../../../public/assets/css/cart.css">

  <main class="cart-main">
    <div class="container">
      <div class="row">

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
                <a href="../../../public/index.php?page=products" class="back-link">
                  <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
              </div>

            <?php else: ?>
              <?php foreach ($items as $item):
                $thanh_tien = $item['unit_price'] * $item['quantity'];
                
                // XỬ LÝ ẢNH SẢN PHẨM 
                // Đã sửa lại đường dẫn và không tự ý nối thêm đuôi .jpg vì database đã lưu sẵn đuôi ảnh
                $img_src = !empty($item['product_image'])
                 ? "/Media/" . htmlspecialchars($item['product_image'])
                : "/Media/no-image.png";
              ?>

              <div class="cart-item"
                   data-id="<?= $item['product_id'] ?>"
                   data-price="<?= $item['unit_price'] ?>"
                   data-qty="<?= $item['quantity'] ?>"
                   data-stock="<?= $item['stock'] ?>">
                <div class="row no-gutters align-items-start">
                  
                  <div class="col-auto pr-3">
                    <input type="checkbox" checked class="item-checkbox" name="selected[]" value="<?= $item['product_id'] ?>">
                  </div>

                  <div class="col-auto pr-3">
                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="cart-item-img">
                  </div>

                  <div class="col">
                    <div class="cart-item-info">
                      <div class="cart-item-header">
                        <div>
                          <p class="cart-item-name"><?= htmlspecialchars($item['product_name']) ?></p>
                          <p class="cart-item-origin">
                            <i class="fas fa-tag"></i> Còn <?= $item['stock'] ?> sản phẩm
                          </p>
                        </div>
                        <button class="delete-btn" data-id="<?= $item['cart_item_id'] ?>" title="Xóa khỏi giỏ hàng">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>

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
        </div>

        <div class="col-12 col-lg-4">
          <div class="summary-sticky mt-4 mt-lg-0">
            <div class="summary-card">
              <h2 class="summary-title">Tóm tắt đơn hàng</h2>

              <div class="summary-breakdown">
                <div class="summary-row">
                  <span id="summary-label">Tạm tính (0 món)</span>
                  <span id="summary-subtotal">0đ</span>
                </div>
              </div>

              <div class="summary-total-row">
                <span class="summary-total-label">Tổng cộng</span>
                <span class="summary-total-value" id="summary-total">30.000đ</span>
              </div>

              <form id="checkout-form" action="../../../app/controllers/customer/CartController.php" method="POST">
                <input type="hidden" name="action" value="dat_hang">
                <div id="selected-inputs"></div>
                <button type="submit" class="btn-checkout">Tiến hành thanh toán</button>
              </form>
            </div>

            <div class="trust-badges mt-3">
              <div class="trust-badge"><i class="fas fa-certificate"></i><span>VietGAP</span></div>
              <div class="trust-badge"><i class="fas fa-leaf"></i><span>OCOP</span></div>
              <div class="trust-badge"><i class="fas fa-truck"></i><span>Giao 24h</span></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <script src="../../../public/assets/js/cart.js"></script>
  
  <script>
  document.getElementById('checkout-form').addEventListener('submit', function(e) {
    const container = document.getElementById('selected-inputs');
    container.innerHTML = '';
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
      e.preventDefault();
      alert('Vui lòng chọn ít nhất một sản phẩm để đặt hàng.');
      return;
    }
    
    if (!confirm(`Bạn có chắc chắn muốn tiến hành thanh toán cho ${checked.length} sản phẩm đã chọn?`)) {
      e.preventDefault();
      return;
    }
    checked.forEach(function(chk) {
      const item = chk.closest('.cart-item');
      const inp = document.createElement('input');
      inp.type  = 'hidden'; inp.name  = 'selected[]'; inp.value = item.dataset.id;
      container.appendChild(inp);
      const inpQty  = document.createElement('input');
      inpQty.type   = 'hidden'; inpQty.name   = 'qty[' + item.dataset.id + ']'; inpQty.value  = item.dataset.qty;
      container.appendChild(inpQty);
    });
  });
  </script>