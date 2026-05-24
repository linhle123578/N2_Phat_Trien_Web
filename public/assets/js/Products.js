document.addEventListener("DOMContentLoaded", function () {
    // 1. FIX SẮP XẾP DROP DOWN: Chuyển hướng khớp với tệp Products.php đang chạy trực tiếp
    const sortSelect = document.getElementById("sort-select");
    if (sortSelect) {
        sortSelect.addEventListener("change", function () {
            const selectedSort = this.value;
            let targetUrl = `../../../app/views/customer/Products.php?sort=${selectedSort}`;
            
            if (typeof currentCategory !== 'undefined' && currentCategory) {
                targetUrl += `&category=${currentCategory}`;
            }
            if (typeof currentSearch !== 'undefined' && currentSearch) {
                targetUrl += `&search=${currentSearch}`;
            }
            
            window.location.href = targetUrl;
        });
    }

    // 2. FIX SỰ KIỆN THÊM VÀO GIỎ HÀNG QUA FETCH AJAX
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

            // Đường dẫn tương đối từ thư mục views/customer/ sang controllers/customer/
            fetch('../../controllers/customer/ProductController.php?action=add_to_cart', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'not_logged_in') {
                    // Xử lý khi CHƯA đăng nhập: Hiện thông báo và đẩy sang trang đăng nhập
                    alert(data.message);
                    window.location.href = 'login.php'; 
                } else if (data.status === 'success') {
                    // Xử lý khi ĐÃ đăng nhập: Thông báo thành công và tăng số thực tế trên icon Navbar
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