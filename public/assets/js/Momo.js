document.addEventListener("DOMContentLoaded", () => {
    // Đọc biến từ Momo.php truyền sang
    if (typeof MOMO_ORDER_ID === 'undefined' || !MOMO_ORDER_ID) return;

    let timeLeft = 900; // 15 phút đếm ngược
    let timerInterval;
    let paymentStatusInterval;

    function startCountdown() {
        const timerText = document.getElementById('qr-timer-text');
        
        clearInterval(timerInterval);
        
        timerInterval = setInterval(() => {
            timeLeft--;
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            if(timerText) timerText.innerText = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                clearInterval(paymentStatusInterval);
                if(timerText) timerText.innerText = "Hết hạn";
            }
        }, 1000);
    }

    function generateMomoQR() {
        // Sinh QR Code động dựa theo ID và Tổng tiền thật
        const momoPayload = `224_MOMO_MERCHANT_PAYMENT|ID:${MOMO_ORDER_ID}|TOTAL:${MOMO_TOTAL}`;
        const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(momoPayload)}`;

        const qrImg = document.getElementById('momo-qr-img');
        if(qrImg) {
            qrImg.src = qrApiUrl;
            qrImg.style.display = 'block';
        }

        startCountdown();
        
        // Giả lập 8 giây sau thanh toán thành công
        let simulatedWait = 8;
        paymentStatusInterval = setInterval(() => {
            simulatedWait--;
            if (simulatedWait <= 0) {
                clearInterval(paymentStatusInterval);
                clearInterval(timerInterval);
                
                // Hiển thị Modal thành công
                const modalOrderId = document.getElementById('modal-order-id');
                if(modalOrderId) modalOrderId.innerText = MOMO_ORDER_ID;

                const successModal = document.getElementById('momo-success-modal');
                if(successModal) {
                    successModal.style.visibility = 'visible';
                    successModal.style.opacity = '1';
                }
            }
        }, 1000);
    }

    generateMomoQR();
});