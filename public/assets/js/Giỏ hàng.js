
const SHIPPING_FEE = 30000;

// ── Helpers 

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
    const qty = getItemQty(item);

    if (minusBtn) {
        minusBtn.disabled = qty <= 1;
        minusBtn.style.opacity = qty <= 1 ? '0.45' : '1';
        minusBtn.style.cursor = qty <= 1 ? 'not-allowed' : 'pointer';
    }
}

// ── Summary calculation

function updateSummary() {
    const items = document.querySelectorAll('.cart-item');
    let subtotal = 0;
    let checkedQty = 0;
    let checkedItemsCount = 0;

    items.forEach(item => {
        const checkbox = item.querySelector('.item-checkbox');
        if (checkbox && checkbox.checked) {
            const qty = getItemQty(item);
            subtotal += getItemUnitPrice(item) * qty;
            checkedQty += qty;
            checkedItemsCount += 1;
        }
    });

    const shipping = subtotal > 0 ? SHIPPING_FEE : 0;
    const total = subtotal + shipping;

    const summaryLabel = document.getElementById('summary-label');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryShipping = document.getElementById('summary-shipping');
    const summaryTotal = document.getElementById('summary-total');
    const cartCountEl = document.getElementById('cart-count');

    if (summaryLabel) summaryLabel.textContent = 'Tạm tính (' + checkedQty + ' món)';
    if (summarySubtotal) summarySubtotal.textContent = formatVND(subtotal);
    if (summaryShipping) summaryShipping.textContent = subtotal > 0 ? formatVND(shipping) : 'Miễn phí';
    if (summaryTotal) {
        summaryTotal.textContent = formatVND(total);

        summaryTotal.classList.remove('summary-updated');
        void summaryTotal.offsetWidth;
        summaryTotal.classList.add('summary-updated');
    }

    if (cartCountEl) {
        cartCountEl.textContent = checkedItemsCount;
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
    item.classList.add('removing');

    const finishDelete = () => {
        item.removeEventListener('transitionend', onTransitionEnd);
        if (item.parentNode) item.remove();
        updateSummary();
    };

    const onTransitionEnd = (e) => {
        if (e.target !== item) return;
        finishDelete();
    };

    item.addEventListener('transitionend', onTransitionEnd);

    setTimeout(() => {
        if (document.body.contains(item)) {
            finishDelete();
        }
    }, 350);
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
            setItemQty(item, getItemQty(item) + 1);
            updateSummary();
            return;
        }

        if (deleteBtn) {
            deleteItem(item);
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