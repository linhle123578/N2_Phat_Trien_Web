<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start();
include_once __DIR__ . '/../layouts/header.php';
$raw_header = ob_get_clean();
preg_match('/<nav.*?>.*?<\/nav>/is', $raw_header, $m_head);
$clean_header = $m_head[0] ?? '';

ob_start();
include_once __DIR__ . '/../layouts/footer.php';
$raw_footer = ob_get_clean();
preg_match('/<footer.*?>.*?<\/footer>/is', $raw_footer, $m_foot);
$clean_footer = $m_foot[0] ?? '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tạo tài khoản - Farm2Home</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/SignUp.css">
</head>
<body class="signup-page">

  <?= $clean_header ?>

  <main class="signup-wrapper d-flex align-items-center justify-content-center">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7">
          
          <div class="signup-card">
            <h1 class="form-title text-center mb-1">Tạo tài khoản</h1>
            <p class="form-subtitle text-center mb-4 pb-2">Tham gia cùng cộng đồng thực phẩm sạch Farm2Home.</p>

            <form id="formSignUp" novalidate autocomplete="off">
              
              <div class="form-group mb-3">
                <label class="form-label field-label">Họ và tên</label>
                <div class="input-group-custom">
                  <span class="input-icon"><i class="far fa-user"></i></span>
                  <input type="text" name="fullname" id="fullname" class="form-control field-input" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="invalid-msg" id="msg-fullname"></div>
              </div>

              <div class="form-group mb-3 pt-1">
                <label class="form-label field-label d-block">Giới tính</label>
                <div class="d-flex align-items-center">
                  <div class="custom-control custom-radio mr-4">
                    <input type="radio" id="genderNam" name="gender" value="Nam" class="custom-control-input" checked>
                    <label class="custom-control-label" for="genderNam">Nam</label>
                  </div>
                  <div class="custom-control custom-radio mr-4">
                    <input type="radio" id="genderNu" name="gender" value="Nữ" class="custom-control-input">
                    <label class="custom-control-label" for="genderNu">Nữ</label>
                  </div>
                  <div class="custom-control custom-radio">
                    <input type="radio" id="genderKhac" name="gender" value="Khác" class="custom-control-input">
                    <label class="custom-control-label" for="genderKhac">Khác</label>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 col-md-6 form-group mb-3">
                  <label class="form-label field-label">Số điện thoại</label>
                  <div class="input-group-custom position-relative">
                    <span class="input-icon"><i class="fas fa-phone-alt"></i></span>
                    <input type="tel" name="phone" id="phone" class="form-control field-input" placeholder="0123 456 789" required>
                    <span class="status-badge" id="phone-check-icon"></span>
                  </div>
                  <div class="invalid-msg" id="msg-phone"></div>
                </div>
                
                <div class="col-12 col-md-6 form-group mb-3">
                  <label class="form-label field-label">Email</label>
                  <div class="input-group-custom">
                    <span class="input-icon"><i class="far fa-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control field-input" placeholder="example@email.com" required>
                  </div>
                  <div class="invalid-msg" id="msg-email"></div>
                </div>
              </div>

              <div class="row">
                <div class="col-12 col-md-6 form-group mb-3">
                  <label class="form-label field-label">Mật khẩu</label>
                  <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control field-input" placeholder="••••••••" required>
                    <span class="toggle-eye" id="toggle-pwd-1"><i class="far fa-eye-slash"></i></span>
                  </div>
                  <div class="invalid-msg" id="msg-password"></div>
                </div>

                <div class="col-12 col-md-6 form-group mb-3">
                  <label class="form-label field-label">Xác nhận mật khẩu</label>
                  <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-check-circle"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control field-input" placeholder="••••••••" required>
                    <span class="toggle-eye" id="toggle-pwd-2"><i class="far fa-eye-slash"></i></span>
                  </div>
                  <div class="invalid-msg" id="msg-confirm-password"></div>
                </div>
              </div>

              <div class="form-group my-4 pt-1">
                <div class="custom-control custom-checkbox text-left">
                  <input type="checkbox" class="custom-control-input" id="agreeTerms" name="agree">
                  <label class="custom-control-label terms-text" for="agreeTerms">Tôi đồng ý với các điều khoản và điều kiện của Farm2Home</label>
                </div>
                <div class="invalid-msg" id="msg-agree"></div>
              </div>

              <button type="submit" class="btn btn-signup-submit btn-block" id="btnSubmitForm">Đăng ký</button>

              <div id="server-alert" class="alert d-none mt-3 text-center" role="alert"></div>
            </form>

            <div class="text-center mt-4 pt-1 text-redirect">
              Đã có tài khoản? <a href="../../../app/views/customer/LogIn.php" class="login-link-anchor">Đăng nhập</a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>

  <?= $clean_footer ?>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../../public/assets/js/SignUp.js"></script>
</body>
</html>