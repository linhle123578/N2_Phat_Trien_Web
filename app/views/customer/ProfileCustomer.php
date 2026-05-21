<?php
$BASE_URL = '/n2_phat_trien_web';

// ── DB connection ────────────────────────────────────────────────────────────
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
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

// ── Load customer + account data ─────────────────────────────────────────────
// Assumes session has customer_id; fallback to CUS001 for demo
$session_customer_id = $_SESSION['customer_id'] ?? 'CUS001';

$customer = [
    'customer_id'  => $session_customer_id,
    'full_name'    => 'Nguyễn Thị Hương',
    'phone'        => '0912 345 678',
    'gender'       => 'Nữ',
    'email'        => 'huong.nguyen@gmail.com',
    'avatar'       => 'user_1.jpg',
    'account_id'   => 'ACC001',
    'orders'       => 0,
];

try {
    $sql = "
        SELECT c.customer_id, c.full_name, c.phone, c.gender,
               a.email, a.avatar, a.account_id
        FROM customer c
        LEFT JOIN account a ON c.customer_id = a.account_id
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
        $customer['avatar']      = $row['avatar']      ?? $customer['avatar'];
        $customer['account_id']  = $row['account_id']  ?? $customer['account_id'];
    }
    mysqli_stmt_close($stmt);

    // Count orders
    $stmt2 = mysqli_prepare($conn, "SELECT COUNT(*) FROM `order` WHERE customer_id = ?");
    mysqli_stmt_bind_param($stmt2, 's', $session_customer_id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_bind_result($stmt2, $cnt);
    mysqli_stmt_fetch($stmt2);
    $customer['orders'] = (int)$cnt;
    mysqli_stmt_close($stmt2);
} catch (Exception $e) {
    // keep fallback
}

