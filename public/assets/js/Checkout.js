document.addEventListener('DOMContentLoaded', () => {
    // SỬA DÒNG FIX CỨNG TIỀN THÀNH ĐỌC BIẾN ĐỘNG TỪ DATABASE SANG:
    const subtotal = typeof DB_SUBTOTAL !== 'undefined' ? DB_SUBTOTAL : 0;
    let currentShippingFee = 25000;
    
    const addressModal = document.getElementById('address-modal');
    const btnChangeAddress = document.getElementById('btn-change-address');
    const btnCancelAddress = document.getElementById('btn-cancel-address');
    const btnSaveAddress = document.getElementById('btn-save-address');
    
    const displayName = document.getElementById('display-name');
    const displayAddress = document.getElementById('display-address');
    const shippingFeeDisplay = document.getElementById('shipping-fee-display');
    const finalTotalDisplay = document.getElementById('final-total');

    const shippingRadios = document.querySelectorAll('input[name="shipping"]');
    const paymentRadios = document.querySelectorAll('input[name="payment"]');
    const btnPlaceOrder = document.getElementById('btn-place-order');

    // Lưu thông tin người dùng vào biến để dễ submit
    let customerData = {
        name: "Nguyễn Văn A",
        phone: "0901234567",
        address: "123 Đường Sắc Xuân, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh"
    };

    const formatCurrency = (number) => new Intl.NumberFormat('vi-VN').format(number) + 'đ';

    const updateTotal = () => {
        const total = subtotal + currentShippingFee;
        shippingFeeDisplay.innerText = formatCurrency(currentShippingFee);
        finalTotalDisplay.innerText = formatCurrency(total);
    };

    // Đổi địa chỉ
    btnChangeAddress.addEventListener('click', () => addressModal.classList.add('active'));
    btnCancelAddress.addEventListener('click', () => addressModal.classList.remove('active'));

    btnSaveAddress.addEventListener('click', () => {
        const newName = document.getElementById('input-name').value;
        const newPhone = document.getElementById('input-phone').value;
        const newAddress = document.getElementById('input-address').value;

        if(!newName || !newPhone || !newAddress) {
            alert("Vui lòng điền đầy đủ thông tin!");
            return;
        }

        // Cập nhật biến dữ liệu
        customerData = { name: newName, phone: newPhone, address: newAddress };

        // Cập nhật giao diện
        displayName.innerText = `${newName} (+84) ${newPhone.replace(/^0+/, '')}`;
        displayAddress.innerText = newAddress;
        
        addressModal.classList.remove('active');
    });

    // Chọn Vận chuyển
    shippingRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('.shipping-section .radio-card').forEach(card => card.classList.remove('active'));
            e.target.closest('.radio-card').classList.add('active');
            currentShippingFee = parseInt(e.target.value);
            updateTotal();
        });
    });

    // Chọn Thanh toán
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('.payment-section .radio-card').forEach(card => card.classList.remove('active'));
            e.target.closest('.radio-card').classList.add('active');
        });
    });

    // GỬI DỮ LIỆU ĐẶT HÀNG (API Fetch)
    btnPlaceOrder.addEventListener('click', () => {
        const selectedPayment = document.querySelector('input[name="payment"]:checked').value;
        const totalAmount = subtotal + currentShippingFee;

        // Gom dữ liệu chuẩn bị gửi xuống PHP
        const orderPayload = {
            name: customerData.name,
            phone: customerData.phone,
            address: customerData.address,
            shipping_fee: currentShippingFee,
            total_amount: totalAmount,
            payment_method: selectedPayment
        };

        // Giao diện loading
        const originalText = btnPlaceOrder.innerText;
        btnPlaceOrder.innerText = 'ĐANG XỬ LÝ...';
        btnPlaceOrder.disabled = true;

        // Gọi API lên route đã khai báo
        fetch('CheckoutController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderPayload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.payment === 'momo') {
                    // Chuyển hướng sang trang MoMo
                    window.location.href = data.redirect_url;
                } else {
                    // COD thành công
                    alert(data.message);
                    window.location.href = '/trang-chu'; 
                }
            } else {
                alert(data.message);
                btnPlaceOrder.innerText = originalText;
                btnPlaceOrder.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Có lỗi xảy ra, vui lòng thử lại!");
            btnPlaceOrder.innerText = originalText;
            btnPlaceOrder.disabled = false;
        });
    });

    updateTotal();
});