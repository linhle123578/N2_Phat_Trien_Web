document.addEventListener('DOMContentLoaded', () => {
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

    // ĐÃ SỬA: Khởi tạo biến customerData ở đây để không bị lỗi "undefined" khi bấm nút
    let customerData = {
        name: displayName ? displayName.innerText.split('(')[0].trim() : "Khách hàng",
        phone: "0901234567",
        address: displayAddress ? displayAddress.innerText.trim() : "Địa chỉ mặc định"
    };

    const formatCurrency = (number) => new Intl.NumberFormat('vi-VN').format(number) + 'đ';

    const updateTotal = () => {
        const total = subtotal + currentShippingFee;
        if(shippingFeeDisplay) shippingFeeDisplay.innerText = formatCurrency(currentShippingFee);
        if(finalTotalDisplay) finalTotalDisplay.innerText = formatCurrency(total);
    };

    // Đổi địa chỉ
    if(btnChangeAddress) btnChangeAddress.addEventListener('click', () => addressModal.classList.add('active'));
    if(btnCancelAddress) btnCancelAddress.addEventListener('click', () => addressModal.classList.remove('active'));

    if(btnSaveAddress) {
        btnSaveAddress.addEventListener('click', () => {
            const newName = document.getElementById('input-name').value;
            const newPhone = document.getElementById('input-phone').value;
            const newAddress = document.getElementById('input-address').value;

            if(!newName || !newPhone || !newAddress) {
                alert("Vui lòng điền đầy đủ thông tin!");
                return;
            }

            customerData = { name: newName, phone: newPhone, address: newAddress };

            if(displayName) displayName.innerText = `${newName} (+84) ${newPhone.replace(/^0+/, '')}`;
            if(displayAddress) displayAddress.innerText = newAddress;
            
            addressModal.classList.remove('active');
        });
    }

    // Chọn Vận chuyển
    shippingRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('.shipping-section .radio-card').forEach(card => card.classList.remove('active'));
            const card = e.target.closest('.radio-card');
            if(card) card.classList.add('active');
            currentShippingFee = parseInt(e.target.value);
            updateTotal();
        });
    });

    // Chọn Thanh toán
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('.payment-section .radio-card').forEach(card => card.classList.remove('active'));
            const card = e.target.closest('.radio-card');
            if(card) card.classList.add('active');
        });
    });

    // GỬI DỮ LIỆU ĐẶT HÀNG (API Fetch)
    if(btnPlaceOrder) {
        btnPlaceOrder.addEventListener('click', () => {
            const paymentRadio = document.querySelector('input[name="payment"]:checked');
            if (!paymentRadio) {
                alert("Vui lòng chọn phương thức thanh toán!");
                return;
            }
            
            const selectedPayment = paymentRadio.value.toLowerCase();
            const totalAmount = subtotal + currentShippingFee;

            const orderPayload = {
                name: customerData.name,
                phone: customerData.phone,
                address: customerData.address,
                shipping_fee: currentShippingFee,
                total_amount: totalAmount,
                payment_method: selectedPayment
            };

            const originalText = btnPlaceOrder.innerText;
            btnPlaceOrder.innerText = 'ĐANG XỬ LÝ...';
            btnPlaceOrder.disabled = true;

            // ĐÃ SỬA: Gọi API thẳng vào đường dẫn web hiện tại để tránh lỗi 404 do thẻ <base>
            const apiUrl = window.location.href.split('?')[0];

            fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderPayload)
            })
            .then(response => {
                if (!response.ok) throw new Error("Lỗi HTTP (Mã: " + response.status + ")");
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    if (data.payment === 'momo') {
                        // Nối params vào apiUrl để nhảy sang view MoMo
                        window.location.href = apiUrl + data.redirect_url;
                    } else {
                        alert(data.message);
                        window.location.href = '/'; 
                    }
                } else {
                    alert("Lỗi từ hệ thống: " + data.message);
                    btnPlaceOrder.innerText = originalText;
                    btnPlaceOrder.disabled = false;
                }
            })
            .catch(error => {
    console.error('Fetch error:', error);
    alert("Lỗi kết nối hoặc server. Xem console (F12) để biết thêm.");
});
        });
    }

    updateTotal();
});