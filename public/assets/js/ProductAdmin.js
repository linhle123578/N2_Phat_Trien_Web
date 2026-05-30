/* =========================================================
   ProductAdmin.js  –  Farm2Home Admin Panel
   ========================================================= */

// ── Data setup ──────────────────────────────────────────────
const products = PRODUCTS_FROM_DB.map(p => ({
    ...p,
    stock:       parseInt(p.stock)  || 0,
    price:       parseInt(p.price)  || 0,
    sold:        parseInt(p.sold)   || 0,
    category_id: p.category_id ? String(p.category_id) : ''
}));

const CATEGORY_MAP = {};
CATEGORIES_FROM_DB.forEach(c => { CATEGORY_MAP[String(c.category_id)] = c.name; });

const fallbackImage = 'https://placehold.co/100x100?text=No+Image';
const ITEMS_PER_PAGE = 8;
let currentPage    = 1;
let filteredProducts = [...products];

let productModal = null;
let deleteModal  = null;

// ── Helpers ─────────────────────────────────────────────────
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

function getCategoryName(catId) {
    return CATEGORY_MAP[String(catId)] || 'Chưa phân loại';
}

function getStockStatus(stock) {
    if (stock === 0)   return { label: 'Hết hàng', cls: 'badge-out-of-stock' };
    if (stock < 10)    return { label: 'Sắp hết',  cls: 'badge-low-stock'    };
    return                    { label: 'Còn hàng',  cls: 'badge-in-stock'    };
}

// ── Render table ─────────────────────────────────────────────
function renderTable() {
    const tbody = document.getElementById('productTableBody');
    const total = filteredProducts.length;
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end   = Math.min(start + ITEMS_PER_PAGE, total);
    const page  = filteredProducts.slice(start, end);

    tbody.innerHTML = '';

    if (page.length === 0) {
        tbody.innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.4;"></i>
                Không tìm thấy sản phẩm nào
              </td>
            </tr>`;
        updateInfoBar(0, 0, 0);
        document.getElementById('pagination').innerHTML = '';
        return;
    }

    page.forEach(p => {
        const catName  = getCategoryName(p.category_id);
        const status   = getStockStatus(p.stock);
        const imgSrc   = MEDIA_PATH + (p.product_image || '');

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="ps-4 fw-semibold text-muted" style="font-size:.8rem;">#${p.product_id}</td>
          <td>
            <div class="product-info-cell">
              <img src="${imgSrc}" alt="${escHtml(p.product_name)}"
                   class="product-img-admin"
                   onerror="this.src='${fallbackImage}'">
              <div class="product-name-text">${escHtml(p.product_name)}</div>
            </div>
          </td>
          <td><span class="category-badge">${escHtml(catName)}</span></td>
          <td class="text-center">
            <span class="price-unit-text">
              ${formatPrice(p.price)}đ<span class="unit-label">/${escHtml(p.unit || 'kg')}</span>
            </span>
          </td>
          <td class="text-center">
            <div class="fw-semibold mb-1" style="font-size:.95rem;">${p.stock}</div>
            <span class="badge-stock ${status.cls}">${status.label}</span>
          </td>
          <td class="text-center">
            <span class="sold-count">${p.sold}</span>
          </td>
          <td class="text-center pe-3" style="white-space:nowrap;">
            <button class="btn btn-action btn-action-edit me-1"
                    onclick="openEditModal('${escHtml(p.product_id)}')" title="Sửa">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button class="btn btn-action btn-action-delete"
                    onclick="openDeleteModal('${escHtml(p.product_id)}')" title="Xóa">
              <i class="bi bi-trash"></i>
            </button>
          </td>`;
        tbody.appendChild(tr);
    });

    updateInfoBar(start + 1, end, total);
    renderPagination();
}

function updateInfoBar(from, to, total) {
    const el = document.getElementById('paginationInfo');
    if (total === 0) { el.textContent = ''; return; }
    el.textContent = `Hiển thị ${from}–${to} trong tổng ${total} sản phẩm`;
}

