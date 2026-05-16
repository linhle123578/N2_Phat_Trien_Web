function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    const cartQty = document.getElementById('cartQuantity');
    if (!input) return;
    
    let val = parseInt(input.value) + delta;
    let min = parseInt(input.getAttribute('min')) || 1;
    let max = parseInt(input.getAttribute('max')) || 999;
    
    if (val < min) val = min;
    if (val > max) val = max;
    
    input.value = val;
    if (cartQty) {
        cartQty.value = val;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('qtyInput');
    const cartQty = document.getElementById('cartQuantity');
    
    if (input) {
        input.addEventListener('input', function() {
            let val = parseInt(this.value);
            let min = parseInt(this.getAttribute('min')) || 1;
            let max = parseInt(this.getAttribute('max')) || 999;
            
            if (isNaN(val) || val < min) val = min;
            if (val > max) val = max;
            
            this.value = val;
            if (cartQty) {
                cartQty.value = val;
            }
        });
    }
});