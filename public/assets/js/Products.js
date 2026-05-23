document.addEventListener("DOMContentLoaded", function () {
    // 1. LOGIC SẮP XẾP KHI THAY ĐỔI DROP BOX
    const sortSelect = document.getElementById("sort-select");
    if (sortSelect) {
        sortSelect.addEventListener("change", function () {
            const selectedSort = this.value;
            // SỬA: Hướng trực tiếp về file router gốc products.php, không lùi cấp bằng ../../../
            let targetUrl = `../../../app/views/customer/Products.php?sort=${selectedSort}`;
            
            // Kiểm tra các biến toàn cục được truyền từ PHP ở cuối file View
            if (typeof currentCategory !== 'undefined' && currentCategory) {
                targetUrl += `&category=${currentCategory}`;
            }
            if (typeof currentSearch !== 'undefined' && currentSearch) {
                targetUrl += `&search=${currentSearch}`;
            }
            
            window.location.href = targetUrl;
        });
    }

    // 2. LOGIC THÊM VÀO GIỎ HÀNG QUA AJAX
    const addCartButtons = document.querySelectorAll(".btn-add-cart");
    addCartButtons.forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const productId = this.getAttribute("data-product-id");
            const productCard = this.closest(".card-body");
            const productTitle = productCard.querySelector(".product-title").innerText;

            const formData = new FormData();
            formData.append('product_id', productId);

            // SỬA: Gọi trực tiếp cổng xử lý action từ file router gốc products.php
            fetch('products.php?action=add_to_cart', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Mạng lỗi hệ thống");
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'not_logged_in') {
                    alert("Bạn cần phải đăng nhập tài khoản hệ thống để thực hiện chức năng mua sắm này!");
                    window.location.href = 'login.php'; // SỬA: Chuyển hướng trực tiếp ở tầng root
                } else if (data.status === 'success') {
                    alert(`Đã thêm thành công sản phẩm "${productTitle}" vào giỏ hàng hệ thống!`);
                    
                    const cartBadge = document.getElementById("cart-count");
                    if (cartBadge) {
                        cartBadge.innerText = data.new_cart_count;
                    }
                } else {
                    alert("Lỗi hệ thống: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Đường truyền hệ thống đang bận. Vui lòng thử lại sau!");
            });
        });
    });
});