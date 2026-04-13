// Dữ liệu mẫu mô phỏng 8 sản phẩm trong ảnh
const products = [
    { id: 1, name: "Rau Muống Hữu Cơ Đà Lạt", price: 18000, oldPrice: 25000, location: "Đà Lạt, Lâm Đồng", rating: 4, reviews: 234, tag: "Hữu cơ", discount: "-28%" },
    { id: 2, name: "Cà Rốt Baby Đà Lạt", price: 35000, oldPrice: null, location: "Đà Lạt, Lâm Đồng", rating: 5, reviews: 189, tag: "Bán chạy", discount: null },
    { id: 3, name: "Xoài Cát Hòa Lộc", price: 85000, oldPrice: 110000, location: "Cái Bè, Tiền Giang", rating: 5, reviews: 412, tag: "OCOP", discount: "-23%" },
    { id: 4, name: "Thanh Long Ruột Đỏ Bình Thuận", price: 45000, oldPrice: null, location: "Bình Thuận", rating: 4, reviews: 98, tag: "Mới", discount: null },
    { id: 5, name: "Cải Thảo Hữu Cơ", price: 22000, oldPrice: null, location: "Mộc Châu, Sơn La", rating: 4, reviews: 156, tag: "Hữu cơ", discount: null },
    { id: 6, name: "Khoai Lang Mật Đà Lạt", price: 28000, oldPrice: null, location: "Đà Lạt, Lâm Đồng", rating: 5, reviews: 287, tag: "Bán chạy", discount: null },
    { id: 7, name: "Bơ Booth Đắk Lắk", price: 65000, oldPrice: null, location: "Đắk Lắk", rating: 4, reviews: 345, tag: "OCOP", discount: null },
    { id: 8, name: "Nấm Linh Chi Đỏ Hữu Cơ", price: 320000, oldPrice: 380000, location: "Lâm Đồng", rating: 5, reviews: 167, tag: "OCOP", discount: "-16%" }
];

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('product-container');
    
    // Xóa nội dung cũ (nếu có) và render list mới
    container.innerHTML = products.map(product => `
        <div class="col-md-4 col-6">
            <div class="card h-100 product-card border-0 shadow-sm">
                <div class="position-relative">
                    ${product.tag ? `<span class="badge bg-success position-absolute m-2">${product.tag}</span>` : ''}
                    ${product.discount ? `<span class="badge bg-danger position-absolute top-0 end-0 m-2">${product.discount}</span>` : ''}
                    <img src="Media/${product.id}.jpg" class="card-img-top p-3" alt="${product.name}">
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-1"><i class="bi bi-geo-alt"></i> ${product.location}</p>
                    <h6 class="product-title">${product.name}</h6>
                    <div class="text-warning mb-2 small">
                        ${renderStars(product.rating)}
                        <span class="text-muted">(${product.reviews})</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-orange">${product.price.toLocaleString()}đ</span>
                        ${product.oldPrice ? `<span class="text-muted text-decoration-line-through small">${product.oldPrice.toLocaleString()}đ</span>` : ''}
                    </div>
                    <button class="btn btn-outline-dark w-100 rounded-pill mt-3 btn-add-cart">
                        <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ
                    </button>
                </div>
            </div>
        </div>
    `).join('');
});

function renderStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += i <= rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
    }
    return stars;
}