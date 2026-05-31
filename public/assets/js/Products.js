
document.addEventListener("DOMContentLoaded", function () {
    const sortSelect = document.getElementById("sort-select");
    if (sortSelect) {
        sortSelect.addEventListener("change", function () {
            const selectedSort = this.value;
            let targetUrl = `ProductController.php?sort=${selectedSort}`;
            if (typeof currentCategory !== 'undefined' && currentCategory) {
                targetUrl += `&category=${currentCategory}`;
            }
            if (typeof currentSearch !== 'undefined' && currentSearch) {
                targetUrl += `&search=${currentSearch}`;
            }
            
            window.location.href = targetUrl;
        });
    }

    const addCartButtons = document.querySelectorAll(".btn-add-cart");
    addCartButtons.forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const productId = this.getAttribute("data-product-id");
            const productCard = this.closest(".card-body");
            const productTitle = productCard.querySelector(".product-title").innerText;

            // Đóng gói dữ liệu an toàn gửi lên máy chủ
            const formData = new FormData();
            formData.append('product_id', productId);

            formData.append('ajax', '1');

            fetch(`../../../app/controllers/customer/CartController.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'not_logged_in') {
                    alert(data.message);
                    window.location.href = `../../../app/views/customer/LogIn.php`; 
                } else if (data.status === 'success') {
                    alert(`Đã thêm thành công sản phẩm "${productTitle}" vào giỏ hàng hệ thống!`);
                    
                    const cartBadge = document.getElementById("cart-count");
                    if (cartBadge) {
                        cartBadge.innerText = data.new_cart_count;
                    }
                } else {
                    alert("Lỗi: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Đường truyền hệ thống đang bận, vui lòng thử lại sau!");
            });
        });
    });
});
