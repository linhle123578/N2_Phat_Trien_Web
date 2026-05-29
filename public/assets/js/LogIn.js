document.addEventListener("DOMContentLoaded", function () {
    const serverAlert = document.getElementById("server-alert");

    // CÃ¡c thÃ nh pháº§n form section Ä‘iá»u khiá»ƒn view áº©n/hiá»‡n
    const loginSection = document.getElementById("login-form-section");
    const forgotSection = document.getElementById("forgot-form-section");
    const resetSection = document.getElementById("reset-form-section");

    // Link chuyá»ƒn form trá»£ giÃºp nhanh
    const linkForgot = document.getElementById("linkForgotPassword");
    const btnBackToLogin = document.querySelectorAll(".btn-back-to-login");

    // â”€â”€ 1. ÄIá»€U KHIá»‚N CHUYá»‚N Äá»”I FORM (GIAO DIá»†N)
    if (linkForgot) {
        linkForgot.addEventListener("click", function (e) {
            e.preventDefault();
            serverAlert.className = "alert d-none"; // Reset thÃ´ng bÃ¡o cÅ©
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

// â”€â”€ 2. Xá»¬ LÃ áº¨N / HIá»†N Máº¬T KHáº¨U KHÃCH HÃ€NG (Cáº¢ ÄÄ‚NG NHáº¬P VÃ€ Äá»”I Máº¬T KHáº¨U)
    // 2.1 Cho Ã´ ÄÄƒng nháº­p cÅ©
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

    // 2. Xá»­ lÃ½ áº¨n/Hiá»‡n Máº­t kháº©u
    const toggleResetPwds = document.querySelectorAll(".toggle-password-reset");
    toggleResetPwds.forEach(btn => {
        btn.addEventListener("click", function () {
            const targetId = this.getAttribute("data-target"); // Láº¥y id cá»§a Ã´ input cáº§n áº©n/hiá»‡n
            const pwdInput = document.getElementById(targetId);
            const eyeIcon = this.querySelector("i");

            if (pwdInput && pwdInput.type === "password") {
                pwdInput.type = "text";
                eyeIcon.className = "far fa-eye"; // Äá»•i thÃ nh máº¯t má»Ÿ
            } else if (pwdInput) {
                pwdInput.type = "password";
                eyeIcon.className = "far fa-eye-slash"; // Äá»•i thÃ nh máº¯t Ä‘Ã³ng
            }
        });
    });

    // â”€â”€ 3. AJAX ÄÄ‚NG NHáº¬P Há»† THá»NG
    const formLogIn = document.getElementById("formLogIn");
    if (formLogIn) {
        formLogIn.addEventListener("submit", function (e) {
            e.preventDefault();
            
            // XÃ³a sáº¡ch cÃ¡c thÃ´ng bÃ¡o lá»—i Validation cÅ©
            document.querySelectorAll(".invalid-msg").forEach(el => el.textContent = "");
            serverAlert.className = "alert d-none";

            const identity = document.getElementById("identity").value.trim();
            const password = document.getElementById("password").value;
            let hasError = false;

            if (identity === "") {
                document.getElementById("msg-identity").textContent = "Vui lÃ²ng nháº­p Email hoáº·c Sá»‘ Ä‘iá»‡n thoáº¡i.";
                hasError = true;
            }
            if (password === "") {
                document.getElementById("msg-password").textContent = "Vui lÃ²ng nháº­p máº­t kháº©u cá»§a báº¡n.";
                hasError = true;
            }

            if (hasError) return;

            const submitBtn = document.getElementById("btnLoginSubmit");
            submitBtn.disabled = true;
            submitBtn.textContent = "Äang xá»­ lÃ½...";

            const formData = new FormData(formLogIn);

<<<<<<< HEAD
            let fetchUrl = "../app/controllers/customer/LogInController.php?action=login";
            if (window.location.pathname.includes('/app/views/customer/')) {
                fetchUrl = "../../../app/controllers/customer/LogInController.php?action=login";
            }

            fetch(fetchUrl, {
=======
            fetch("../app/controllers/customer/LogInController.php?action=login", {
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
                method: "POST",
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error("Lá»—i káº¿t ná»‘i Server.");
                return res.json();
            })
            .then(data => {
                serverAlert.classList.remove("d-none");
                if (data.status === "success") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
<<<<<<< HEAD
                    // ÄÄƒng nháº­p khÃ¡ch hÃ ng -> Trang chá»§
                    setTimeout(() => { window.location.href = "../../../app/views/customer/TrangChu.php"; }, 1200);
                } else if (data.status === "admin") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
                    // ÄÄƒng nháº­p admin -> Dashboard admin
                    setTimeout(() => { window.location.href = "../../../app/controllers/admin/DashboardController.php"; }, 1200);
=======
                    // Đăng nhập khách hàng -> Trang chủ
                    setTimeout(() => { window.location.href = "index.php?page=TrangChu"; }, 1200);
                } else if (data.status === "admin") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
                    // Đăng nhập admin -> Dashboard admin
                    setTimeout(() => { window.location.href = "../app/controllers/admin/DashboardController.php"; }, 1200);
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
                } else {
                    serverAlert.className = "alert alert-danger";
                    serverAlert.textContent = data.message;
                    submitBtn.disabled = false;
                    submitBtn.textContent = "ÄÄƒng nháº­p";
                }
            })
            .catch(err => {
                console.error(err);
                serverAlert.className = "alert alert-danger";
                serverAlert.textContent = "KhÃ´ng thá»ƒ káº¿t ná»‘i vá»›i há»‡ thá»‘ng Cloud. Chi tiáº¿t: " + err.message;
                submitBtn.disabled = false;
                submitBtn.textContent = "ÄÄƒng nháº­p";
            });
        });
    }

    // â”€â”€ 4. AJAX YÃŠU Cáº¦U Gá»¬I OTP QUÃŠN Máº¬T KHáº¨U KHÃCH HÃ€NG
    const formForgot = document.getElementById("formForgot");
    if (formForgot) {
        formForgot.addEventListener("submit", function (e) {
            e.preventDefault();
            document.getElementById("msg-forgot-email").textContent = "";
            serverAlert.className = "alert d-none";

            const email = document.getElementById("forgot_email").value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById("msg-forgot-email").textContent = "Äá»‹nh dáº¡ng Ä‘á»‹a chá»‰ Email khÃ´ng Ä‘Ãºng.";
                return;
            }

            const btnForgot = document.getElementById("btnForgotSubmit");
            btnForgot.disabled = true;
            btnForgot.textContent = "Äang gá»­i OTP...";
            const formData = new FormData(formForgot);

<<<<<<< HEAD
            let fetchUrl = "../app/controllers/customer/LogInController.php?action=forgot";
            if (window.location.pathname.includes('/app/views/customer/')) {
                fetchUrl = "../../../app/controllers/customer/LogInController.php?action=forgot";
            }

            fetch(fetchUrl, {
=======
            fetch("../app/controllers/customer/LogInController.php?action=forgot", {
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                serverAlert.classList.remove("d-none");
                if (data.status === "otp_sent") {
                    serverAlert.className = "alert alert-warning";
                    serverAlert.textContent = data.message;
                    
                    // Chuyá»ƒn sang Form nháº­p mÃ£ OTP Ä‘áº·t láº¡i máº­t kháº©u má»›i
                    forgotSection.classList.add("d-none");
                    resetSection.classList.remove("d-none");
                } else {
                    serverAlert.className = "alert alert-danger";
                    serverAlert.textContent = data.message;
                    btnForgot.disabled = false;
                    btnForgot.textContent = "Gá»­i mÃ£ xÃ¡c thá»±c";
                }
            })
            .catch(err => {
                console.error(err);
                btnForgot.disabled = false;
                btnForgot.textContent = "Gá»­i mÃ£ xÃ¡c thá»±c";
            });
        });
    }

    // â”€â”€ 5. AJAX XÃC NHáº¬N OTP VÃ€ LÆ¯U Láº I Máº¬T KHáº¨U Má»šI HOÃ€N Táº¤T
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
                document.getElementById("msg-otp").textContent = "Vui lÃ²ng nháº­p mÃ£ OTP gá»“m 6 chá»¯ sá»‘.";
                hasError = true;
            }
            if (newPass.length < 6) {
                document.getElementById("msg-new-password").textContent = "Máº­t kháº©u má»›i chá»©a tá»‘i thiá»ƒu tá»« 6 kÃ½ tá»±.";
                hasError = true;
            }
            if (newPass !== confirmPass) {
                document.getElementById("msg-confirm-new-password").textContent = "XÃ¡c nháº­n máº­t kháº©u má»›i khÃ´ng khá»›p.";
                hasError = true;
            }

            if (hasError) return;

            const btnReset = document.getElementById("btnResetSubmit");
            btnReset.disabled = true;
            btnReset.textContent = "Äang cáº­p nháº­t...";
            const formData = new FormData(formReset);

<<<<<<< HEAD
            let fetchUrl = "../app/controllers/customer/LogInController.php?action=reset";
            if (window.location.pathname.includes('/app/views/customer/')) {
                fetchUrl = "../../../app/controllers/customer/LogInController.php?action=reset";
            }

            fetch(fetchUrl, {
=======
            fetch("../app/controllers/customer/LogInController.php?action=reset", {
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                serverAlert.classList.remove("d-none");
                if (data.status === "success") {
                    serverAlert.className = "alert alert-success";
                    serverAlert.textContent = data.message;
                    
                    // ThÃ nh cÃ´ng hoÃ n toÃ n -> ÄÆ°a quay vá» Form Ä‘Äƒng nháº­p chÃ­nh
                    setTimeout(() => {
                        resetSection.classList.add("d-none");
                        loginSection.classList.remove("d-none");
                        formLogIn.reset();
                    }, 2000);
                } else {
                    serverAlert.className = "alert alert-danger";
                    serverAlert.textContent = data.message;
                    btnReset.disabled = false;
                    btnReset.textContent = "LÆ°u thay Ä‘á»•i & ÄÄƒng nháº­p";
                }
            })
            .catch(err => {
                console.error(err);
                btnReset.disabled = false;
                btnReset.textContent = "LÆ°u thay Ä‘á»•i & ÄÄƒng nháº­p";
            });
        });
    }
});

    // â”€â”€ 6. CHá»ŒN PHÃ‚N Há»† KHÃCH HÃ€NG / QUáº¢N LÃ
    $(document).ready(function() {
    // Xá»­ lÃ½ sá»± kiá»‡n click chuyá»ƒn Ä‘á»•i tab giá»¯a NgÆ°á»i dÃ¹ng vÃ  Quáº£n lÃ½
    $('.role-tab').on('click', function() {
        
        // Loáº¡i bá» tráº¡ng thÃ¡i active cá»§a tab cÅ© vÃ  thÃªm vÃ o tab vá»«a click
        $('.role-tab').removeClass('active');
        $(this).addClass('active');
        
        // Láº¥y giÃ¡ trá»‹ vai trÃ² (customer hoáº·c admin)
        const selectedRole = $(this).data('role');
        
        // Cáº­p nháº­t giÃ¡ trá»‹ vÃ o input áº©n trong form Ä‘á»ƒ gá»­i lÃªn PHP xá»­ lÃ½
        $('#login_role').val(selectedRole);
        
        // Náº¿u chá»n Quáº£n lÃ½ thÃ¬ áº©n dÃ²ng "ÄÄƒng kÃ½ ngay" Ä‘i, chá»n NgÆ°á»i dÃ¹ng thÃ¬ hiá»‡n láº¡i
        if (selectedRole === 'admin') {
            $('#signup-redirect-text').addClass('d-none');
        } else {
            $('#signup-redirect-text').removeClass('d-none');
        }
    });
});