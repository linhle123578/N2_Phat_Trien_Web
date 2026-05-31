document.addEventListener('DOMContentLoaded', () => {

    const ORDER_DATA   = (typeof MOMO_ORDER_DATA !== 'undefined') ? MOMO_ORDER_DATA : {};
    const LIVE_MODE    = ORDER_DATA.live_mode     || false;
    const CONTROLLER   = 'MomoPaymentController.php';

    let   ORDER_ID     = ORDER_DATA.order_id     || null;

    // ---- Thời gian QR còn hiệu lực
    const QR_DURATION  = 10*60;
    let   timerSeconds = QR_DURATION;
    let   timerInterval  = null;
    let   pollInterval   = null;
    let   qrExpired      = false;
    let   paymentDone    = false;

    const qrImg             = document.getElementById('qr-img');
    const qrPlaceholder     = document.getElementById('qr-placeholder');
    const qrLoading         = document.getElementById('qr-loading');
    const qrSuccessOverlay  = document.getElementById('qr-success-overlay');
    const qrExpiredOverlay  = document.getElementById('qr-expired-overlay');
    const timerBadge        = document.getElementById('qr-timer-badge');
    const timerText         = document.getElementById('qr-timer-text');
    const btnRefresh        = document.getElementById('btn-refresh-qr');
    const successModal      = document.getElementById('momo-success-modal');
    const modalOrderId      = document.getElementById('modal-order-id');

    // Tạo QR ngay khi load trang
    initPayment();

    function initPayment() {
        showQRLoading(true);
        fetchCreateQR();
    }

    // GỌI API TẠO QR
    function fetchCreateQR() {
        qrExpired   = false;
        paymentDone = false;

        qrSuccessOverlay.classList.remove('show');
        qrExpiredOverlay.classList.remove('show');
        btnRefresh.classList.remove('show');
        timerBadge.classList.remove('expired');

        const body = new URLSearchParams({ action: 'create_qr' });
        if (ORDER_ID) body.append('order_id', ORDER_ID);

        fetch(CONTROLLER, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body
        })
        .then(r => r.json())
        .then(data => {
            showQRLoading(false);

            if (data.status === 'already_paid') {
                onPaymentSuccess();
                return;
            }

            if (data.status === 'success' && data.qr_url) {
                displayQR(data.qr_url);
                startTimer();
                startPolling();

                if (data.demo_mode) {
                    injectDemoButton();
                }
            } else {
                showQRError(data.message || 'Không thể tạo mã QR. Vui lòng thử lại.');
            }
        })
        .catch(err => {
            showQRLoading(false);
            showQRError('Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.');
            console.error('create_qr error:', err);
        });
    }

    // HIỆN ẢNH QR
    function displayQR(url) {
        qrPlaceholder.style.display = 'none';
        qrImg.src = url;
        qrImg.style.display = 'block';
        qrImg.onload  = () => {};
        qrImg.onerror = () => showQRError('Ảnh QR không tải được. Thử làm mới.');
    }

    // COUNTDOWN TIMER
    function startTimer() {
        clearInterval(timerInterval);
        timerSeconds = QR_DURATION;
        renderTimer();

        timerInterval = setInterval(() => {
            timerSeconds--;
            renderTimer();

            if (timerSeconds <= 0) {
                clearInterval(timerInterval);
                onTimerExpired();
            }
        }, 1000);
    }

    function renderTimer() {
        const m = String(Math.floor(timerSeconds / 60)).padStart(2, '0');
        const s = String(timerSeconds % 60).padStart(2, '0');
        timerText.textContent = `${m}:${s}`;
    }

    function onTimerExpired() {
        if (paymentDone) return;

        qrExpired = true;
        stopPolling();

        timerBadge.classList.add('expired');
        timerText.textContent = 'HẾT HẠN';

        qrExpiredOverlay.classList.add('show');
        btnRefresh.classList.add('show');
    }

    // POLLING TRẠNG THÁI
    function startPolling() {
        stopPolling();
        pollInterval = setInterval(checkPaymentStatus, 5000);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    function checkPaymentStatus() {
        if (paymentDone || qrExpired) return;

        const body = new URLSearchParams({ action: 'check_status' });
        if (ORDER_ID) body.append('order_id', ORDER_ID);

        fetch(CONTROLLER, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body
        })
        .then(r => r.json())
        .then(data => {
            if (data.paid === true) {
                // Lưu order_id thật vừa được tạo (từ pending flow)
                if (data.order_id && !ORDER_ID) {
                    ORDER_ID = data.order_id;
                }
                onPaymentSuccess();
            }
        })
        .catch(err => console.warn('Polling error (sẽ thử lại):', err));
    }

    // THANH TOÁN THÀNH CÔNG
    function onPaymentSuccess() {
        if (paymentDone) return;
        paymentDone = true;

        clearInterval(timerInterval);
        stopPolling();

        qrExpiredOverlay.classList.remove('show');
        qrSuccessOverlay.classList.add('show');

        setTimeout(() => {
            if (modalOrderId) modalOrderId.textContent = ORDER_ID || '—';
            const btnDetail = document.getElementById('btn-success-detail');
            if (btnDetail && ORDER_ID) {
                btnDetail.href = `../../../app/views/customer/OrderDetail.php?id=${ORDER_ID}`;
            }
            if (successModal) successModal.classList.add('show');
        }, 800);
    }

    // LÀM MỚI QR
    window.refreshQR = function () {
        clearInterval(timerInterval);
        stopPolling();

        qrExpiredOverlay.classList.remove('show');
        btnRefresh.classList.remove('show');
        timerBadge.classList.remove('expired');

        showQRLoading(true);
        fetchCreateQR();
    };

    // Nút giả lập thanh toán thành công
    function injectDemoButton() {
        // Tránh thêm 2 lần
        if (document.getElementById('btn-mock-confirm')) return;

        const qrPanel = document.querySelector('.momo-qr-panel');
        if (!qrPanel) return;

        const wrap = document.createElement('div');
        wrap.style.cssText = 'margin-top:16px; text-align:center;';

        const label = document.createElement('p');
        label.style.cssText = 'font-size:11px; color:#9CA3AF; font-family:Manrope,sans-serif; margin-bottom:6px;';
        label.textContent = '— Chế độ DEMO —';

        const btn = document.createElement('button');
        btn.id = 'btn-mock-confirm';
        btn.textContent = '✅ Giả lập thanh toán thành công';
        btn.style.cssText = [
            'background: #022409',
            'color: #C5EDC3',
            'border: none',
            'border-radius: 9999px',
            'padding: 10px 20px',
            'font-family: Plus Jakarta Sans, sans-serif',
            'font-weight: 700',
            'font-size: 13px',
            'cursor: pointer',
            'transition: opacity 0.2s',
        ].join(';');

        btn.addEventListener('click', () => {
            btn.disabled    = true;
            btn.textContent = '⏳ Đang xử lý...';
            mockConfirmPayment();
        });

        wrap.appendChild(label);
        wrap.appendChild(btn);
        qrPanel.appendChild(wrap);
    }

    function mockConfirmPayment() {
        fetch(CONTROLLER, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams({ action: 'mock_confirm', order_id: ORDER_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                onPaymentSuccess();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xác nhận'));
                const btn = document.getElementById('btn-mock-confirm');
                if (btn) {
                    btn.disabled    = false;
                    btn.textContent = '✅ Giả lập thanh toán thành công';
                }
            }
        })
        .catch(err => {
            console.error('mock_confirm error:', err);
            alert('Lỗi kết nối khi giả lập thanh toán');
        });
    }

    function showQRLoading(show) {
        if (!qrLoading) return;
        if (show) {
            qrLoading.classList.remove('hidden');
        } else {
            qrLoading.classList.add('hidden');
        }
    }

    function showQRError(msg) {
        if (qrPlaceholder) {
            qrPlaceholder.style.display = 'flex';
            const p = qrPlaceholder.querySelector('p');
            if (p) {
                p.textContent = msg;
                p.style.color = '#B91C1C';
            }
            const icon = qrPlaceholder.querySelector('.qr-icon');
            if (icon) icon.textContent = '❌';
        }
        btnRefresh.classList.add('show');
    }

    if (successModal) {
        successModal.addEventListener('click', (e) => {
            if (e.target === successModal) {
                successModal.classList.remove('show');
            }
        });
    }
});
