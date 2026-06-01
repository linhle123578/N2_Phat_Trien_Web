<?php
ob_start();
include_once __DIR__ . '/../layouts/header.php';
?>
﻿<?php

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
mysqli_real_connect(
    $conn,
    "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
    "3YHrkxqAKWynehu.root",
    "BzDRrZAdAT2jLuyd",
    "db_web_farm2home",
    4000,
    NULL,
    MYSQLI_CLIENT_SSL
);
mysqli_set_charset($conn, "utf8mb4");

// -- Tải dữ liệu customer + account ---------------------------------------------
$session_customer_id = $_SESSION['customer_id'] ?? 'CUS005';

$customer = [
    'customer_id'  => $session_customer_id,
    'full_name'    => '',
    'phone'        => '',
    'gender'       => '',
    'email'        => '',
    'account_id'   => '',
    'orders'       => 0,
];

try {
    $sql = "
        SELECT c.customer_id, c.full_name, c.phone, c.gender,
               a.email, a.account_id
        FROM customer c
        LEFT JOIN account a ON c.account_id = a.account_id
        WHERE c.customer_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $session_customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $customer['customer_id'] = $row['customer_id'] ?? $customer['customer_id'];
        $customer['full_name']   = $row['full_name']   ?? $customer['full_name'];
        $customer['phone']       = $row['phone']       ?? $customer['phone'];
        $customer['gender']      = $row['gender']      ?? $customer['gender'];
        $customer['email']       = $row['email']       ?? $customer['email'];
        $customer['account_id']  = $row['account_id']  ?? $customer['account_id'];
    }
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($conn, "SELECT COUNT(*) FROM `order` WHERE customer_id = ?");
    mysqli_stmt_bind_param($stmt2, 's', $session_customer_id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_bind_result($stmt2, $cnt);
    mysqli_stmt_fetch($stmt2);
    $customer['orders'] = (int)$cnt;
    mysqli_stmt_close($stmt2);
} catch (Exception $e) {}

// -- Tải địa chỉ ------------------------------------------------------------
$addresses = [];
try {
    $sql_addr = "SELECT address_id, receiver_name, address_type,
                        province, district, ward, street_address, is_default
                 FROM address WHERE customer_id = ? ORDER BY is_default DESC, address_id ASC";
    $stmt3 = mysqli_prepare($conn, $sql_addr);
    mysqli_stmt_bind_param($stmt3, 's', $session_customer_id);
    mysqli_stmt_execute($stmt3);
    $res_addr = mysqli_stmt_get_result($stmt3);
    while ($r = mysqli_fetch_assoc($res_addr)) {
        $addresses[] = $r;
    }
    mysqli_stmt_close($stmt3);
} catch (Exception $e) {}

// -- Xử lý POST ---------------------------------------------------------------
$msg_profile  = '';
$msg_password = '';
$msg_address  = '';

