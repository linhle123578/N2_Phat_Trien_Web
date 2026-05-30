function formatVND(amount) {
    const safeAmount = Number(amount);
    if (isNaN(safeAmount) || safeAmount < 0) return '0đ';
    return safeAmount.toLocaleString('vi-VN') + 'đ';
}

function getItemQty(item) {
    return parseInt(item.dataset.qty, 10) || 0;
}

function setItemQty(item, qty) {
    const safeQty = Math.max(1, parseInt(qty, 10) || 1);
    item.dataset.qty = safeQty;
    const qtyDisplay = item.querySelector('.qty-display');
    if (qtyDisplay) qtyDisplay.textContent = safeQty;

    updateItemTotal(item);
    updateQtyButtons(item);
    saveQtyToDB(item, safeQty);
}

// Lưu số lượng lên DB qua AJAX
function saveQtyToDB(item, qty) {
    const deleteBtn  = item.querySelector('.delete-btn');
    const cartItemId = deleteBtn ? deleteBtn.dataset.id : null;
    if (!cartItemId) return;

    fetch('../../../app/controllers/customer/CartController.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'cart_item_id=' + encodeURIComponent(cartItemId) + '&quantity=' + qty
    }).catch(err => console.warn('Lưu qty thất bại:', err));
}

function getItemUnitPrice(item) {
    return parseInt(item.dataset.price, 10) || 0;
}

function updateItemTotal(item) {
    const priceEl = item.querySelector('.unit-price');
    const totalEl = item.querySelector('.item-total');

    const price = getItemUnitPrice(item);
    const qty = getItemQty(item);
    const unit = item.dataset.unit || '';

    if (priceEl) priceEl.textContent = formatVND(price) + ' / ' + unit;
    if (totalEl) totalEl.textContent = formatVND(price * qty);
}

// ── Quantity button state

function updateQtyButtons(item) {
    const minusBtn = item.querySelector('.qty-minus');
    const plusBtn = item.querySelector('.qty-plus');
    const qty = getItemQty(item);
    const stock = parseInt(item.dataset.stock, 10) || 999; // Lấy tồn kho từ data-stock

    if (minusBtn) {
        minusBtn.disabled = qty <= 1;
        minusBtn.style.opacity = qty <= 1 ? '0.45' : '1';
        minusBtn.style.cursor = qty <= 1 ? 'not-allowed' : 'pointer';
    }
    
    if (plusBtn) {
        // Vô hiệu hóa nút + nếu số lượng chạm mức tồn kho
        plusBtn.disabled = qty >= stock;
        plusBtn.style.opacity = qty >= stock ? '0.45' : '1';
        plusBtn.style.cursor = qty >= stock ? 'not-allowed' : 'pointer';
    }
}

// ── Summary calculation

function updateSummary() {
    const items = document.querySelectorAll('.cart-item');
    let subtotal = 0;
    // Đếm tổng số loại sản phẩm hiện có trong DOM để update lên badge header
    const totalItemsCount = items.length; 

    items.forEach(item => {
        const checkbox = item.querySelector('.item-checkbox');
        if (checkbox && checkbox.checked) {
            const qty = getItemQty(item);
            subtotal += getItemUnitPrice(item) * qty;
        }
    });

    const total = subtotal;

    const summaryLabel = document.getElementById('summary-label');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryShipping = document.getElementById('summary-shipping');
    const summaryTotal = document.getElementById('summary-total');
    const cartCountEl = document.getElementById('cart-count');

    // Chỗ này cũng cập nhật lại chữ "Tạm tính (x món)" dựa vào số lượng sản phẩm được tick
    const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
    if (summaryLabel) summaryLabel.textContent = 'Tạm tính (' + checkedCount + ' món)';
    
    if (summarySubtotal) summarySubtotal.textContent = formatVND(subtotal);
    if (summaryShipping) summaryShipping.textContent = subtotal > 0 ? formatVND(shipping) : 'Miễn phí';
    if (summaryTotal) {
        summaryTotal.textContent = formatVND(total);

        summaryTotal.classList.remove('summary-updated');
        void summaryTotal.offsetWidth;
        summaryTotal.classList.add('summary-updated');
    }

    // Cập nhật số lượng trên icon Header
    if (cartCountEl) {
        cartCountEl.textContent = totalItemsCount;
    }
}

