document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("formSignUp");
    const phoneInput = document.getElementById("phone");
    const submitBtn = document.getElementById("btnSubmitForm");
    
    // Biến trạng thái lưu trữ việc SĐT hợp lệ và không trùng lặp
    let isPhoneValidAndAvailable = false;

 // 1. KIỂM TRA TRÙNG SỐ ĐIỆN THOẠI REAL-TIME (ĐÃ SỬA LỖI ĐƯỜNG TRUYỀN)
    phoneInput.addEventListener("change", function () {
        const phoneValue = phoneInput.value.trim();
        const msgEl = document.getElementById("msg-phone");
        const iconEl = document.getElementById("phone-check-icon");

        msgEl.textContent = "";
        iconEl.innerHTML = "";

        const phoneRegex = /^(0[3|5|7|8|9])+([0-9]{8})$/;
        if (!phoneRegex.test(phoneValue)) {
            msgEl.textContent = "Số điện thoại không đúng định dạng Việt Nam.";
            iconEl.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
            isPhoneValidAndAvailable = false;
            return;
        }

        // Đẩy tham số action lên URL parameter để Controller bóc tách chính xác bằng $_GET['action']
        fetch("../../controllers/customer/SignUpController.php?action=checkPhone", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "phone=" + encodeURIComponent(phoneValue) // Truyền thuần số điện thoại dưới body
        })
        .then(response => {
            if (!response.ok) throw new Error("Mất kết nối mạng hoặc Server phản hồi lỗi: " + response.status);
            return response.json(); // Ép kiểu dữ liệu sang JSON
        })
        .then(data => {
            if (data.exists) {
                // Nếu số điện thoại đã tồn tại trong database
                msgEl.textContent = data.message;
                iconEl.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
                isPhoneValidAndAvailable = false;
            } else {
                // Nếu số điện thoại khả dụng
                iconEl.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                isPhoneValidAndAvailable = true;
            }
        })
        .catch(error => {
            console.error("Lỗi chi tiết hệ thống:", error);
            msgEl.textContent = "Không thể check trùng số điện thoại.";
            isPhoneValidAndAvailable = false;
        });
    });
    
    // 2. XỬ LÝ SUBMIT HOÀN TẤT ĐĂNG KÝ
    form.addEventListener("submit", function (e) {
        e.preventDefault(); // Ngăn trang reload

        // Reset các tin nhắn báo lỗi cũ
        document.querySelectorAll(".invalid-msg").forEach(el => el.textContent = "");
        const serverAlert = document.getElementById("server-alert");
        serverAlert.className = "alert d-none";

        const fullname = document.getElementById("fullname").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm_password").value;
        const agreeTerms = document.getElementById("agreeTerms").checked; // Lấy đúng giá trị True/False

        let hasError = false;

        // Bắt lỗi Họ tên
        if (fullname === "") {
            document.getElementById("msg-fullname").textContent = "Vui lòng nhập họ tên.";
            hasError = true;
        }

        // Bắt lỗi Số điện thoại
        if (!isPhoneValidAndAvailable) {
            document.getElementById("msg-phone").textContent = "Số điện thoại trùng lặp hoặc chưa đúng định dạng.";
            hasError = true;
        }

        // Bắt lỗi Email
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById("msg-email").textContent = "Địa chỉ email không đúng định dạng.";
            hasError = true;
        }

        // Bắt lỗi Mật khẩu
        if (password.length < 6) {
            document.getElementById("msg-password").textContent = "Mật khẩu chứa tối thiểu 6 ký tự.";
            hasError = true;
        }

        // Bắt lỗi Xác nhận mật khẩu
        if (password !== confirmPassword) {
            document.getElementById("msg-confirm-password").textContent = "Mật khẩu xác nhận không khớp.";
            hasError = true;
        }

        // KHẮC PHỤC LỖI: KIỂM TRA BẮT BUỘC ĐỒNG Ý ĐIỀU KHOẢN
        if (!agreeTerms) {
            document.getElementById("msg-agree").textContent = "Bạn phải đồng ý với điều khoản điều kiện để đăng ký.";
            hasError = true;
        }

        // Nếu có bất kỳ lỗi nào thì dừng luồng submit ngay lập tức
        if (hasError) return;

        // Khóa nút submit tránh spam click
        submitBtn.disabled = true;
        submitBtn.textContent = "Đang xử lý...";

        const formData = new FormData(form);

        fetch("../../controllers/customer/SignUpController.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            serverAlert.classList.remove("d-none");
            if (data.status === "success") {
                serverAlert.classList.add("alert-success");
                serverAlert.textContent = data.message;
                setTimeout(() => { window.location.href = "login.php"; }, 2000);
            } else {
                serverAlert.classList.add("alert-danger");
                serverAlert.textContent = data.message;
                submitBtn.disabled = false;
                submitBtn.textContent = "Đăng ký";
            }
        })
        .catch(error => {
            console.error("Submit Error:", error);
            serverAlert.classList.remove("d-none");
            serverAlert.classList.add("alert-danger");
            serverAlert.textContent = "Lỗi đường truyền hệ thống.";
            submitBtn.disabled = false;
            submitBtn.textContent = "Đăng ký";
        });
    });

    // 3. KHẮC PHỤC LỖI: ICON ẨN/HIỆN MẬT KHẨU KHÔNG HOẠT ĐỘNG
    const toggleEye1 = document.getElementById('toggle-pwd-1');
    const toggleEye2 = document.getElementById('toggle-pwd-2');

    if (toggleEye1) {
        toggleEye1.addEventListener('click', function () {
            toggleVisibility('password', this);
        });
    }

    if (toggleEye2) {
        toggleEye2.addEventListener('click', function () {
            toggleVisibility('confirm_password', this);
        });
    }

    function toggleVisibility(fieldId, iconElement) {
        const field = document.getElementById(fieldId);
        const icon = iconElement.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'far fa-eye'; // Hiện mật khẩu (mắt mở)
        } else {
            field.type = 'password';
            icon.className = 'far fa-eye-slash'; // Ẩn mật khẩu (mắt gạch chéo)
        }
    }
});