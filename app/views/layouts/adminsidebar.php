<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$pendingReturns = $pendingReturns ?? 0;
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo" style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px; padding-bottom: 24px; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,.1);">
        <img src="../../../Media/Logo-trang.png" alt="Logo Farm2Home" style="width: 160px; height: auto; object-fit: contain;">
        <div class="logo-text" style="font-size: 12px; font-weight: 400; opacity: 0.7; letter-spacing: 0.5px; padding-left: 4px;">Admin</div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
        <a href="../../../app/controllers/admin/DashboardController.php" class="nav-btn <?= ($currentPage === 'Dashboard.php' || $currentPage === 'DashboardController.php') ? 'active' : '' ?>">
            <span class="material-symbols-outlined">dashboard</span>Dashboard
        </a>

        <a href="../../../app/views/admin/ProductAdmin.php" class="nav-btn <?= ($currentPage === 'ProductAdmin.php') ? 'active' : '' ?>">
            <span class="material-symbols-outlined">storefront</span>Sản phẩm
        </a>

        <a href="../../../app/views/admin/ControlOrder.php" class="nav-btn <?= ($currentPage === 'ControlOrder.php') ? 'active' : '' ?>">
            <span class="material-symbols-outlined">receipt_long</span>Đơn hàng
            <?php if ($pendingReturns > 0): ?>
                <span class="notif-dot"><?= $pendingReturns ?></span>
            <?php endif; ?>
        </a>

        <a href="../../../app/views/admin/ProfileAdmin.php" class="nav-btn <?= ($currentPage === 'ProfileAdmin.php') ? 'active' : '' ?>">
            <span class="material-symbols-outlined">person</span>Tài khoản
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="../../../app/views/customer/logout.php" class="nav-btn" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi tài khoản không?');">
            <span class="material-symbols-outlined">logout</span>Đăng xuất
        </a>
    </div>
</div>