// ── Item appearance (dim unchecked)

function updateItemAppearance(item) {
    const checkbox = item.querySelector('.item-checkbox');
    const checked = checkbox ? checkbox.checked : false;
    item.classList.toggle('unchecked', !checked);
}

// ── Delete item 

function deleteItem(item) {
    if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) return;

    // Lấy ID sản phẩm cần xóa
    const btn = item.querySelector('.delete-btn');
    const cartItemId = btn ? btn.dataset.id : null;
    
    if (!cartItemId) return;

    // SỬA ĐƯỜNG DẪN THÀNH 'CartController.php'
    fetch('../../../app/controllers/customer/CartController.php', { 
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : 'cart_item_id=' + cartItemId
    })
    .then(response => {
        if (response.ok) {
            // Xóa thành công thì chạy hiệu ứng trượt và trừ số lượng
            item.classList.add('removing');

            const finishDelete = () => {
                if (item.parentNode) item.remove();
                updateSummary(); 
            };
            
            setTimeout(finishDelete, 350);
        } else {
            // In thẳng mã lỗi ra để biết đường mò (404 là sai đường dẫn, 500 là lỗi DB)
            alert('Lỗi hệ thống! Mã lỗi: ' + response.status);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Lỗi kết nối mạng hoặc server không phản hồi!');
    });
}

//  Event delegation 

const cartItems = document.getElementById('cart-items');

if (cartItems) {
    cartItems.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-checkbox')) {
            const item = e.target.closest('.cart-item');
            if (!item) return;
            updateItemAppearance(item);
            updateSummary();
        }
    });

    cartItems.addEventListener('click', function(e) {
        const item = e.target.closest('.cart-item');
        if (!item) return;

        const minusBtn = e.target.closest('.qty-minus');
        const plusBtn = e.target.closest('.qty-plus');
        const deleteBtn = e.target.closest('.delete-btn');

        if (minusBtn) {
            const qty = getItemQty(item);
            if (qty > 1) {
                setItemQty(item, qty - 1);
                updateSummary();
            }
            return;
        }

        if (plusBtn) {
            const qty = getItemQty(item);
            const stock = parseInt(item.dataset.stock, 10) || 999;
            
            if (qty < stock) {
                setItemQty(item, qty + 1);
                updateSummary();
            } else {
                alert('Số lượng bạn chọn đã đạt mức tối đa của sản phẩm này!');
            }
            return;
        }

        if (deleteBtn) {
            deleteItem(item);
            return;
        }
    });
}

// Init

document.querySelectorAll('.cart-item').forEach(function(item) {
    updateItemTotal(item);
    updateItemAppearance(item);
    updateQtyButtons(item);
});

updateSummary();

// ── Xử lý Submit Form Thanh Toán
const checkoutForm = document.getElementById('checkout-form');

if (checkoutForm) {
    checkoutForm.addEventListener('submit', function(e) {
        const selectedInputsDiv = document.getElementById('selected-inputs');
        if (!selectedInputsDiv) return;

        // Xóa dữ liệu cũ nếu có
        selectedInputsDiv.innerHTML = ''; 

        // Tìm tất cả các checkbox sản phẩm ĐANG ĐƯỢC TICK
        const checkedItems = document.querySelectorAll('.item-checkbox:checked');
        
        // Chặn submit nếu chưa chọn món nào
        /*if (checkedItems.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 sản phẩm để thanh toán!');
            return;
        }*/

        // Lặp qua từng món được chọn, tạo thẻ input ẩn nhét vào form
        checkedItems.forEach(checkbox => {
            const cartItem = checkbox.closest('.cart-item');
            
            // Lấy ID sản phẩm (Giả định ID nằm ở value của checkbox, vd: <input type="checkbox" value="10">)
            const productId = checkbox.value; 
            const qty = getItemQty(cartItem);

            // 1. Tạo thẻ input chứa ID sản phẩm (name="selected[]")
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'selected[]';
            idInput.value = productId;
            selectedInputsDiv.appendChild(idInput);

            // 2. Tạo thẻ input chứa Số lượng tương ứng (name="qty[id]")
            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `qty[${productId}]`;
            qtyInput.value = qty;
            selectedInputsDiv.appendChild(qtyInput);
        });
        
        // Sau khi nhét đủ input ẩn vào DOM, form sẽ tự động submit đi tiếp
    });

}