function pc_db_connect() {
    $c = mysqli_init();
    mysqli_ssl_set($c, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($c, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    mysqli_real_connect($c,
        "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
        "3YHrkxqAKWynehu.root", "BzDRrZAdAT2jLuyd",
        "db_web_farm2home", 4000, NULL, MYSQLI_CLIENT_SSL);
    mysqli_set_charset($c, "utf8mb4");
    return $c;
}

function reload_addresses(string $cid): array {
    $list = [];
    try {
        $c2 = pc_db_connect();
        $stmt_r = mysqli_prepare($c2,
            "SELECT address_id, receiver_name, address_type,
                    province, district, ward, street_address, is_default
             FROM address WHERE customer_id = ? ORDER BY is_default DESC, address_id ASC");
        mysqli_stmt_bind_param($stmt_r, 's', $cid);
        mysqli_stmt_execute($stmt_r);
        $res2 = mysqli_stmt_get_result($stmt_r);
        while ($r = mysqli_fetch_assoc($res2)) $list[] = $r;
        mysqli_stmt_close($stmt_r);
        mysqli_close($c2);
    } catch (Exception $e) {}
    return $list;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -- Lưu thông tin customer -----------------------------
    if (isset($_POST['save_profile'])) {
        $fn  = trim($_POST['full_name'] ?? '');
        $ph  = trim($_POST['phone']     ?? '');
        $gen = trim($_POST['gender']    ?? '');
        $em  = trim($_POST['email']     ?? '');
        $cid = $customer['customer_id'];
        $acct = $customer['account_id'];

        try {
            $c = pc_db_connect();

            $stmt = mysqli_prepare($c,
                "UPDATE customer SET full_name=?, phone=?, gender=? WHERE customer_id=?");
            mysqli_stmt_bind_param($stmt, 'ssss', $fn, $ph, $gen, $cid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($acct) {
                $stmt2 = mysqli_prepare($c,
                    "UPDATE account SET email=? WHERE account_id=?");
                mysqli_stmt_bind_param($stmt2, 'ss', $em, $acct);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }
            mysqli_close($c);

            $customer['full_name'] = htmlspecialchars($fn);
            $customer['phone']     = htmlspecialchars($ph);
            $customer['gender']    = htmlspecialchars($gen);
            $customer['email']     = htmlspecialchars($em);
            $msg_profile = 'success';
        } catch (Exception $e) {
            $msg_profile = 'error';
        }
    }

    // -- Đổi mật khẩu ---------------------------------------------------------
    if (isset($_POST['save_password'])) {
        $old_pw  = $_POST['old_password']     ?? '';
        $new_pw  = $_POST['new_password']     ?? '';
        $conf_pw = $_POST['confirm_password'] ?? '';
        $acct    = $customer['account_id'];

        if ($new_pw !== $conf_pw || strlen($new_pw) < 6) {
            $msg_password = 'error';
        } elseif (!$acct) {
            $msg_password = 'error';
        } else {
            try {
                $c = pc_db_connect();
                $sv = mysqli_prepare($c,
                    "SELECT account_password FROM account WHERE account_id=? LIMIT 1");
                mysqli_stmt_bind_param($sv, 's', $acct);
                mysqli_stmt_execute($sv);
                mysqli_stmt_bind_result($sv, $stored_pw);
                mysqli_stmt_fetch($sv);
                mysqli_stmt_close($sv);

                if (!$stored_pw || md5($old_pw) !== $stored_pw) {
                    $msg_password = 'wrong_old';
                } else {
                    $hashed = md5($new_pw);
                    $su = mysqli_prepare($c,
                        "UPDATE account SET account_password=? WHERE account_id=?");
                    mysqli_stmt_bind_param($su, 'ss', $hashed, $acct);
                    mysqli_stmt_execute($su);
                    mysqli_stmt_close($su);
                    $msg_password = 'success';
                }
                mysqli_close($c);
            } catch (Exception $e) {
                $msg_password = 'error';
            }
        }
    }

    // -- Thêm địa chỉ ---------------------------------------------------------
    if (isset($_POST['add_address'])) {
        $r_name    = trim($_POST['receiver_name']   ?? '');
        $r_phone   = trim($_POST['addr_phone']      ?? '');
        $addr_type = trim($_POST['addr_type']       ?? 'Nhà');
        $province  = trim($_POST['addr_province']   ?? '');
        $district  = trim($_POST['addr_district']   ?? '');
        $ward      = trim($_POST['addr_ward']       ?? '');
        $street    = trim($_POST['addr_street']     ?? '');
        $is_def    = isset($_POST['addr_is_default']) ? 1 : 0;
        $cid       = $customer['customer_id'];
        $new_id    = 'ADDR_' . uniqid();

        if (empty($r_name) || empty($street)) {
            $msg_address = 'error';
        } else {
            $c = pc_db_connect();
            if ($is_def) {
                $su = mysqli_prepare($c, "UPDATE address SET is_default=0 WHERE customer_id=?");
                mysqli_stmt_bind_param($su, 's', $cid);
                mysqli_stmt_execute($su);
                mysqli_stmt_close($su);
            }
            $si = mysqli_prepare($c,
                "INSERT INTO address
                    (address_id, customer_id, receiver_name,
                     address_type, province, district, ward, street_address, is_default)
                 VALUES (?,?,?,?,?,?,?,?,?)");
            if ($si) {
                mysqli_stmt_bind_param($si, 'ssssssssi',
                    $new_id, $cid, $r_name,
                    $addr_type, $province, $district, $ward, $street, $is_def);
                if (mysqli_stmt_execute($si)) {
                    $msg_address = 'add_success';
                } else {
                    $msg_address = 'db_error:' . mysqli_stmt_error($si);
                }
                mysqli_stmt_close($si);
            } else {
                $msg_address = 'prepare_error:' . mysqli_error($c);
            }
            mysqli_close($c);
        }
        $addresses = reload_addresses($cid);
    }

    // -- Đặt địa chỉ mặc định -------------------------------------------------
    if (isset($_POST['set_default_address'])) {
        $addr_id = trim($_POST['address_id'] ?? '');
        $cid     = $customer['customer_id'];
        try {
            $c = pc_db_connect();
            $su = mysqli_prepare($c,
                "UPDATE address SET is_default=0 WHERE customer_id=?");
            mysqli_stmt_bind_param($su, 's', $cid);
            mysqli_stmt_execute($su);
            mysqli_stmt_close($su);
            $sd = mysqli_prepare($c,
                "UPDATE address SET is_default=1 WHERE address_id=? AND customer_id=?");
            mysqli_stmt_bind_param($sd, 'ss', $addr_id, $cid);
            mysqli_stmt_execute($sd);
            mysqli_stmt_close($sd);
            mysqli_close($c);
            $msg_address = 'default_set';
        } catch (Exception $e) {}
        $addresses = reload_addresses($cid);
    }

    // ?? Xóa Địa chỉ 
    if (isset($_POST['delete_address'])) {
        $addr_id = trim($_POST['address_id'] ?? '');
        $cid     = $customer['customer_id'];
        try {
            $c = pc_db_connect();
            $sd = mysqli_prepare($c,
                "DELETE FROM address WHERE address_id=? AND customer_id=?");
            mysqli_stmt_bind_param($sd, 'ss', $addr_id, $cid);
            mysqli_stmt_execute($sd);
            mysqli_stmt_close($sd);
            mysqli_close($c);
            $msg_address = 'delete_success';
        } catch (Exception $e) {}
        $addresses = reload_addresses($cid);
    }
}

mysqli_close($conn);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Địa chỉ đầy đủ string từ các cột riêng lẻ
function build_full_address(array $addr): string {
    $parts = array_filter([
        $addr['street_address'] ?? '',
        $addr['ward']           ?? '',
        $addr['district']       ?? '',
        $addr['province']       ?? '',
    ]);
    return implode(', ', $parts);
}

$extra_head = '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/ProfileCustomer.css">
';
echo $extra_head;
?>


<div class="container" style="padding-top: 80px;">

    <!-- Breadcrumb -->
    <nav class="profile-breadcrumb">
        <a href="../../../public/index.php">Trang chủ</a>
        <span class="sep">›</span>
        <span class="current">Tài khoản của tôi</span>
    </nav>

    <!-- ------------ MAIN LAYOUT ------------ -->
    <div class="profile-layout">

        <!-- -- SIDEBAR ------------------------------------ -->
        <aside class="profile-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-title">MENU TÀI KHOẢN</div>
                <ul class="sidebar-menu">
                    <li class="active">
                        <a href="ProfileCustomer.php">
                            <i class="bi bi-person-circle"></i>
                            Thông tin cá nhân
                        </a>
                    </li>
                    <li>
                        <a href="OrderHistory.php">
                            <i class="bi bi-bag-check"></i>
                            Lịch sử đơn hàng
                            <?php if ($customer['orders'] > 0): ?>
                                <span class="sidebar-badge"><?= (int)$customer['orders'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="sidebar-divider"></div>
                <div class="sidebar-logout">
                    <a href="logout.php" id="btnLogout">
                        <i class="bi bi-box-arrow-right"></i>
                        Đăng xuất
                    </a>


                </div>
            </div>
        </aside>

        <!-- -- MAIN CONTENT ------------------------------- -->
        <div class="profile-main-solo">

        <!-- -- Personal Info -------------------- -->
        <div class="section-card">
            <div class="section-card-header">
                <div class="sec-icon"><i class="bi bi-person-fill"></i></div>
                <h3>Thông tin cá nhân</h3>
            </div>

            <?php if ($msg_profile === 'success'): ?>
                <div class="pc-alert pc-alert-success mx-22 mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>Lưu thông tin thành công!
                </div>
            <?php elseif ($msg_profile === 'error'): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>Có lỗi xảy ra, vui lòng thử lại.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-body-pad">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldName">Họ và tên</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="fieldName" name="full_name"
                                       value="<?= e($customer['full_name']) ?>"
                                       placeholder="Nhập họ và tên" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldPhone">Số điện thoại</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="fieldPhone" name="phone"
                                       value="<?= e($customer['phone']) ?>"
                                       placeholder="VD: 0901234567">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldEmail">Email</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="fieldEmail" name="email"
                                       value="<?= e($customer['email']) ?>"
                                       placeholder="email@example.com">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldGender">Giới tính</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-gender-ambiguous"></i></span>
                                <select class="form-select" id="fieldGender" name="gender"
                                        style="border-left:none;border-radius:0 var(--radius-input) var(--radius-input) 0;">
                                    <option value="Nam"  <?= $customer['gender'] === 'Nam'  ? 'selected' : '' ?>>Nam</option>
                                    <option value="Nữ" <?= $customer['gender'] === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="Khác" <?= $customer['gender'] === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" name="save_profile" class="btn-save-main">
                                <i class="bi bi-floppy me-2"></i>Lưu thông tin
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- -- Addresses ------------------------- -->
        <div class="section-card">
            <div class="section-card-header">
                <div class="sec-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <h3>Địa chỉ giao hàng</h3>
                <button class="btn-add-addr" type="button" id="btnShowAddAddr">
                    <i class="bi bi-plus-circle"></i> Thêm địa chỉ
                </button>
            </div>

            <?php if ($msg_address === 'add_success'): ?>
                <div class="pc-alert pc-alert-success mx-22 mb-3"><i class="bi bi-check-circle-fill me-2"></i>Thêm địa chỉ thành công!</div>
            <?php elseif ($msg_address === 'delete_success'): ?>
                <div class="pc-alert pc-alert-success mx-22 mb-3"><i class="bi bi-check-circle-fill me-2"></i>Đã xoá địa chỉ.</div>
            <?php elseif ($msg_address === 'default_set'): ?>
                <div class="pc-alert pc-alert-success mx-22 mb-3"><i class="bi bi-check-circle-fill me-2"></i>Đã đặt địa chỉ mặc định.</div>
            <?php elseif ($msg_address === 'error'): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3"><i class="bi bi-exclamation-circle-fill me-2"></i>Vui lòng điền đầy đủ thông tin bắt buộc (Người nhận, Số nhà/đường).</div>
            <?php elseif (str_starts_with($msg_address, 'db_error:') || str_starts_with($msg_address, 'prepare_error:')): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3"><i class="bi bi-exclamation-circle-fill me-2"></i>Lỗi DB: <?= htmlspecialchars($msg_address) ?></div>
            <?php endif; ?>

            <div class="add-addr-form" id="addAddrForm" style="display:none;">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Người nhận <span class="text-danger">*</span></label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" name="receiver_name"
                                       placeholder="Tên người nhận" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Loại địa chỉ</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                <select class="form-select" name="addr_type"
                                        style="border-left:none;border-radius:0 var(--radius-input) var(--radius-input) 0;">
                                    <option value="Nhà">Nhà</option>
                                    <option value="Văn phòng">Văn phòng</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-map"></i></span>
                                <input type="text" class="form-control" name="addr_province"
                                       placeholder="VD: TP. Hồ Chí Minh" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Quận / Huyện</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-signpost-2"></i></span>
                                <input type="text" class="form-control" name="addr_district"
                                       placeholder="VD: Quận 1">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Phường / Xã</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-signpost"></i></span>
                                <input type="text" class="form-control" name="addr_ward"
                                       placeholder="VD: Phường Bến Nghé">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Số nhà, tên đường <span class="text-danger">*</span></label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" name="addr_street"
                                       placeholder="VD: 123 Nguyễn Huệ" required>
                            </div>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                            <label class="pc-checkbox-label">
                                <input type="checkbox" name="addr_is_default" value="1">
                                <span>Đặt làm địa chỉ mặc định</span>
                            </label>
                            <div class="ms-auto d-flex gap-2">
                                <button type="button" class="btn-cancel-addr" id="btnCancelAddAddr">Huỷ</button>
                                <button type="submit" name="add_address" class="btn-save-main">
                                    <i class="bi bi-plus-circle me-1"></i>Thêm địa chỉ
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="address-list">
                <?php if (empty($addresses)): ?>
                    <div class="addr-empty">
                        <i class="bi bi-geo-alt"></i>
                        <p>Bạn chưa có địa chỉ giao hàng nào.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                    <div class="address-item <?= $addr['is_default'] ? 'is-default' : '' ?>">
                        <div class="addr-icon">
                            <i class="bi bi-<?= $addr['is_default'] ? 'house-fill' : 'geo-alt' ?>"></i>
                        </div>
                        <div class="addr-info">
                            <div class="addr-title"><?= e($addr['receiver_name']) ?></div>
                            <?php if (!empty($addr['address_type'])): ?>
                                <div class="addr-type">
                                    <span class="badge bg-secondary"><?= e($addr['address_type']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="addr-text"><?= e(build_full_address($addr)) ?></div>
                        </div>
                        <div class="addr-actions">
                            <?php if ($addr['is_default']): ?>
                                <span class="default-badge">
                                    <i class="bi bi-patch-check-fill me-1"></i>Mặc định
                                </span>
                            <?php else: ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="address_id"
                                           value="<?= e($addr['address_id']) ?>">
                                    <button type="submit" name="set_default_address"
                                            class="set-default-link">Đặt mặc định</button>
                                </form>
                                <form method="POST" action="" style="display:inline;"
                                      onsubmit="return confirm('Bạn có chắc muốn xoá địa chỉ này?')">
                                    <input type="hidden" name="address_id"
                                           value="<?= e($addr['address_id']) ?>">
                                    <button type="submit" name="delete_address"
                                            class="btn-addr-del" title="Xoá">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- -- Password -------------------------- -->
        <div class="section-card">
            <div class="section-card-header">
                <div class="sec-icon"><i class="bi bi-shield-lock-fill"></i></div>
                <h3>Bảo mật tài khoản</h3>
            </div>

            <?php if ($msg_password === 'success'): ?>
                <div class="pc-alert pc-alert-success mx-22 mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>Đổi mật khẩu thành công!
                </div>
            <?php elseif ($msg_password === 'wrong_old'): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>Mật khẩu hiện tại không đúng.
                </div>
            <?php elseif ($msg_password === 'error'): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>Mật khẩu không hợp lệ hoặc không khớp (tối thiểu 6 ký tự).
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-body-pad">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label" for="fieldPwOld">Mật khẩu hiện tại</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="fieldPwOld"
                                       name="old_password" placeholder="••••••••">
                                <button class="btn-eye" type="button" data-target="fieldPwOld">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="fieldPwNew">Mật khẩu mới</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="fieldPwNew"
                                       name="new_password" placeholder="Tối thiểu 6 ký tự">
                                <button class="btn-eye" type="button" data-target="fieldPwNew">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="fieldPwConfirm">Xác nhận mật khẩu mới</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="fieldPwConfirm"
                                       name="confirm_password" placeholder="Nhập lại mật khẩu mới">
                                <button class="btn-eye" type="button" data-target="fieldPwConfirm">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" name="save_password" class="btn-save-main">
                                <i class="bi bi-shield-check me-2"></i>Đổi mật khẩu
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

    </div><!-- /.profile-layout -->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../public/assets/js/ProfileCustomer.js"></script>


<!-- ── Modal xác nhận đăng xuất  ── -->
<div id="logoutOverlay" style="
    display:none;
    position:fixed;
    top:0;left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.55);
    z-index:99999;
    align-items:center;
    justify-content:center;
">
    <div style="
        background:#fff;
        border-radius:18px;
        padding:36px 28px 28px;
        max-width:360px;
        width:90%;
        box-shadow:0 20px 60px rgba(0,0,0,0.25);
        text-align:center;
        font-family:'Plus Jakarta Sans',sans-serif;
        position:relative;
        z-index:100000;
    ">
        <div style="width:60px;height:60px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.6rem;color:#c0392b;">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        <div style="font-size:1.08rem;font-weight:800;color:#1a2e1c;margin-bottom:8px;">Đăng xuất?</div>
        <div style="font-size:0.88rem;color:#6b7c6e;margin-bottom:24px;line-height:1.6;">Bạn có chắc muốn đăng xuất khỏi tài khoản không?</div>
        <div style="display:flex;gap:10px;">
            <button id="btnLogoutCancel" style="flex:1;padding:11px;border-radius:999px;border:1.5px solid #dde8da;background:none;font-weight:600;font-size:0.9rem;color:#6b7c6e;cursor:pointer;font-family:inherit;transition:background .15s;">Huỷ</button>
            <a href="logout.php" style="flex:1;padding:11px;border-radius:999px;border:none;background:#c0392b;color:#fff;font-weight:700;font-size:0.9rem;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;transition:background .15s;">Đăng xuất</a>
        </div>
    </div>
</div>


<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
