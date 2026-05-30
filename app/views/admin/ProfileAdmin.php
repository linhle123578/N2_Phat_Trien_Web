<?php
$BASE_URL = '/n2_phat_trien_web';

// ── Database connection ──────────────────────────────────────────────────────
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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_admin_id = $_SESSION['admin_id'] ?? 'ADM001';

// ── Fallback admin data ───────────────────────────────────────────────────────
$admin = [
    'admin_id'   => $current_admin_id,
    'full_name'  => 'Admin Farm2Home',
    'birthday'   => '',
    'phone'      => '0933 111 222',
    'gender'     => 'Nam',
    'address'    => 'Hà Nội, Việt Nam',
    'department' => 'Quản trị hệ thống',
    'account_id' => 'ACC001',
    // Các trường lấy từ bảng account (join)
    'email'      => 'admin@farm2home.vn',
    'avatar'     => 'user_1.jpg',
    'role'       => 'Quản trị viên',
];

// ── Query bảng admin JOIN account để lấy đủ thông tin ────────────────────────
// Cấu trúc thực tế: admin(admin_id, full_name, birthday, phone, gender, address, department, account_id)
try {
    // Thử join với bảng account để lấy email, avatar, role
    $sql = "
        SELECT
            a.admin_id,
            a.full_name,
            a.birthday,
            a.phone,
            a.gender,
            a.address,
            a.department,
            a.account_id,
            acc.email,
            acc.account_role as role
        FROM admin a
        LEFT JOIN account acc ON a.account_id = acc.account_id
        WHERE a.admin_id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $current_admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $admin['admin_id']   = $row['admin_id']   ?? $admin['admin_id'];
            $admin['full_name']  = $row['full_name']  ?? $admin['full_name'];
            $admin['birthday']   = $row['birthday']   ?? $admin['birthday'];
            $admin['phone']      = $row['phone']      ?? $admin['phone'];
            $admin['gender']     = $row['gender']     ?? $admin['gender'];
            $admin['address']    = $row['address']    ?? $admin['address'];
            $admin['department'] = $row['department'] ?? $admin['department'];
            $admin['account_id'] = $row['account_id'] ?? $admin['account_id'];
            $admin['email']      = $row['email']      ?? $admin['email'];
            $admin['avatar']     = $row['avatar']     ?? $admin['avatar'];
            $admin['role']       = $row['role']       ?? $admin['role'];
        }
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    // Giữ nguyên fallback nếu query lỗi
}

mysqli_close($conn);