// ── Load addresses ────────────────────────────────────────────────────────────
$addresses = [];
try {
    $sql_addr = "SELECT address_id, receiver_name, phone, detail, is_default
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

// ── Handle POST ───────────────────────────────────────────────────────────────
$msg_profile  = '';
$msg_password = '';
$msg_address  = '';

function pc_db_connect() {
    $c = mysqli_init();
    mysqli_ssl_set($c, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($c,
        "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
        "3YHrkxqAKWynehu.root", "BzDRrZAdAT2jLuyd",
        "db_web_farm2home", 4000, NULL, MYSQLI_CLIENT_SSL);
    mysqli_set_charset($c, "utf8mb4");
    return $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Save profile ──────────────────────────────────
    if (isset($_POST['save_profile'])) {
        $fn     = trim($_POST['full_name'] ?? '');
        $ph     = trim($_POST['phone']     ?? '');
        $gen    = trim($_POST['gender']    ?? '');
        $em     = trim($_POST['email']     ?? '');
        $cid    = $customer['customer_id'];
        $acct   = $customer['account_id'];

        // Handle avatar upload
        $avatar_filename = $customer['avatar'];
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['avatar']['type'], $allowed) && $_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $new_name = 'avatar_' . $cid . '_' . time() . '.' . $ext;
                $upload_path = '../../../Media/' . $new_name;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    $avatar_filename = $new_name;
                }
            }
        }

        try {
            $c = pc_db_connect();
            $stmt = mysqli_prepare($c,
                "UPDATE customer SET full_name=?, phone=?, gender=? WHERE customer_id=?");
            mysqli_stmt_bind_param($stmt, 'ssss', $fn, $ph, $gen, $cid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($acct) {
                $stmt2 = mysqli_prepare($c,
                    "UPDATE account SET email=?, avatar=? WHERE account_id=?");
                mysqli_stmt_bind_param($stmt2, 'sss', $em, $avatar_filename, $acct);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }
            mysqli_close($c);

            $customer['full_name'] = htmlspecialchars($fn);
            $customer['phone']     = htmlspecialchars($ph);
            $customer['gender']    = htmlspecialchars($gen);
            $customer['email']     = htmlspecialchars($em);
            $customer['avatar']    = htmlspecialchars($avatar_filename);
            $msg_profile = 'success';
        } catch (Exception $e) {
            $msg_profile = 'error';
        }
    }

    // ── Save password ──────────────────────────────────
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
                $sv = mysqli_prepare($c, "SELECT account_password FROM account WHERE account_id=? LIMIT 1");
                mysqli_stmt_bind_param($sv, 's', $acct);
                mysqli_stmt_execute($sv);
                mysqli_stmt_bind_result($sv, $stored_pw);
                mysqli_stmt_fetch($sv);
                mysqli_stmt_close($sv);

                if (!$stored_pw || !password_verify($old_pw, $stored_pw)) {
                    $msg_password = 'wrong_old';
                } else {
                    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                    $su = mysqli_prepare($c, "UPDATE account SET account_password=? WHERE account_id=?");
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

    // ── Add address ────────────────────────────────────
    if (isset($_POST['add_address'])) {
        $r_name   = trim($_POST['receiver_name'] ?? '');
        $r_phone  = trim($_POST['addr_phone']    ?? '');
        $r_detail = trim($_POST['addr_detail']   ?? '');
        $is_def   = isset($_POST['addr_is_default']) ? 1 : 0;
        $cid      = $customer['customer_id'];
        $new_id   = 'ADDR_' . uniqid();

        try {
            $c = pc_db_connect();
            if ($is_def) {
                $su = mysqli_prepare($c, "UPDATE address SET is_default=0 WHERE customer_id=?");
                mysqli_stmt_bind_param($su, 's', $cid);
                mysqli_stmt_execute($su);
                mysqli_stmt_close($su);
            }
            $si = mysqli_prepare($c,
                "INSERT INTO address (address_id, customer_id, receiver_name, phone, detail, is_default) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($si, 'sssssi', $new_id, $cid, $r_name, $r_phone, $r_detail, $is_def);
            mysqli_stmt_execute($si);
            mysqli_stmt_close($si);
            mysqli_close($c);
            $msg_address = 'add_success';
        } catch (Exception $e) {
            $msg_address = 'error';
        }
        // Reload addresses
        $addresses = [];
        try {
            $c2 = pc_db_connect();
            $stmt_r = mysqli_prepare($c2, "SELECT address_id, receiver_name, phone, detail, is_default
                         FROM address WHERE customer_id = ? ORDER BY is_default DESC, address_id ASC");
            mysqli_stmt_bind_param($stmt_r, 's', $cid);
            mysqli_stmt_execute($stmt_r);
            $res2 = mysqli_stmt_get_result($stmt_r);
            while ($r = mysqli_fetch_assoc($res2)) $addresses[] = $r;
            mysqli_stmt_close($stmt_r);
            mysqli_close($c2);
        } catch (Exception $e) {}
    }

    // ── Set default address ────────────────────────────
    if (isset($_POST['set_default_address'])) {
        $addr_id = trim($_POST['address_id'] ?? '');
        $cid     = $customer['customer_id'];
        try {
            $c = pc_db_connect();
            $su = mysqli_prepare($c, "UPDATE address SET is_default=0 WHERE customer_id=?");
            mysqli_stmt_bind_param($su, 's', $cid);
            mysqli_stmt_execute($su);
            mysqli_stmt_close($su);
            $sd = mysqli_prepare($c, "UPDATE address SET is_default=1 WHERE address_id=? AND customer_id=?");
            mysqli_stmt_bind_param($sd, 'ss', $addr_id, $cid);
            mysqli_stmt_execute($sd);
            mysqli_stmt_close($sd);
            mysqli_close($c);
            $msg_address = 'default_set';
        } catch (Exception $e) {}
        $addresses = [];
        try {
            $c2 = pc_db_connect();
            $stmt_r = mysqli_prepare($c2, "SELECT address_id, receiver_name, phone, detail, is_default
                         FROM address WHERE customer_id = ? ORDER BY is_default DESC, address_id ASC");
            mysqli_stmt_bind_param($stmt_r, 's', $cid);
            mysqli_stmt_execute($stmt_r);
            $res2 = mysqli_stmt_get_result($stmt_r);
            while ($r = mysqli_fetch_assoc($res2)) $addresses[] = $r;
            mysqli_stmt_close($stmt_r);
            mysqli_close($c2);
        } catch (Exception $e) {}
    }

    // ── Delete address ─────────────────────────────────
    if (isset($_POST['delete_address'])) {
        $addr_id = trim($_POST['address_id'] ?? '');
        $cid     = $customer['customer_id'];
        try {
            $c = pc_db_connect();
            $sd = mysqli_prepare($c, "DELETE FROM address WHERE address_id=? AND customer_id=?");
            mysqli_stmt_bind_param($sd, 'ss', $addr_id, $cid);
            mysqli_stmt_execute($sd);
            mysqli_stmt_close($sd);
            mysqli_close($c);
            $msg_address = 'delete_success';
        } catch (Exception $e) {}
        $addresses = [];
        try {
            $c2 = pc_db_connect();
            $stmt_r = mysqli_prepare($c2, "SELECT address_id, receiver_name, phone, detail, is_default
                         FROM address WHERE customer_id = ? ORDER BY is_default DESC, address_id ASC");
            mysqli_stmt_bind_param($stmt_r, 's', $cid);
            mysqli_stmt_execute($stmt_r);
            $res2 = mysqli_stmt_get_result($stmt_r);
            while ($r = mysqli_fetch_assoc($res2)) $addresses[] = $r;
            mysqli_stmt_close($stmt_r);
            mysqli_close($c2);
        } catch (Exception $e) {}
    }
}

mysqli_close($conn);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi – Farm2Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/ProfileCustomer.css">
</head>
<body>

<?php include '../../../app/views/layouts/header.php'; ?>

<div class="container" style="padding-top: 10px;">

    <!-- Breadcrumb -->
    <nav class="profile-breadcrumb">
        <a href="../../../index.php">Trang chủ</a>
        <span class="sep">›</span>
        <span class="current">Tài khoản của tôi</span>
    </nav>

    <!-- Greeting -->
    <div class="profile-greeting">
        <img id="greetingAvatar"
             class="greeting-avatar"
             src="../../../Media/<?= e($customer['avatar']) ?>"
             alt="<?= e($customer['full_name']) ?>"
             onerror="this.src='../../../Media/user_1.jpg'">
        <div class="greeting-text">
            <h2>Xin chào, <?= e(explode(' ', $customer['full_name'])[count(explode(' ', $customer['full_name'])) - 1]) ?>!</h2>
            <p><i class="bi bi-envelope" style="margin-right:4px;"></i><?= e($customer['email']) ?></p>
        </div>
    </div>

    <!-- ════════════ MAIN CONTENT (no sidebar) ════════════ -->
    <div class="profile-main-solo">

        <!-- ── Section 1: Personal Info ──────────────────── -->
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

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Avatar + name row -->
                <div class="profile-top-row">
                    <div class="profile-avatar-wrap" id="avatarWrap" title="Nhấn để đổi ảnh">
                        <img id="avatarPreview"
                             src="../../../Media/<?= e($customer['avatar']) ?>"
                             alt="<?= e($customer['full_name']) ?>"
                             onerror="this.src='../../../Media/user_1.jpg'">
                        <div class="avatar-cam-overlay"><i class="bi bi-camera"></i></div>
                    </div>
                    <input type="file" id="avatarFileInput" name="avatar" accept="image/*">

                    <div class="profile-top-info">
                        <div class="pu-name"><?= e($customer['full_name']) ?></div>
                        <div class="pu-id">
                            <i class="bi bi-person-badge"></i>
                            ID: <?= e($customer['customer_id']) ?>
                        </div>
                        <div class="pu-date">
                            <i class="bi bi-bag-check"></i>
                            <?= (int)$customer['orders'] ?> đơn hàng
                        </div>
                    </div>
                </div>

                <!-- Form fields -->
                <div class="form-body-pad">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldName">Họ và tên</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="fieldName" name="full_name"
                                       value="<?= e($customer['full_name']) ?>" placeholder="Nhập họ và tên" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldPhone">Số điện thoại</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="fieldPhone" name="phone"
                                       value="<?= e($customer['phone']) ?>" placeholder="VD: 0901234567">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldEmail">Email</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="fieldEmail" name="email"
                                       value="<?= e($customer['email']) ?>" placeholder="email@example.com">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="fieldGender">Giới tính</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-gender-ambiguous"></i></span>
                                <select class="form-select" id="fieldGender" name="gender"
                                        style="border-left:none;border-radius:0 var(--radius-input) var(--radius-input) 0;">
                                    <option value="Nam"  <?= $customer['gender'] === 'Nam'  ? 'selected' : '' ?>>Nam</option>
                                    <option value="Nữ"   <?= $customer['gender'] === 'Nữ'   ? 'selected' : '' ?>>Nữ</option>
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

        <!-- ── Section 2: Addresses ───────────────────────── -->
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
                <div class="pc-alert pc-alert-danger mx-22 mb-3"><i class="bi bi-exclamation-circle-fill me-2"></i>Có lỗi xảy ra.</div>
            <?php endif; ?>

            <!-- Add address form (hidden by default) -->
            <div class="add-addr-form" id="addAddrForm" style="display:none;">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Người nhận</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" name="receiver_name"
                                       placeholder="Tên người nhận" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Số điện thoại</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" name="addr_phone"
                                       placeholder="Số điện thoại nhận hàng">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Địa chỉ chi tiết</label>
                            <div class="input-group pc-input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" name="addr_detail"
                                       placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" required>
                            </div>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                            <label class="pc-checkbox-label">
                                <input type="checkbox" name="addr_is_default" value="1">
                                <span>Đặt làm địa chỉ mặc định</span>
                            </label>
                            <div class="ms-auto d-flex gap-2">
                                <button type="button" class="btn-cancel-addr" id="btnCancelAddAddr">Hủy</button>
                                <button type="submit" name="add_address" class="btn-save-main">
                                    <i class="bi bi-plus-circle me-1"></i>Thêm địa chỉ
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Address list -->
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
                            <div class="addr-phone"><?= e($addr['phone']) ?></div>
                            <div class="addr-text"><?= e($addr['detail']) ?></div>
                        </div>
                        <div class="addr-actions">
                            <?php if ($addr['is_default']): ?>
                                <span class="default-badge"><i class="bi bi-patch-check-fill me-1"></i>Mặc định</span>
                            <?php else: ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="address_id" value="<?= e($addr['address_id']) ?>">
                                    <button type="submit" name="set_default_address" class="set-default-link">
                                        Đặt mặc định
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$addr['is_default']): ?>
                            <form method="POST" action="" style="display:inline;"
                                  onsubmit="return confirm('Bạn có chắc muốn xoá địa chỉ này?')">
                                <input type="hidden" name="address_id" value="<?= e($addr['address_id']) ?>">
                                <button type="submit" name="delete_address" class="btn-addr-del" title="Xoá">
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

        <!-- ── Section 3: Password ────────────────────────── -->
        <div class="section-card">
            <div class="section-card-header">
                <div class="sec-icon"><i class="bi bi-shield-lock-fill"></i></div>
                <h3>Bảo mật tài khoản</h3>
            </div>

            <?php if ($msg_password === 'success'): ?>
                <div class="pc-alert pc-alert-success mx-22 mb-3"><i class="bi bi-check-circle-fill me-2"></i>Đổi mật khẩu thành công!</div>
            <?php elseif ($msg_password === 'wrong_old'): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3"><i class="bi bi-exclamation-circle-fill me-2"></i>Mật khẩu hiện tại không đúng.</div>
            <?php elseif ($msg_password === 'error'): ?>
                <div class="pc-alert pc-alert-danger mx-22 mb-3"><i class="bi bi-exclamation-circle-fill me-2"></i>Mật khẩu không hợp lệ hoặc không khớp (tối thiểu 6 ký tự).</div>
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

    </div><!-- /profile-main-solo -->
</div><!-- /container -->

<?php include '../../../app/views/layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../../public/assets/js/ProfileCustomer.js"></script>
</body>
</html>