// ── Pagination ───────────────────────────────────────────────
function renderPagination() {
    const totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE);
    const ul = document.getElementById('pagination');
    if (totalPages <= 1) { ul.innerHTML = ''; return; }

    const MAX_VISIBLE = 5;
    let startPage = Math.max(1, currentPage - Math.floor(MAX_VISIBLE / 2));
    let endPage   = Math.min(totalPages, startPage + MAX_VISIBLE - 1);
    if (endPage - startPage + 1 < MAX_VISIBLE) startPage = Math.max(1, endPage - MAX_VISIBLE + 1);

    let html = `
      <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1});return false;">
          <i class="bi bi-chevron-left"></i>
        </a>
      </li>`;

    if (startPage > 1) {
        html += pageBtn(1);
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    }
    for (let i = startPage; i <= endPage; i++) html += pageBtn(i);
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        html += pageBtn(totalPages);
    }

    html += `
      <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1});return false;">
          <i class="bi bi-chevron-right"></i>
        </a>
      </li>`;

    ul.innerHTML = html;
}

function pageBtn(i) {
    return `<li class="page-item ${i === currentPage ? 'active' : ''}">
      <a class="page-link" href="#" onclick="changePage(${i});return false;">${i}</a>
    </li>`;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderTable();
    document.querySelector('.card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Alert counters ───────────────────────────────────────────
function updateAlertCounters() {
    let low = 0, out = 0;
    products.forEach(p => {
        if (p.stock === 0)       out++;
        else if (p.stock < 10)   low++;
    });
    document.getElementById('countLowStock').textContent   = low;
    document.getElementById('countOutOfStock').textContent = out;
}

// ── Filters ──────────────────────────────────────────────────
function removeAccents(str) {
    if (!str) return '';
    return String(str)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase();
}

function applyFilters() {
    const searchRaw   = document.getElementById('searchInput').value.trim();
    const search      = removeAccents(searchRaw);
    const catFilter   = document.getElementById('filterCategory').value.trim();

    filteredProducts = products.filter(p => {
        const matchSearch = !search ||
            removeAccents(p.product_name).includes(search) ||
            removeAccents(String(p.product_id)).includes(search);
        const matchCat = !catFilter || String(p.category_id) === catFilter;
        return matchSearch && matchCat;
    });

    currentPage = 1;
    renderTable();
}

// ── Image upload handler ──────────────────────────────────────
function setupImageUpload(areaId, inputId, previewId) {
    const area    = document.getElementById(areaId);
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!area || !input) return;

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                area.classList.add('has-image');
                area.querySelector('.image-upload-icon')?.style && (area.querySelector('.image-upload-icon').style.display = 'none');
                area.querySelector('.image-upload-text').textContent = file.name;
            };
            reader.readAsDataURL(file);
        }
    });
}

function resetImageUpload(areaId, inputId, previewId) {
    const area    = document.getElementById(areaId);
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!area) return;
    input.value = '';
    preview.src = '';
    area.classList.remove('has-image');
    const icon = area.querySelector('.image-upload-icon');
    if (icon) icon.style.display = '';
    const text = area.querySelector('.image-upload-text');
    if (text) text.textContent = 'Kéo thả hoặc nhấp để chọn ảnh';
}

// ── Edit modal ───────────────────────────────────────────────
function openEditModal(id) {
    const p = products.find(x => String(x.product_id) === String(id));
    if (!p) return;

    document.getElementById('productModalLabel').textContent = 'Chỉnh sửa sản phẩm';
    document.getElementById('editProductId').value  = p.product_id;
    document.getElementById('editName').value       = p.product_name;
    document.getElementById('editCategory').value   = p.category_id || '';
    document.getElementById('editPrice').value      = p.price;
    document.getElementById('editStock').value      = p.stock;
    document.getElementById('editUnit').value       = (p.unit || 'kg').toLowerCase();
    document.getElementById('editImageName').value  = p.product_image || '';

    // Show existing image in preview
    const area    = document.getElementById('editImageArea');
    const preview = document.getElementById('editImagePreview');
    resetImageUpload('editImageArea', 'editImageFile', 'editImagePreview');
    if (p.product_image) {
        preview.src = MEDIA_PATH + p.product_image;
        area.classList.add('has-image');
        const text = area.querySelector('.image-upload-text');
        if (text) text.textContent = p.product_image;
    }

    productModal.show();
}

// ── Delete modal ─────────────────────────────────────────────
function openDeleteModal(id) {
    const p = products.find(x => String(x.product_id) === String(id));
    if (!p) return;
    document.getElementById('deleteProductId').value      = p.product_id;
    document.getElementById('deleteProductName').textContent = p.product_name;
    deleteModal.show();
}

