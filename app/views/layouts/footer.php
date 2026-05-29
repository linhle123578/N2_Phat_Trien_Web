<!-- app/views/layouts/footer.php -->

 <footer class="footer-custom">
    <div class="container">
      <div class="row">
        <div class="col-12 col-md-6 col-lg-5 mb-4 mb-lg-0">
          <img src="../../../Media/Logo-trang.png" alt="Farm2Home" class="footer-logo mb-3">
          <p class="footer-desc">
            Farm2Home mang nông sản sạch, tươi ngon và an toàn đến tận tay bạn,
            để mỗi bữa ăn luôn trọn vẹn sự an tâm và chất lượng.
          </p>
          <?php
          $isLoggedInFooter = isset($_SESSION['user']) || isset($_SESSION['customer_id']);
          if (!$isLoggedInFooter):
          ?>
          <form class="subscribe-form">
            <div class="row no-gutters subscribe-row">
              <div class="col-12 col-sm-auto mt-2 mt-sm-0">
                <a href="../../../app/views/customer/SignUp.php" class="btn btn-subscribe w-100 d-inline-block text-center" style="line-height: 25px;">Đăng ký</a>
              </div>
            </div>
          </form>
          <?php endif; ?>
        </div>
        <div class="col-6 col-md-3 col-lg-3 mb-4 mb-md-0">
          <h5>Liên kết</h5>
          <ul class="list-unstyled">
            <li><a href="../../../app/views/customer/TrangChu.php">Trang Chủ</a></li>
            <li><a href="../../../app/views/customer/Products.php">Sản Phẩm</a></li>
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
        </div>
      </div>
    </div>
  </footer>

</body>
</html>