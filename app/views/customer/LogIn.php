<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ===== XỬ LÝ LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identity = $_POST['identity'] ?? '';
    $password = $_POST['password'] ?? '';

    // ❌ KHÔNG CÒN ADMIN FIX CỨNG NỮA

    // 👉 DEMO CUSTOMER LOGIN (tạm thời)
    if (!empty($identity) && !empty($password)) {

      $_SESSION['role'] = 'customer';

$_SESSION['customer_id'] = 1;
$_SESSION['customer_name'] = $identity;

$_SESSION['user'] = [
    'name' => $identity,
    'identity' => $identity
];

        header("Location: " . BASE_URL . "index.php?page=TrangChu");
        exit();
    }

    // sai dữ liệu
    echo "Sai tài khoản hoặc mật khẩu";
    exit();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chào mừng trở lại - Farm2Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
     <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/LogIn.css">
</head>
<body>
<?php include __DIR__ . '/../layouts/header.php'; ?>
  <div class="login-body-content">
      <div class="login-container-wrapper">
          <div class="container">
              
              <div class="login-split-box">
                  <div class="row no-gutters">
                      
                      <div class="col-md-6 col-img-container">
                          <div class="login-image-side"></div>
                      </div>
                      
                      <div class="col-md-6">
                          <div class="login-form-side">
                              
                              <div id="server-alert" class="alert d-none" role="alert"></div>

                              <div id="login-form-section">
                                  <h2 class="form-title">Chào mừng trở lại</h2>
                                  <p class="form-subtitle">Chọn tài khoản của bạn để tiếp tục</p>
                                  
                                  <div class="role-switcher">
                                      <div class="role-tab active" data-role="customer">Khách hàng</div>
                                      <div class="role-tab" data-role="admin">Quản lý</div>
                                  </div>

                                  <form id="formLogIn" method="POST" action="">
                                      <input type="hidden" id="login_role" name="role" value="customer">

                                      <div class="form-group-custom">
                                          <label class="field-label">Số điện thoại hoặc Email</label>
                                          <div class="input-group-custom">
                                              <span class="input-icon"><i class="far fa-user"></i></span>
                                              <input type="text" id="identity" name="identity" class="field-input" placeholder="Nhập SĐT hoặc email...">
                                          </div>
                                          <div class="invalid-msg" id="msg-identity"></div>
                                      </div>

                                      <div class="form-group-custom">
                                          <label class="field-label">Mật khẩu</label>
                                          <div class="input-group-custom">
                                              <span class="input-icon"><i class="fas fa-lock"></i></span>
                                              <input type="password" id="password" name="password" class="field-input" placeholder="••••••••">
                                              <span class="toggle-eye" id="toggle-pwd"><i class="far fa-eye-slash"></i></span>
                                          </div>
                                          <div class="invalid-msg" id="msg-password"></div>
                                      </div>

                                      <div class="d-flex justify-content-between align-items-center mb-3">
                                          <div class="custom-control custom-checkbox">
                                              <input type="checkbox" class="custom-control-input" id="rememberMe">
                                              <label class="custom-control-label text-secondary" style="font-size:0.85rem; cursor:pointer;" for="rememberMe">Ghi nhớ đăng nhập</label>
                                          </div>
                                          <a href="#" id="linkForgotPassword" class="forgot-link-anchor">Quên mật khẩu?</a>
                                      </div>

                                      <button type="submit" id="btnLoginSubmit" class="btn-login-submit">Đăng nhập</button>
                                  </form>
                                  
                                  <p class="text-redirect" id="signup-redirect-text">Bạn chưa có tài khoản? <a href="SignUp.php" class="signup-link-anchor">Đăng ký ngay</a></p>
                              </div>

                              <div id="forgot-form-section" class="d-none">
                                  <h2 class="form-title">Quên mật khẩu</h2>
                                  <p class="form-subtitle">Nhập Gmail để nhận mã OTP xác thực</p>
                                  
                                  <form id="formForgot" novalidate>
                                      <div class="form-group-custom">
                                          <label class="field-label">Địa chỉ Gmail</label>
                                          <div class="input-group-custom">
                                              <span class="input-icon"><i class="far fa-envelope"></i></span>
                                              <input type="email" id="forgot_email" class="field-input" placeholder="example@gmail.com">
                                          </div>
                                          <div class="invalid-msg" id="msg-forgot-email"></div>
                                      </div>
                                      
                                      <button type="submit" id="btnForgotSubmit" class="btn-login-submit">Gửi mã xác thực</button>
                                      <button type="button" class="btn-back-to-login btn-block mt-2">Quay lại</button>
                                  </form>
                              </div>

                              <div id="reset-form-section" class="d-none">
                                  <h2 class="form-title" style="color: #f0a04b;">Đặt lại mật khẩu</h2>
                                  <p class="form-subtitle">Tạo mật khẩu đăng nhập mới an toàn hơn</p>
                                  
                                  <form id="formReset" novalidate>
                                      <div class="form-group-custom">
                                          <label class="field-label">Mã xác thực OTP</label>
                                          <div class="input-group-custom">
                                              <span class="input-icon"><i class="fas fa-shield-alt"></i></span>
                                              <input type="number" id="otp_code" class="field-input text-center font-weight-bold" placeholder="X X X X X X" style="letter-spacing: 3px;">
                                          </div>
                                          <div class="invalid-msg" id="msg-otp"></div>
                                      </div>

                                    <div class="form-group position-relative mb-3">
                                        <label for="new_password">Mật khẩu mới</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Nhập mật khẩu mới">
                                        <span class="toggle-password-reset" data-target="new_password" style="position: absolute; right: 15px; top: 38px; cursor: pointer;">
                                            <i class="far fa-eye-slash"></i>
                                        </span>
                                        <span id="msg-new-password" class="invalid-msg text-danger small"></span>
                                    </div>

                                    <div class="form-group position-relative mb-3">
                                        <label for="confirm_new_password">Xác nhận mật khẩu mới</label>
                                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" placeholder="Xác nhận mật khẩu mới">
                                        <span class="toggle-password-reset" data-target="confirm_new_password" style="position: absolute; right: 15px; top: 38px; cursor: pointer;">
                                            <i class="far fa-eye-slash"></i>
                                        </span>
                                        <span id="msg-confirm-new-password" class="invalid-msg text-danger small"></span>
                                    </div>

                                      <button type="submit" id="btnResetSubmit" class="btn-login-submit" style="background-color: #f0a04b;">Xác nhận đổi mật khẩu</button>
                                  </form>
                              </div>

                          </div>
                      </div>

                  </div>
              </div>
              
          </div>
      </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <script src="<?= BASE_URL ?>assets/js/LogIn.js"></script> -->
</body>
</html>