// ── Handle form submit (POST) ─────────────────────────────────────────────────
$msg_profile  = '';
$msg_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function pa_db_connect() {
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

    if (isset($_POST['save_profile'])) {
        $fn   = trim($_POST['full_name']  ?? '');
        $ph   = trim($_POST['phone']      ?? '');
        $bday = trim($_POST['birthday']   ?? '');
        $gen  = trim($_POST['gender']     ?? '');
        $addr = trim($_POST['address']    ?? '');
        $dept = trim($_POST['department'] ?? '');
        $em   = trim($_POST['email']      ?? '');
        $aid  = $admin['admin_id'];
        $acct = $admin['account_id'] ?? null;

        try {
            $c = pa_db_connect();
            $stmt = mysqli_prepare($c,
                "UPDATE admin SET full_name=?, phone=?, birthday=?, gender=?, address=?, department=? WHERE admin_id=?");
            mysqli_stmt_bind_param($stmt, 'sssssss', $fn, $ph, $bday, $gen, $addr, $dept, $aid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($em && $acct) {
                $stmt2 = mysqli_prepare($c, "UPDATE account SET email=? WHERE account_id=?");
                mysqli_stmt_bind_param($stmt2, 'ss', $em, $acct);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }
            mysqli_close($c);

            $admin['full_name']  = htmlspecialchars($fn);
            $admin['phone']      = htmlspecialchars($ph);
            $admin['birthday']   = htmlspecialchars($bday);
            $admin['gender']     = htmlspecialchars($gen);
            $admin['address']    = htmlspecialchars($addr);
            $admin['department'] = htmlspecialchars($dept);
            $admin['email']      = htmlspecialchars($em);
            $msg_profile = 'success';
        } catch (Exception $e) {
            $msg_profile = 'error';
        }
    }

    if (isset($_POST['save_password'])) {
        $old_pw  = $_POST['old_password']     ?? '';
        $new_pw  = $_POST['new_password']     ?? '';
        $conf_pw = $_POST['confirm_password'] ?? '';
        $acct    = $admin['account_id'] ?? null;

        if ($new_pw !== $conf_pw || strlen($new_pw) < 6) {
            $msg_password = 'error';
        } elseif (!$acct) {
            $msg_password = 'error';
        } else {
            try {
                $c = pa_db_connect();
                $stmt_v = mysqli_prepare($c, "SELECT account_password FROM account WHERE account_id=? LIMIT 1");
                mysqli_stmt_bind_param($stmt_v, 's', $acct);
                mysqli_stmt_execute($stmt_v);
                mysqli_stmt_bind_result($stmt_v, $stored_pw);
                mysqli_stmt_fetch($stmt_v);
                mysqli_stmt_close($stmt_v);

                if (!$stored_pw || md5($old_pw) !== $stored_pw) {
                    $msg_password = 'wrong_old';
                } else {
                    $hashed = md5($new_pw);
                    $stmt_u = mysqli_prepare($c, "UPDATE account SET account_password=? WHERE account_id=?");
                    mysqli_stmt_bind_param($stmt_u, 'ss', $hashed, $acct);
                    mysqli_stmt_execute($stmt_u);
                    mysqli_stmt_close($stmt_u);
                    $msg_password = 'success';
                }
                mysqli_close($c);
            } catch (Exception $e) {
                $msg_password = 'error';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - Farm2Home Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="stylesheet" href="../../../public/assets/css/AdminSidebar.css">
    <link rel="stylesheet" href="../../../public/assets/css/ProfileAdmin.css">
</head>
<body>
<div class="pa-shell d-flex">
    <?php require_once __DIR__ . '/../layouts/adminsidebar.php'; ?>
    <div class="pa-main flex-grow-1 d-flex flex-column">

        <!-- HEADER -->
        <header class="admin-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="toggleSidebar" onclick="openSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold">Hồ sơ cá nhân</h5>
                </div>
            </div>
        </header>

        <section class="pa-content p-4 flex-grow-1">
            <div class="pa-card mb-4">
                <div class="pa-card-header">
                    <i class="bi bi-person-fill"></i>
                    <div>
                        <h5 class="pa-card-title">Hồ sơ cá nhân</h5>
                        <p class="pa-card-desc">Cập nhật thông tin tài khoản của bạn.</p>
                    </div>
                </div>
                
                <?php if ($msg_profile === 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show mx-0 mb-3 rounded-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>Cập nhật hồ sơ thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($msg_profile === 'error'): ?>
                    <div class="alert alert-danger alert-dismissible fade show mx-0 mb-3 rounded-3" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>Có lỗi xảy ra khi cập nhật.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row g-4">

                        <!-- Form fields column -->
                        <div class="col-lg-12">
                            <div class="mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1" style="font-weight: 700; color: #2c3e50;"><?= htmlspecialchars($admin['full_name']) ?></h4>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" style="font-weight: 500;"><i class="bi bi-shield-lock me-1"></i><?= htmlspecialchars($admin['role'] ?? 'Quản trị viên') ?></span>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="pa-label" for="full_name">Họ và tên</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="full_name" name="full_name"
                                               value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pa-label" for="email">Email</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pa-label" for="phone">Số điện thoại</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                               value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pa-label" for="birthday">Ngày sinh</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                        <input type="date" class="form-control" id="birthday" name="birthday"
                                               value="<?= htmlspecialchars($admin['birthday'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pa-label" for="gender">Giới tính</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-gender-ambiguous"></i></span>
                                        <select class="form-select" id="gender" name="gender"
                                                style="border-left:none; border-radius:0 var(--radius-input) var(--radius-input) 0;">
                                            <option value="Nam"  <?= ($admin['gender'] ?? '') === 'Nam'  ? 'selected' : '' ?>>Nam</option>
                                            <option value="Nữ"   <?= ($admin['gender'] ?? '') === 'Nữ'   ? 'selected' : '' ?>>Nữ</option>
                                            <option value="Khác" <?= ($admin['gender'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pa-label" for="department">Phòng ban</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                                        <input type="text" class="form-control" id="department" name="department"
                                               value="<?= htmlspecialchars($admin['department'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="pa-label" for="address">Địa chỉ</label>
                                    <div class="input-group pa-input-group">
                                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                        <input type="text" class="form-control" id="address" name="address"
                                               value="<?= htmlspecialchars($admin['address'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-12 mt-5 text-end">
                                    <button type="submit" name="save_profile" class="btn pa-btn-primary rounded-pill px-5 py-2 shadow-sm" style="font-weight: 600; font-size: 1.05rem;">
                                        <i class="bi bi-floppy me-2"></i>Lưu thay đổi
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div><!-- /.row -->
                </form>
            </div><!-- /.pa-card -->


            <!-- ── CARD 2: Change Password ───────────────── -->
            <div class="pa-card">
                <div class="pa-card-header">
                    <i class="bi bi-lock-fill"></i>
                    <div>
                        <h5 class="pa-card-title">Đổi mật khẩu</h5>
                        <p class="pa-card-desc">Mật khẩu nên có ít nhất 6 ký tự và khó đoán.</p>
                    </div>
                </div>

                <?php if ($msg_password === 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show mx-0 mb-3 rounded-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>Đổi mật khẩu thành công!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($msg_password === 'wrong_old'): ?>
                    <div class="alert alert-danger alert-dismissible fade show mx-0 mb-3 rounded-3" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>Mật khẩu hiện tại không đúng.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($msg_password === 'error'): ?>
                    <div class="alert alert-danger alert-dismissible fade show mx-0 mb-3 rounded-3" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>Mật khẩu mới không hợp lệ hoặc không khớp (tối thiểu 6 ký tự).
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="pa-label" for="old_password">Mật khẩu hiện tại</label>
                            <div class="input-group pa-input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="old_password"
                                       name="old_password" placeholder="••••••••">
                                <button type="button" class="btn pa-eye-btn" data-target="old_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="pa-label" for="new_password">Mật khẩu mới</label>
                            <div class="input-group pa-input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="new_password"
                                       name="new_password" placeholder="••••••••">
                                <button type="button" class="btn pa-eye-btn" data-target="new_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="pa-label" for="confirm_password">Xác nhận mật khẩu mới</label>
                            <div class="input-group pa-input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="confirm_password"
                                       name="confirm_password" placeholder="••••••••">
                                <button type="button" class="btn pa-eye-btn" data-target="confirm_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" name="save_password" class="btn pa-btn-primary rounded-pill px-5">
                                <i class="bi bi-shield-check me-2"></i>Đổi mật khẩu
                            </button>
                        </div>
                    </div>
                </form>
            </div><!-- /.pa-card -->

        </section><!-- /.pa-content -->
    </div><!-- /.pa-main -->
</div><!-- /.pa-shell -->

<!-- Overlay for mobile sidebar -->
<div class="pa-overlay" id="paOverlay"></div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script>
    const PA_BASE_URL = <?= json_encode($BASE_URL, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="../../../public/assets/js/ProfileAdmin.js"></script>
<script>
function openSidebar()  {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('paOverlay').classList.add('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('paOverlay').classList.remove('show');
}
document.getElementById('paOverlay').onclick = closeSidebar;
</script>
</body>
</html>
