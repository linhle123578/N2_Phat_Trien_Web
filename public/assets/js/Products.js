document.addEventListener("DOMContentLoaded", function () {
    // 1. LOGIC SẮP XẾP KHI THAY ĐỔI DROP BOX
    const sortSelect = document.getElementById("sort-select");
    if (sortSelect) {
        sortSelect.addEventListener("change", function () {
            const selectedSort = this.value;
            let targetUrl = `products.php?sort=${selectedSort}`;
            
            if (currentCategory) {
                targetUrl += `&category=${currentCategory}`;
            }
            if (currentSearch) {
                targetUrl += `&search=${currentSearch}`;
            }
            
            window.location.href = targetUrl;
        });
    }

    // 2. LOGIC CLICK THÊM VÀO GIỎ HÀNG
    const addCartButtons = document.querySelectorAll(".btn-add-cart");
    addCartButtons.forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const productId = this.getAttribute("data-product-id");
            const productTitle = this.closest(".card-body").querySelector(".product-title").innerText;

            alert(`Đã thêm thành công "${productTitle}" vào giỏ hàng!`);
            
            const cartBadge = document.getElementById("cart-count");
            if (cartBadge) {
                let currentCount = parseInt(cartBadge.innerText) || 0;
                cartBadge.innerText = currentCount + 1;
            }
        });
    });
});