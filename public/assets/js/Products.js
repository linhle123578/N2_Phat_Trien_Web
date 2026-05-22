document.addEventListener("DOMContentLoaded", function () {
    
    // 1. LOGIC SẮP XẾP SẢN PHẨM KHI CHỌN DROP BOX
    const sortSelect = document.getElementById("sort-select");
    if (sortSelect) {
        sortSelect.addEventListener("change", function () {
            const selectedSort = this.value;
            let targetUrl = `ProductController.php?sort=${selectedSort}`; // Sửa hướng url về Controller nhận bộ lọc
            
            if (currentCategory) {
                targetUrl += `&category=${currentCategory}`;
            }
            if (currentSearch) {
                targetUrl += `&search=${currentSearch}`;
            }
            
            window.location.href = targetUrl;
        });
    }

    // 2. LOGIC AJAX THÊM VÀO GIỎ HÀNG & KIỂM TRA ĐĂNG NHẬP
    // SỬA SELECTOR: Đổi tên lớp tìm kiếm nút bấm cho khớp chính xác với class HTML gốc (.btn-add-cart)
    const addCartButtons = document.querySelectorAll(".btn-add-cart");
    
    addCartButtons.forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            
            const productId = this.getAttribute("data-product-id");
            const cardBody = this.closest(".card-body");
            const productTitle = cardBody ? cardBody.querySelector(".product-title").innerText : "sản phẩm";

            const formData = new FormData();
            formData.append('product_id', productId);

            // Chỉ định endpoint xử lý về đúng tập tin điều hướng Controller
            fetch('../../../app/controllers/customer/ProductController.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.status === 401) {
                    alert("Bạn cần phải đăng nhập để thêm sản phẩm vào giỏ hàng!");
                    window.location.href = "login.php"; 
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;

                if (data.status === "success") {
                    alert(`Đã thêm thành công "${productTitle}" vào giỏ hàng của bạn!`);
                    
                    const cartBadge = document.getElementById("cart-count");
                    if (cartBadge) {
                        cartBadge.innerText = data.cart_count;
                    }
                } else {
                    alert(data.message || "Có lỗi xảy ra khi thêm sản phẩm.");
                }
            })
            .catch(error => {
                console.error("Error adding to cart:", error);
            });
        });
    });
});