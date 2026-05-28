document.addEventListener("DOMContentLoaded", function () {
    const serverAlert = document.getElementById("server-alert");

    // Các thành phần form section điều khiển view ẩn/hiện
    const loginSection = document.getElementById("login-form-section");
    const forgotSection = document.getElementById("forgot-form-section");
    const resetSection = document.getElementById("reset-form-section");

    // Link chuyển form trợ giúp nhanh
    const linkForgot = document.getElementById("linkForgotPassword");
    const btnBackToLogin = document.querySelectorAll(".btn-back-to-login");

    // ── 1. ĐIỀU KHIỂN CHUYỂN ĐỔI FORM (GIAO DIỆN)
    if (linkForgot) {
        linkForgot.addEventListener("click", function (e) {
            e.preventDefault();
            serverAlert.className = "alert d-none"; // Reset thông báo cũ
            loginSection.classList.add("d-none");
            forgotSection.classList.remove("d-none");
        });
    }

    btnBackToLogin.forEach(btn => {
        btn.addEventListener("click", function () {
            serverAlert.className = "alert d-none";
            forgotSection.classList.add("d-none");
            resetSection.classList.add("d-none");
            loginSection.classList.remove("d-none");
        });
    });

// ── 2. XỬ LÝ ẨN / HIỆN MẬT KHẨU KHÁCH HÀNG (CẢ ĐĂNG NHẬP VÀ ĐỔI MẬT KHẨU)
    // 2.1 Cho ô Đăng nhập cũ
    const togglePwd = document.getElementById("toggle-pwd");
    if (togglePwd) {
        togglePwd.addEventListener("click", function () {
            const pwdInput = document.getElementById("password");
            const eyeIcon = this.querySelector("i");
            if (pwdInput.type === "password") {
                pwdInput.type = "text";
                eyeIcon.className = "far fa-eye";
            } else {
                pwdInput.type = "password";
                eyeIcon.className = "far fa-eye-slash";
            }
        });
    }

    // 2. Xử lý Ẩn/Hiện Mật khẩu
    const toggleResetPwds = document.querySelectorAll(".toggle-password-reset");
    toggleResetPwds.forEach(btn => {
        btn.addEventListener("click", function () {
            const targetId = this.getAttribute("data-target"); // Lấy id của ô input cần ẩn/hiện
            const pwdInput = document.getElementById(targetId);
            const eyeIcon = this.querySelector("i");

            if (pwdInput && pwdInput.type === "password") {
                pwdInput.type = "text";
                eyeIcon.className = "far fa-eye"; // Đổi thành mắt mở
            } else if (pwdInput) {
                pwdInput.type = "password";
                eyeIcon.className = "far fa-eye-slash"; // Đổi thành mắt đóng
            }
        });
    });

    // ── 3. AJAX ĐĂNG NHẬP HỆ THỐNG
    const formLogIn = document.getElementById("formLogIn");
    if (formLogIn) {
        formLogIn.addEventListener("submit", function (e) {
            e.preventDefault();
            
            // Xóa sạch các thông báo lỗi Validation cũ
            document.querySelectorAll(".invalid-msg").forEach(el => el.textContent = "");
            serverAlert.className = "alert d-none";

            const identity = document.getElementById("identity").value.trim();
            const password = document.getElementById("password").value;
            let hasError = false;

            if (identity === "") {
                document.getElementById("msg-identity").textContent = "Vui lòng nhập Email hoặc Số điện thoại.";
                hasError = true;
            }
            if (password === "") {
                document.getElementById("msg-password").textContent = "Vui lòng nhập mật khẩu của bạn.";
                hasError = true;
            }

            if (hasError) return;

            const submitBtn = document.getElementById("btnLoginSubmit");
            submitBtn.disabled = true;
            submitBtn.textContent = "Đang xử lý...";

            const formData = new FormData(formLogIn);

            fetch("../app/controllers/customer/LogInController.php?action=login", {
                method: "POST",
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error("Lỗi kết nối Server.");
                return res.json();
            })
            .then(data => {
                serverAlert.classList.remove("d-none");
                if (data.status === "success") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
                    // Đăng nhập khách hàng -> Trang chủ
                    setTimeout(() => { window.location.href = "index.php?page=TrangChu"; }, 1200);
                } else if (data.status === "admin") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
                    // Đăng nhập admin -> Dashboard admin
                    setTimeout(() => { window.location.href = "../app/controllers/admin/DashboardController.php"; }, 1200);
                } else {
                    serverAlert.className = "alert alert-danger";
                    serverAlert.textContent = data.message;
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Đăng nhập";
                }
            })
            .catch(err => {
                console.error(err);
                serverAlert.className = "alert alert-danger";
                serverAlert.textContent = "Không thể kết nối với hệ thống Cloud.";
                submitBtn.disabled = false;
                submitBtn.textContent = "Đăng nhập";
            });
        });
    }

    // ── 4. AJAX YÊU CẦU GỬI OTP QUÊN MẬT KHẨU KHÁCH HÀNG
    const formForgot = document.getElementById("formForgot");
    if (formForgot) {
        formForgot.addEventListener("submit", function (e) {
            e.preventDefault();
            document.getElementById("msg-forgot-email").textContent = "";
            serverAlert.className = "alert d-none";

            const email = document.getElementById("forgot_email").value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById("msg-forgot-email").textContent = "Định dạng địa chỉ Email không đúng.";
                return;
            }

            const btnForgot = document.getElementById("btnForgotSubmit");
            btnForgot.disabled = true;
            btnForgot.textContent = "Đang gửi OTP...";

            fetch("../app/controllers/customer/LogInController.php?action=forgot", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "email=" + encodeURIComponent(email)
            })
            .then(res => res.json())
            .then(data => {
                serverAlert.classList.remove("d-none");
                if (data.status === "otp_sent") {
                    serverAlert.className = "alert alert-warning";
                    serverAlert.textContent = data.message;
                    
                    // Chuyển sang Form nhập mã OTP đặt lại mật khẩu mới
                    forgotSection.classList.add("d-none");
                    resetSection.classList.remove("d-none");
                } else {
                    serverAlert.className = "alert alert-danger";
                    serverAlert.textContent = data.message;
                    btnForgot.disabled = false;
                    btnForgot.textContent = "Gửi mã xác thực";
                }
            })
            .catch(err => {
                console.error(err);
                btnForgot.disabled = false;
                btnForgot.textContent = "Gửi mã xác thực";
            });
        });
    }

    // ── 5. AJAX XÁC NHẬN OTP VÀ LƯU LẠI MẬT KHẨU MỚI HOÀN TẤT
    const formReset = document.getElementById("formReset");
    if (formReset) {
        formReset.addEventListener("submit", function (e) {
            e.preventDefault();
            document.querySelectorAll(".invalid-msg").forEach(el => el.textContent = "");
            serverAlert.className = "alert d-none";

            const otp = document.getElementById("otp_code").value.trim();
            const newPass = document.getElementById("new_password").value;
            const confirmPass = document.getElementById("confirm_new_password").value;
            let hasError = false;

            if (otp.length !== 6) {
                document.getElementById("msg-otp").textContent = "Vui lòng nhập mã OTP gồm 6 chữ số.";
                hasError = true;
            }
            if (newPass.length < 6) {
                document.getElementById("msg-new-password").textContent = "Mật khẩu mới chứa tối thiểu từ 6 ký tự.";
                hasError = true;
            }
            if (newPass !== confirmPass) {
                document.getElementById("msg-confirm-new-password").textContent = "Xác nhận mật khẩu mới không khớp.";
                hasError = true;
            }

            if (hasError) return;

            const btnReset = document.getElementById("btnResetSubmit");
            btnReset.disabled = true;
            btnReset.textContent = "Đang cập nhật...";

            fetch("../app/controllers/customer/LogInController.php?action=reset", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `otp=${encodeURIComponent(otp)}&new_password=${encodeURIComponent(newPass)}&confirm_password=${encodeURIComponent(confirmPass)}`
            })
            .then(res => res.json())
            .then(data => {
                serverAlert.classList.remove("d-none");
                if (data.status === "success") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
                    
                    // Thành công hoàn toàn -> Đưa quay về Form đăng nhập chính
                    setTimeout(() => {
                        resetSection.classList.add("d-none");
                        loginSection.classList.remove("d-none");
                        formLogIn.reset();
                    }, 2000);
                } else {
                    serverAlert.className = "alert alert-danger";
                    serverAlert.textContent = data.message;
                    btnReset.disabled = false;
                    btnReset.textContent = "Lưu thay đổi & Đăng nhập";
                }
            })
            .catch(err => {
                console.error(err);
                btnReset.disabled = false;
                btnReset.textContent = "Lưu thay đổi & Đăng nhập";
            });
        });
    }
});

    // ── 6. CHỌN PHÂN HỆ KHÁCH HÀNG / QUẢN LÝ
    $(document).ready(function() {
    // Xử lý sự kiện click chuyển đổi tab giữa Người dùng và Quản lý
    $('.role-tab').on('click', function() {
        
        // Loại bỏ trạng thái active của tab cũ và thêm vào tab vừa click
        $('.role-tab').removeClass('active');
        $(this).addClass('active');
        
        // Lấy giá trị vai trò (customer hoặc admin)
        const selectedRole = $(this).data('role');
        
        // Cập nhật giá trị vào input ẩn trong form để gửi lên PHP xử lý
        $('#login_role').val(selectedRole);
        
        // Nếu chọn Quản lý thì ẩn dòng "Đăng ký ngay" đi, chọn Người dùng thì hiện lại
        if (selectedRole === 'admin') {
            $('#signup-redirect-text').addClass('d-none');
        } else {
            $('#signup-redirect-text').removeClass('d-none');
        }
    });
});