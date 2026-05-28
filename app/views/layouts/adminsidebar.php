<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Farm2Home – Quản lý đơn hàng</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
<?php
define('BASE_URL', '/N2_Phat_Trien_Web/');
$pendingReturns = 0;
?>
<!-- FIX CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/AdminSidebar.css">
</head>

<body>

<div class="sidebar" id="sidebar">

    <div class="sidebar-logo">
        <img src="<?= BASE_URL ?>Media/Logo-trang.png"
             alt="Logo Farm2Home"
             style="width:38px; height:38px;">

        <div class="logo-text">
            Farm2Home
            <small>Admin Dashboard</small>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:3px">

        <a href="<?= BASE_URL ?>app/views/admin/Dashboard.php" class="nav-btn">
            <span class="material-symbols-outlined">bar_chart</span>
            Thống kê
        </a>

        <a href="<?= BASE_URL ?>app/views/admin/ProductAdmin.php" class="nav-btn">
            <span class="material-symbols-outlined">storefront</span>
            Quản Lý Sản phẩm
        </a>

        <a href="<?= BASE_URL ?>app/views/admin/ControlOrder.php" class="nav-btn active">
            <span class="material-symbols-outlined">receipt_long</span>
            Quản Lý Đơn hàng

            <?php $pendingReturns = $pendingReturns ?? 0; ?>

            <?php if ($pendingReturns > 0): ?>
                <span class="notif-dot"><?= $pendingReturns ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>app/views/admin/ProfileAdmin.php" class="nav-btn">
            <span class="material-symbols-outlined">person</span>
            Tài khoản
        </a>

    </div>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>app/views/customer/Logout.php" class="nav-btn">
            <span class="material-symbols-outlined">logout</span>
            Đăng xuất
        </a>
    </div>

</div>

</body>
</html>