// ── Utility ──────────────────────────────────────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

function showToast(message, type = 'success') {
    const wrap = document.createElement('div');
    const cls  = type === 'success' ? 'farm-toast-success'
               : type === 'warning' ? 'farm-toast-warning'
               : 'farm-toast-danger';
    const icon = type === 'success' ? 'bi-check-circle-fill'
               : type === 'warning' ? 'bi-exclamation-circle-fill'
               : 'bi-x-circle-fill';
    wrap.className = `farm-toast ${cls}`;
    wrap.innerHTML = `<i class="bi ${icon}" style="font-size:1.1rem;flex-shrink:0;"></i><span>${message}</span>`;
    document.body.appendChild(wrap);
    setTimeout(() => {
        wrap.style.transition = 'opacity .3s, transform .3s';
        wrap.style.opacity    = '0';
        wrap.style.transform  = 'translateX(40px)';
        setTimeout(() => wrap.remove(), 320);
    }, 3000);
}

// ── DOMContentLoaded ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    deleteModal  = new bootstrap.Modal(document.getElementById('deleteModal'));

    setupImageUpload('editImageArea', 'editImageFile', 'editImagePreview');

    renderTable();
    updateAlertCounters();

    // Search & filter
    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('filterCategory').addEventListener('change', applyFilters);

    // Add product button
    document.getElementById('btnAddProduct').addEventListener('click', () => {
        document.getElementById('productModalLabel').textContent = 'Thêm sản phẩm mới';
        document.getElementById('editProductId').value  = '';
        document.getElementById('editName').value       = '';
        document.getElementById('editCategory').value   = '';
        document.getElementById('editPrice').value      = '';
        document.getElementById('editStock').value      = '';
        document.getElementById('editUnit').value       = 'kg';
        document.getElementById('editImageName').value  = '';
        resetImageUpload('editImageArea', 'editImageFile', 'editImagePreview');
        productModal.show();
    });

    // Save product
    document.getElementById('saveProductBtn').addEventListener('click', async () => {
        const editId = document.getElementById('editProductId').value.trim();
        const name   = document.getElementById('editName').value.trim();
        const catId  = document.getElementById('editCategory').value;
        const price  = parseInt(document.getElementById('editPrice').value) || 0;
        const stock  = parseInt(document.getElementById('editStock').value) || 0;
        const unit   = document.getElementById('editUnit').value;

        // Image: prefer newly uploaded file name, fall back to text field
        const fileInput = document.getElementById('editImageFile');
        const fallbackImageName = document.getElementById('editImageName').value.trim();

        if (!name)       { showToast('Vui lòng nhập tên sản phẩm', 'warning'); return; }
        if (!catId)      { showToast('Vui lòng chọn danh mục', 'warning');     return; }
        if (price <= 0)  { showToast('Vui lòng nhập giá bán hợp lệ', 'warning'); return; }

        const formData = new FormData();
        formData.append('action', editId ? 'edit' : 'add');
        if (editId) formData.append('product_id', editId);
        formData.append('product_name', name);
        formData.append('category_id', catId);
        formData.append('price', price);
        formData.append('stock', stock);
        formData.append('unit', unit);
        formData.append('product_image_name', fallbackImageName);

        if (fileInput.files.length > 0) {
            formData.append('image_file', fileInput.files[0]);
        }

        const btn = document.getElementById('saveProductBtn');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...';
        btn.disabled = true;

        try {
            const response = await fetch('../../../app/controllers/admin/ProductAdminController.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                productModal.hide();
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                showToast(result.message || 'Lỗi lưu sản phẩm!', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Lỗi kết nối đến server.', 'danger');
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    });

    // Confirm delete
    document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
        const id  = document.getElementById('deleteProductId').value;
        
        const btn = document.getElementById('confirmDeleteBtn');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xóa...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('product_id', id);

        try {
            const response = await fetch('../../../app/controllers/admin/ProductAdminController.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                deleteModal.hide();
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                showToast(result.message || 'Lỗi xóa sản phẩm!', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Lỗi kết nối đến server.', 'danger');
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    });

    // Mobile sidebar
    document.getElementById('toggleSidebar')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('open');
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.addEventListener('click', () => {
                document.querySelector('.sidebar').classList.remove('open');
                overlay.classList.remove('show');
            });
            document.body.appendChild(overlay);
        }
        overlay.classList.toggle('show');
    });
});