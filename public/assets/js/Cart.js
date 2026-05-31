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


function updateSummary() {
    const items = document.querySelectorAll('.cart-item');
    let subtotal = 0;
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

    if (cartCountEl) {
        cartCountEl.textContent = totalItemsCount;
    }
}


function updateItemAppearance(item) {
    const checkbox = item.querySelector('.item-checkbox');
    const checked = checkbox ? checkbox.checked : false;
    item.classList.toggle('unchecked', !checked);
}


function deleteItem(item) {
    if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) return;

    const btn = item.querySelector('.delete-btn');
    const cartItemId = btn ? btn.dataset.id : null;
    
    if (!cartItemId) return;

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


document.querySelectorAll('.cart-item').forEach(function(item) {
    updateItemTotal(item);
    updateItemAppearance(item);
    updateQtyButtons(item);
});

updateSummary();

const checkoutForm = document.getElementById('checkout-form');

if (checkoutForm) {
    checkoutForm.addEventListener('submit', function(e) {
        const selectedInputsDiv = document.getElementById('selected-inputs');
        if (!selectedInputsDiv) return;

        selectedInputsDiv.innerHTML = ''; 

        const checkedItems = document.querySelectorAll('.item-checkbox:checked');

        checkedItems.forEach(checkbox => {
            const cartItem = checkbox.closest('.cart-item');
            
            const productId = checkbox.value; 
            const qty = getItemQty(cartItem);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'selected[]';
            idInput.value = productId;
            selectedInputsDiv.appendChild(idInput);

            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `qty[${productId}]`;
            qtyInput.value = qty;
            selectedInputsDiv.appendChild(qtyInput);
        });
        
    });

}
