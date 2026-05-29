document.addEventListener('DOMContentLoaded', () => {

    // ----------------------------------------------------------------
    // Config & biến trạng thái
    // ----------------------------------------------------------------
    const subtotal         = typeof DB_SUBTOTAL !== 'undefined' ? DB_SUBTOTAL : 0;
    let   currentShippingFee = 25000;

    // Đọc thông tin khách hàng từ data-attributes (PHP render sẵn - chính xác hơn)
    const nameEl    = document.getElementById('display-name');
    const addrEl    = document.getElementById('display-address');
    let customerData = {
        name:    nameEl?.dataset?.name    || nameEl?.innerText?.split('(+84)')[0]?.trim() || '',
        phone:   nameEl?.dataset?.phone   || '',
        address: addrEl?.dataset?.address || addrEl?.innerText?.trim() || ''
    };

    // ----------------------------------------------------------------
    // DOM refs
    // ----------------------------------------------------------------
    const addressModal      = document.getElementById('address-modal');
    const btnChangeAddress  = document.getElementById('btn-change-address');
    const btnCancelAddress  = document.getElementById('btn-cancel-address');
    const btnSaveAddress    = document.getElementById('btn-save-address');
    const displayName       = document.getElementById('display-name');
    const displayAddress    = document.getElementById('display-address');
    const shippingFeeDisplay= document.getElementById('shipping-fee-display');
    const finalTotalDisplay = document.getElementById('final-total');
    const shippingRadios    = document.querySelectorAll('input[name="shipping"]');
    const paymentRadios     = document.querySelectorAll('input[name="payment"]');
    const btnPlaceOrder     = document.getElementById('btn-place-order');

    const formatCurrency = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

    const updateTotal = () => {
        const total = subtotal + currentShippingFee;
        if (shippingFeeDisplay) shippingFeeDisplay.innerText = formatCurrency(currentShippingFee);
        if (finalTotalDisplay)  finalTotalDisplay.innerText  = formatCurrency(total);
    };

    // ----------------------------------------------------------------
    // Modal đổi địa chỉ
    // ----------------------------------------------------------------
    btnChangeAddress?.addEventListener('click', () => addressModal?.classList.add('active'));
    btnCancelAddress?.addEventListener('click', () => addressModal?.classList.remove('active'));

    btnSaveAddress?.addEventListener('click', () => {
        const newName    = document.getElementById('input-name')?.value?.trim();
        const newPhone   = document.getElementById('input-phone')?.value?.trim();
        const newAddress = document.getElementById('input-address')?.value?.trim();

        if (!newName || !newPhone || !newAddress) {
            showCheckoutError('Vui lòng điền đầy đủ thông tin!');
            return;
        }

        customerData = { name: newName, phone: newPhone, address: newAddress };

        if (displayName)   displayName.innerText   = `${newName} (+84) ${newPhone.replace(/^0+/, '')}`;
        if (displayAddress) displayAddress.innerText = newAddress;

        addressModal?.classList.remove('active');
    });

    // ----------------------------------------------------------------
    // Chọn vận chuyển
    // ----------------------------------------------------------------
    shippingRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('input[name="shipping"]').forEach(r => {
                r.closest('.radio-card')?.classList.remove('active');
            });
            e.target.closest('.radio-card')?.classList.add('active');
            currentShippingFee = parseInt(e.target.value);
            updateTotal();
        });
    });

    // ----------------------------------------------------------------
    // Chọn thanh toán
    // ----------------------------------------------------------------
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            document.querySelectorAll('input[name="payment"]').forEach(r => {
                r.closest('.radio-card')?.classList.remove('active');
            });
            e.target.closest('.radio-card')?.classList.add('active');
        });
    });

    // ----------------------------------------------------------------
    // ĐẶT HÀNG
    // ----------------------------------------------------------------
    btnPlaceOrder?.addEventListener('click', () => {
        const selectedPayment = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
        const totalAmount     = subtotal + currentShippingFee;

        // Validate địa chỉ
        if (!customerData.name || !customerData.phone || !customerData.address) {
            showCheckoutError('Vui lòng kiểm tra lại thông tin giao hàng!');
            return;
        }

        const orderPayload = {
            name:           customerData.name,
            phone:          customerData.phone,
            address:        customerData.address,
            shipping_fee:   currentShippingFee,
            total_amount:   totalAmount,
            payment_method: selectedPayment
        };

        // Loading state
        const originalText     = btnPlaceOrder.innerText;
        btnPlaceOrder.innerText  = 'ĐANG XỬ LÝ...';
        btnPlaceOrder.disabled   = true;

        // Đường dẫn absolute đến controller
        fetch('/app/controllers/customer/CheckoutController.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(orderPayload)
        })
        .then(response => {
            // Bắt lỗi nếu server trả HTML thay vì JSON
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                return response.text().then(html => {
                    console.error('Server trả HTML thay vì JSON:', html.substring(0, 300));
                    throw new Error('Server lỗi: nhận được HTML thay vì JSON. Kiểm tra lại đường dẫn controller.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                if (data.payment === 'momo') {
                    window.location.href = data.redirect_url;
                } else {
                    showCODSuccessModal(data.order_id || '');
                }
            } else {
                showCheckoutError(data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
                btnPlaceOrder.innerText = originalText;
                btnPlaceOrder.disabled  = false;
            }
        })
        .catch(error => {
            console.error('Checkout error:', error);
            showCheckoutError('Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại!');
            btnPlaceOrder.innerText = originalText;
            btnPlaceOrder.disabled  = false;
        });
    });

    updateTotal();

    // ================================================================
    // HELPERS (trong cùng scope DOMContentLoaded)
    // ================================================================

    function showCODSuccessModal(order_id) {
        let modal = document.getElementById('cod-success-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'cod-success-modal';
            modal.style.cssText = [
                'position:fixed', 'inset:0', 'z-index:2000',
                'display:flex', 'align-items:center', 'justify-content:center',
                'background:rgba(0,0,0,0.55)',
                'font-family:"Plus Jakarta Sans",sans-serif',
            ].join(';');

            modal.innerHTML = `
                <div style="background:#fff;border-radius:24px;padding:48px 40px 40px;
                            max-width:440px;width:90%;text-align:center;
                            box-shadow:0 20px 60px rgba(0,0,0,0.18)">
                    <div style="width:80px;height:80px;
                                background:linear-gradient(135deg,#022409,#183A1D);
                                border-radius:50%;display:flex;align-items:center;
                                justify-content:center;margin:0 auto 24px;font-size:36px">✅</div>
                    <h2 style="font-weight:800;font-size:28px;color:#022409;margin-bottom:8px">
                        Đặt hàng thành công!</h2>
                    <p style="font-family:Manrope,sans-serif;font-size:14px;color:#424841;margin-bottom:4px">
                        Cảm ơn bạn đã tin tưởng Farm2Home</p>
                    <p style="font-family:Manrope,sans-serif;font-size:14px;color:#424841;margin-bottom:4px">
                        Phương thức: <strong>Thanh toán khi nhận hàng (COD)</strong></p>
                    <p style="font-family:Manrope,sans-serif;font-size:14px;color:#424841;margin-bottom:12px">
                        Mã đơn hàng của bạn:</p>
                    <div id="cod-order-id"
                         style="font-family:'Liberation Mono',monospace;font-weight:700;
                                font-size:16px;color:#022409;background:#F1EEDD;
                                padding:6px 16px;border-radius:8px;display:inline-block;
                                margin-bottom:28px;letter-spacing:1px">—</div>
                    <br>
                    <a href="../../../app/views/customer/TrangChu.php"
                       style="background:linear-gradient(102deg,#022409 0%,#183A1D 100%);
                              color:#C5EDC3;border:none;border-radius:9999px;
                              padding:14px 40px;font-weight:700;font-size:15px;
                              letter-spacing:1.5px;text-transform:uppercase;
                              text-decoration:none;display:inline-block;margin-top:8px">
                        Về trang chủ
                    </a>
                </div>
            `;
            document.body.appendChild(modal);
        }
        const orderIdEl = modal.querySelector('#cod-order-id');
        if (orderIdEl) orderIdEl.textContent = order_id || '—';
        modal.style.display = 'flex';
    }

    function showCheckoutError(msg) {
        let errEl = document.getElementById('checkout-error-msg');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.id = 'checkout-error-msg';
            errEl.style.cssText = [
                'color:#B91C1C', 'font-size:14px', 'text-align:center',
                'margin-top:10px', 'font-family:Manrope,sans-serif',
                'font-weight:600',
            ].join(';');
            // Chèn sau nút đặt hàng nếu có, không thì append vào summary-card
            const target = btnPlaceOrder?.parentNode || document.querySelector('.summary-card');
            if (target) target.appendChild(errEl);
        }
        errEl.textContent = msg;
        setTimeout(() => { if (errEl) errEl.textContent = ''; }, 5000);
    }

}); // end DOMContentLoaded