<?php

// ======================
// KẾT NỐI DATABASE TiDB
// ======================

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
if (!$conn) die("Kết nối thất bại: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8");

// ======================
// XỬ LÝ YÊU CẦU TRẢ HÀNG
// ======================

if (isset($_POST['handle_return'])) {
    $return_id  = mysqli_real_escape_string($conn, $_POST['return_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['return_status']);
    mysqli_query($conn, "UPDATE returnrequest SET return_status='$new_status' WHERE return_id='$return_id'");
    $qs = http_build_query([
        'search' => $_POST['search']     ?? '',
        'filter' => $_POST['filter_val'] ?? '',
        'page'   => $_POST['page_val']   ?? 1,
    ]);
    header("Location: " . $_SERVER['PHP_SELF'] . '?' . $qs);
    exit;
}

// ======================
// XOÁ ĐƠN HÀNG
// ======================

if (isset($_POST['delete_order'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    mysqli_query($conn, "DELETE FROM returnrequest WHERE order_id='$order_id'");
    mysqli_query($conn, "DELETE FROM shipment    WHERE order_id='$order_id'");
    mysqli_query($conn, "DELETE FROM orderitem   WHERE order_id='$order_id'");
    mysqli_query($conn, "DELETE FROM payment     WHERE order_id='$order_id'");
    mysqli_query($conn, "DELETE FROM `order`     WHERE order_id='$order_id'");
    $qs = http_build_query([
        'search' => $_POST['search']     ?? '',
        'filter' => $_POST['filter_val'] ?? '',
        'page'   => $_POST['page_val']   ?? 1,
    ]);
    header("Location: " . $_SERVER['PHP_SELF'] . '?' . $qs);
    exit;
}

// ======================
// UPDATE STATUS
// ======================

if (isset($_POST['update_status'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE `order` SET order_status='$status' WHERE order_id='$order_id'");
    $qs = http_build_query([
        'search' => $_POST['search']     ?? '',
        'filter' => $_POST['filter_val'] ?? '',
        'page'   => $_POST['page_val']   ?? 1,
    ]);
    header("Location: " . $_SERVER['PHP_SELF'] . '?' . $qs);
    exit;
}

// ======================
// ĐẾM YÊU CẦU TRẢ HÀNG ĐANG CHỜ
// ======================

$pendingReturns = 0;
$rr = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM returnrequest WHERE return_status='Đang xử lý'");
if ($rr) $pendingReturns = mysqli_fetch_assoc($rr)['cnt'];

// ======================
// PHÂN TRANG + LẤY ĐƠN HÀNG
// ======================

$searchRaw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search    = mysqli_real_escape_string($conn, $searchRaw);
$filter    = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : '';
$perPage   = 15;
$page      = max(1, intval($_GET['page'] ?? 1));

$where = "WHERE 1=1";
if ($search) {
    // Tìm kiếm không phân biệt dấu: dùng COLLATE hoặc so sánh trực tiếp
    // TiDB hỗ trợ utf8mb4_general_ci nên LIKE đã không phân biệt dấu thông thường,
    // nhưng để an toàn ta tìm thêm phiên bản không dấu
    $searchNoAccent = mysqli_real_escape_string($conn, removeAccents($searchRaw));
    $where .= " AND (
        c.full_name LIKE '%$search%'
        OR o.order_id LIKE '%$search%'
        OR c.full_name COLLATE utf8mb4_general_ci LIKE '%$searchNoAccent%'
        OR o.order_id COLLATE utf8mb4_general_ci LIKE '%$searchNoAccent%'
    )";
}
if ($filter === 'return') {
    $where .= " AND EXISTS (SELECT 1 FROM returnrequest rr WHERE rr.order_id = o.order_id)";
} elseif ($filter) {
    $where .= " AND o.order_status = '$filter'";
}

$count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM `order` o LEFT JOIN customer c ON o.customer_id=c.customer_id $where");
$totalOrders  = mysqli_fetch_assoc($count_result)['total'];
$totalPages   = max(1, ceil($totalOrders / $perPage));
$page         = min($page, $totalPages);
$offset       = ($page - 1) * $perPage;

$sql = "
SELECT o.order_id, c.full_name, o.order_status, o.created_at, p.total_amount
FROM `order` o
LEFT JOIN customer c ON o.customer_id = c.customer_id
LEFT JOIN payment  p ON o.order_id    = p.order_id
$where
ORDER BY o.created_at DESC
LIMIT $perPage OFFSET $offset
";

$result = mysqli_query($conn, $sql);
$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Sản phẩm
    $dq = mysqli_query($conn,
        "SELECT pr.product_name, pr.product_image, oi.quantity, oi.price
         FROM orderitem oi
         LEFT JOIN product pr ON oi.product_id = pr.product_id
         WHERE oi.order_id = '" . $row['order_id'] . "'"
    );
    $items = [];
    while ($d = mysqli_fetch_assoc($dq)) $items[] = $d;

    $amount = $row['total_amount'] ?: array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

    // Yêu cầu trả hàng
    $rq = mysqli_query($conn,
        "SELECT return_id, reason, return_status, request_date
         FROM returnrequest WHERE order_id = '" . $row['order_id'] . "'
         ORDER BY request_date DESC LIMIT 1"
    );
    $returnInfo = $rq ? mysqli_fetch_assoc($rq) : null;

    $orders[] = [
        "id"         => $row['order_id'],
        "name"       => $row['full_name'],
        "status"     => $row['order_status'],
        "time"       => date('H:i:s d-m-Y', strtotime($row['created_at'])),
        "amount"     => $amount,
        "items"      => $items,
        "returnInfo" => $returnInfo,
    ];
}

// Map chuẩn hoá từ tiếng Anh → tiếng Việt để dùng trong select
$statusNormalize = [
    'pending'   => 'Chờ xác nhận',
    'Pending'   => 'Chờ xác nhận',
    'shipping'  => 'Đang giao',
    'Shipping'  => 'Đang giao',
    'completed' => 'Hoàn thành',
    'Completed' => 'Hoàn thành',
    'delivered' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
    'Cancelled' => 'Đã hủy',
    'canceled'  => 'Đã hủy',
];

// Các trạng thái chuẩn tiếng Việt dùng trong select
$statusMapVi = [
    'Chờ xác nhận' => ['label' => 'Chờ xác nhận', 'class' => 'badge-pending'],
    'Đang giao'    => ['label' => 'Đang giao',     'class' => 'badge-shipping'],
    'Hoàn thành'   => ['label' => 'Hoàn thành',    'class' => 'badge-completed'],
    'Đã hủy'       => ['label' => 'Đã hủy',        'class' => 'badge-cancel'],
];
$statusMap = [
    // Tiếng Việt (chuẩn)
    'Chờ xác nhận' => ['label' => 'Chờ xác nhận', 'class' => 'badge-pending'],
    'Đang giao'    => ['label' => 'Đang giao',     'class' => 'badge-shipping'],
    'Hoàn thành'   => ['label' => 'Hoàn thành',    'class' => 'badge-completed'],
    'Đã hủy'       => ['label' => 'Đã hủy',        'class' => 'badge-cancel'],
    // Tiếng Anh (dữ liệu cũ nạp sẵn trong DB)
    'pending'      => ['label' => 'Chờ xác nhận', 'class' => 'badge-pending'],
    'Pending'      => ['label' => 'Chờ xác nhận', 'class' => 'badge-pending'],
    'shipping'     => ['label' => 'Đang giao',     'class' => 'badge-shipping'],
    'Shipping'     => ['label' => 'Đang giao',     'class' => 'badge-shipping'],
    'completed'    => ['label' => 'Hoàn thành',    'class' => 'badge-completed'],
    'Completed'    => ['label' => 'Hoàn thành',    'class' => 'badge-completed'],
    'cancelled'    => ['label' => 'Đã hủy',        'class' => 'badge-cancel'],
    'Cancelled'    => ['label' => 'Đã hủy',        'class' => 'badge-cancel'],
    'canceled'     => ['label' => 'Đã hủy',        'class' => 'badge-cancel'],
    'delivered'    => ['label' => 'Hoàn thành',    'class' => 'badge-completed'],
];

// Map trạng thái yêu cầu trả hàng
$returnStatusMap = [
    'Đang xử lý'  => ['label' => 'Đang xử lý',  'class' => 'rs-pending'],
    'Đã hoàn tiền'=> ['label' => 'Đã hoàn tiền', 'class' => 'rs-refunded'],
    'Đã đổi hàng' => ['label' => 'Đã đổi hàng',  'class' => 'rs-exchanged'],
    'Đã hủy đơn'  => ['label' => 'Đã hủy đơn',   'class' => 'rs-cancelled'],
    'Từ chối'     => ['label' => 'Từ chối',       'class' => 'rs-rejected'],
];

function pageUrl($p, $search, $filter) {
    return '?' . http_build_query(['search'=>$search,'filter'=>$filter,'page'=>$p]);
}

// Xoá dấu tiếng Việt để tìm kiếm không phân biệt dấu/hoa thường
function removeAccents($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $from = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
             'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
             'ì','í','ị','ỉ','ĩ',
             'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
             'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
             'ỳ','ý','ỵ','ỷ','ỹ','đ'];
    $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
             'e','e','e','e','e','e','e','e','e','e','e',
             'i','i','i','i','i',
             'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
             'u','u','u','u','u','u','u','u','u','u','u',
             'y','y','y','y','y','d'];
    return str_replace($from, $to, $str);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farm2Home – Quản lý đơn hàng</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="../../../public/assets/css/AdminSidebar.css">
<link rel="stylesheet" href="../../../public/assets/css/ControlOrder.css">
<style>
    :root { --cream: #fefbe9; --cream-2: #f0ead0; }
    body  { background: #fefbe9 !important; }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<?php require_once __DIR__ . '/../layouts/adminsidebar.php'; ?>

<!-- MAIN -->
<div class="main-wrap">
    <div class="topbar" style="background:#fefbe9;border-bottom:1px solid #e8e0c8;padding:16px 28px">
        <div class="d-flex align-items-center" style="gap:10px">
            <button class="hamburger" onclick="openSidebar()"><span class="material-symbols-outlined">menu</span></button>
            <div>
                <div class="topbar-title">Quản Lý Đơn Hàng</div>
                <div class="topbar-sub">
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">

        <!-- BANNER CẢNH BÁO TRẢ HÀNG -->
        <?php if($pendingReturns > 0): ?>
        <div class="return-alert" onclick="applyFilterDirect('return')">
            <div class="return-alert-icon">
                <span class="material-symbols-outlined">assignment_return</span>
            </div>
            <div>
                <div class="return-alert-title">Có <?= $pendingReturns ?> yêu cầu trả hàng đang chờ xử lý</div>
                <div class="return-alert-sub">Nhấn để xem và xử lý các yêu cầu từ khách hàng</div>
            </div>
            <div class="return-alert-arrow"><span class="material-symbols-outlined">arrow_forward_ios</span></div>
        </div>
        <?php endif; ?>

        <div class="card-box">

            <!-- HEADER -->
            <div class="card-header-row">
                <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:12px">
                    <div>
                        <div class="card-title">Danh sách đơn hàng</div>
                        <div class="card-sub">Cập nhật lúc <span id="live-clock"></span></div>
                    </div>
                    <form method="GET" class="filter-bar" id="filterForm" style="flex:1;min-width:240px;max-width:480px">
                        <div class="search-wrap">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" name="search" id="searchInput" class="search-input"
                                placeholder="Tìm kiếm đơn hàng..."
                                value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                        </div>
                        <div class="position-relative">
                            <button type="button" class="btn-filter" id="filterToggle">
                                <span class="material-symbols-outlined">filter_list</span>
                                Lọc<?php if($filter): ?>&nbsp;<span style="color:var(--green)">●</span><?php endif; ?>
                            </button>
                            <div class="filter-dropdown" id="filterDropdown" style="position:fixed;z-index:9999;display:none">
                                <?php
                                $opts = [
                                    ''             => ['Tất cả',              '#aaa'],
                                    'Chờ xác nhận' => ['Chờ xác nhận',        '#c8a000'],
                                    'Đang giao'    => ['Đang giao',            '#1c5c2c'],
                                    'Hoàn thành'   => ['Hoàn thành',           '#1a5c30'],
                                    'Đã hủy'       => ['Đã hủy',              '#9a3800'],
                                    'return'       => ['Yêu cầu trả hàng',    '#d4600a'],
                                ];
                                foreach($opts as $val => [$label, $color]): ?>
                                <div class="filter-opt <?= $filter===$val?'active':'' ?>" onclick="applyFilter('<?= $val ?>')">
                                    <span class="filter-dot" style="background:<?= $color ?>"></span>
                                    <?= $label ?>
                                    <?php if($val==='return' && $pendingReturns>0): ?>
                                    <span class="notif-dot ml-auto" style="margin-left:auto"><?= $pendingReturns ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" name="filter" id="filterInput" value="<?= htmlspecialchars($filter) ?>">
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
            </div>

            <!-- TABLE desktop -->
            <div class="table-responsive">
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Mã đơn hàng</th>
                            <th>Người đặt</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="5"><div class="empty-state">
                            <span class="material-symbols-outlined">inbox</span>
                            <p>Không tìm thấy đơn hàng nào.</p>
                        </div></td></tr>
                    <?php else: foreach($orders as $o):
                        $st  = $statusMap[$o['status']] ?? ['label'=>$o['status'],'class'=>'badge-pending'];
                        $hasReturn = !empty($o['returnInfo']);
                        $ri  = $o['returnInfo'];
                        // Chuẩn hoá trạng thái tiếng Anh → tiếng Việt để select đúng option
                        $statusNormalized = $statusNormalize[$o['status']] ?? $o['status'];
                    ?>
                        <tr class="<?= $hasReturn?'has-return':'' ?>">
                            <td>
                                <span class="order-id"><?= htmlspecialchars($o['id']) ?></span>
                                <?php if($hasReturn): ?>
                                <span class="return-tag">
                                    <span class="material-symbols-outlined">assignment_return</span>
                                    Yêu cầu trả hàng
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600"><?= htmlspecialchars($o['name']) ?></td>
                            <td style="color:var(--text-soft);font-size:13px"
                                data-time="<?= htmlspecialchars($o['createdAt'] ?? $o['time']) ?>">
                                <?= $o['time'] ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                                <?php if($hasReturn):
                                    $rs = $returnStatusMap[$ri['return_status']] ?? ['label'=>$ri['return_status'],'class'=>'rs-pending'];
                                ?>
                                <br><span class="rs-badge <?= $rs['class'] ?>" style="margin-top:4px;display:inline-block"><?= $rs['label'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="d-flex align-items-center justify-content-end" style="gap:6px">
                                    <button class="btn-view" onclick='openModal(<?= json_encode($o, JSON_UNESCAPED_UNICODE) ?>)'>
                                        <span class="material-symbols-outlined">visibility</span>Xem
                                    </button>
                                    <form method="POST" style="margin:0">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($searchRaw) ?>">
                                        <input type="hidden" name="filter_val" value="<?= htmlspecialchars($filter) ?>">
                                        <input type="hidden" name="page_val" value="<?= $page ?>">
                                        <select name="status" class="status-select" onchange="this.form.submit()">
                                            <?php foreach($statusMapVi as $val=>$s): ?>
                                            <option value="<?= $val ?>" <?= $statusNormalized===$val?'selected':'' ?>><?= $s['label'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <button class="btn-del" onclick='confirmDelete(<?= json_encode(['id'=>$o['id'],'name'=>$o['name']], JSON_UNESCAPED_UNICODE) ?>)'>
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- MOBILE CARDS -->
            <div class="mobile-cards p-3">
            <?php if(empty($orders)): ?>
                <div class="empty-state">
                    <span class="material-symbols-outlined">inbox</span>
                    <p>Không tìm thấy đơn hàng nào.</p>
                </div>
            <?php else: foreach($orders as $o):
                $st = $statusMap[$o['status']] ?? ['label'=>$o['status'],'class'=>'badge-pending'];
                $hasReturn = !empty($o['returnInfo']);
                $statusNormalized = $statusNormalize[$o['status']] ?? $o['status'];
            ?>
                <div class="order-card-mobile <?= $hasReturn?'has-return':'' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($o['name']) ?></div>
                            <div style="font-family:monospace;font-size:11px;color:var(--text-soft)"><?= substr($o['id'],0,22) ?>…</div>
                            <?php if($hasReturn): ?>
                            <span class="return-tag mt-1"><span class="material-symbols-outlined">assignment_return</span>Yêu cầu trả hàng</span>
                            <?php endif; ?>
                        </div>
                        <span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                    </div>
                    <div style="font-size:12px;color:var(--text-soft);margin-bottom:10px"
                         data-time="<?= htmlspecialchars($o['createdAt'] ?? $o['time']) ?>">
                        <?= $o['time'] ?>
                    </div>
                    <div class="d-flex align-items-center" style="gap:6px;flex-wrap:wrap">
                        <button class="btn-view" onclick='openModal(<?= json_encode($o, JSON_UNESCAPED_UNICODE) ?>)'>
                            <span class="material-symbols-outlined">visibility</span>Xem
                        </button>
                        <form method="POST" style="margin:0">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchRaw) ?>">
                            <input type="hidden" name="filter_val" value="<?= htmlspecialchars($filter) ?>">
                            <input type="hidden" name="page_val" value="<?= $page ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <?php foreach($statusMapVi as $val=>$s): ?>
                                <option value="<?= $val ?>" <?= $statusNormalized===$val?'selected':'' ?>><?= $s['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <button class="btn-del" onclick='confirmDelete(<?= json_encode(['id'=>$o['id'],'name'=>$o['name']], JSON_UNESCAPED_UNICODE) ?>)'>
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            </div>

            <!-- PAGINATION -->
            <div class="pager-wrap">
                <div>Hiển thị <?= ($offset+1) ?>–<?= min($offset+$perPage,$totalOrders) ?> trong tổng số <strong><?= $totalOrders ?></strong> đơn hàng</div>
                <?php if($totalPages > 1): ?>
                <div class="page-btns">
                    <a href="<?= pageUrl($page-1,$search,$filter) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                    <?php
                    $start=max(1,$page-2); $end=min($totalPages,$page+2);
                    if($start>1): ?>
                        <a href="<?= pageUrl(1,$search,$filter) ?>" class="page-btn">1</a>
                        <?php if($start>2): ?><span class="page-btn" style="border:none;background:none;pointer-events:none">…</span><?php endif; ?>
                    <?php endif;
                    for($i=$start;$i<=$end;$i++): ?>
                        <a href="<?= pageUrl($i,$search,$filter) ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor;
                    if($end<$totalPages): ?>
                        <?php if($end<$totalPages-1): ?><span class="page-btn" style="border:none;background:none;pointer-events:none">…</span><?php endif; ?>
                        <a href="<?= pageUrl($totalPages,$search,$filter) ?>" class="page-btn"><?= $totalPages ?></a>
                    <?php endif; ?>
                    <a href="<?= pageUrl($page+1,$search,$filter) ?>" class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- ══ MODAL CHI TIẾT ══ -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <h4>Chi tiết đơn hàng</h4>
                <p id="m-id"></p>
            </div>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-7">
                    <div class="info-label">Khách hàng</div>
                    <div class="info-val" id="m-name"></div>
                </div>
                <div class="col-5">
                    <div class="info-label">Trạng thái</div>
                    <div class="info-val" id="m-status"></div>
                </div>
                <div class="col-12 mt-2">
                    <div class="info-label">Thời gian đặt</div>
                    <div class="info-val" id="m-time"></div>
                </div>
            </div>

            <div class="section-divider">Sản phẩm đã đặt</div>
            <div id="m-products"></div>

            <div class="modal-total mt-2">
                <span>Tổng thanh toán</span>
                <strong id="m-amount"></strong>
            </div>

            <!-- KHU VỰC TRẢ HÀNG (hiện khi có returnInfo) -->
            <div id="m-return-block" style="display:none"></div>
        </div>
    </div>
</div>

<!-- ══ MODAL XÁC NHẬN XOÁ ══ -->
<div class="modal-overlay" id="deleteOverlay">
    <div class="confirm-box">
        <div class="confirm-head">
            <div class="confirm-icon"><span class="material-symbols-outlined">delete_forever</span></div>
            <div class="confirm-title">Xác nhận xoá đơn hàng</div>
            <div class="confirm-desc" id="del-desc"></div>
        </div>
        <div class="confirm-footer">
            <button class="btn-cancel-modal" onclick="closeDelete()">Huỷ bỏ</button>
            <form method="POST" style="flex:1;margin:0">
                <input type="hidden" name="delete_order" value="1">
                <input type="hidden" name="order_id" id="del-order-id">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="filter_val" value="<?= htmlspecialchars($filter) ?>">
                <input type="hidden" name="page_val" value="<?= $page ?>">
                <button type="submit" class="btn-confirm-del w-100">Xoá đơn hàng</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const statusLabels = {
    'Chờ xác nhận':'Chờ xác nhận',
    'Đang giao':'Đang giao',
    'Hoàn thành':'Hoàn thành',
    'Đã hủy':'Đã hủy',
    // Tiếng Anh (dữ liệu cũ)
    'pending':'Chờ xác nhận','Pending':'Chờ xác nhận',
    'shipping':'Đang giao','Shipping':'Đang giao',
    'completed':'Hoàn thành','Completed':'Hoàn thành',
    'delivered':'Hoàn thành',
    'cancelled':'Đã hủy','Cancelled':'Đã hủy','canceled':'Đã hủy'
};
const statusClass = {
    'Chờ xác nhận':'badge-pending',
    'Đang giao':'badge-shipping',
    'Hoàn thành':'badge-completed',
    'Đã hủy':'badge-cancel',
    // Tiếng Anh (dữ liệu cũ)
    'pending':'badge-pending','Pending':'badge-pending',
    'shipping':'badge-shipping','Shipping':'badge-shipping',
    'completed':'badge-completed','Completed':'badge-completed',
    'delivered':'badge-completed',
    'cancelled':'badge-cancel','Cancelled':'badge-cancel','canceled':'badge-cancel'
};
const rsLabel = {
    'Đang xử lý':'Đang xử lý','Đã hoàn tiền':'Đã hoàn tiền',
    'Đã đổi hàng':'Đã đổi hàng','Đã hủy đơn':'Đã hủy đơn','Từ chối':'Từ chối'
};
const rsClass = {
    'Đang xử lý':'rs-pending','Đã hoàn tiền':'rs-refunded',
    'Đã đổi hàng':'rs-exchanged','Đã hủy đơn':'rs-cancelled','Từ chối':'rs-rejected'
};

// Sidebar
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('show'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('show'); }

// Filter — dropdown dùng position:fixed để thoát khỏi overflow:hidden của card
document.getElementById('filterToggle').addEventListener('click', function(e) {
    e.stopPropagation();
    const dd = document.getElementById('filterDropdown');
    const rect = this.getBoundingClientRect();
    // Hiện tạm để đo chiều rộng
    dd.style.display = 'block';
    const ddW = dd.offsetWidth;
    // Căn phải theo nút, không tràn màn hình
    let left = rect.right - ddW;
    if (left < 8) left = 8;
    dd.style.top  = (rect.bottom + 6) + 'px';
    dd.style.left = left + 'px';
    dd.classList.toggle('show');
    if (!dd.classList.contains('show')) dd.style.display = 'none';
});
document.addEventListener('click', () => {
    const dd = document.getElementById('filterDropdown');
    dd.classList.remove('show');
    dd.style.display = 'none';
});
function applyFilter(val) {
    document.getElementById('filterInput').value = val;
    document.getElementById('filterForm').submit();
}
function applyFilterDirect(val) {
    document.getElementById('filterInput').value = val;
    document.getElementById('filterForm').submit();
}

// Search
let st;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(st);
    st = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});

function fmt(n) { return Number(n).toLocaleString('vi-VN') + 'đ'; }

// ── MODAL CHI TIẾT ──
function openModal(o) {
    document.getElementById('m-id').textContent    = o.id;
    document.getElementById('m-name').textContent  = o.name;
    document.getElementById('m-time').textContent  = o.time;
    document.getElementById('m-amount').textContent = fmt(o.amount);

    const sc = statusClass[o.status] || 'badge-pending';
    document.getElementById('m-status').innerHTML =
        `<span class="status-badge ${sc}">${statusLabels[o.status]||o.status}</span>`;

    // Sản phẩm
    let html = '';
    (o.items||[]).forEach(item => {
        html += `<div class="product-card">
            <div class="product-img-placeholder"><span class="material-symbols-outlined">eco</span></div>
            <div style="flex:1;min-width:0">
                <div class="product-name">${item.product_name}</div>
                <div class="product-qty">SL: ${item.quantity} &nbsp;·&nbsp; Đơn giá: ${fmt(item.price)}</div>
            </div>
            <div class="product-price">${fmt(item.price * item.quantity)}</div>
        </div>`;
    });
    if (!html) html = '<p style="color:var(--text-soft);font-size:13px">Không có sản phẩm.</p>';
    document.getElementById('m-products').innerHTML = html;

    // Yêu cầu trả hàng
    const rb = document.getElementById('m-return-block');
    if (o.returnInfo) {
        const ri = o.returnInfo;
        const rsc = rsClass[ri.return_status] || 'rs-pending';
        const rsl = rsLabel[ri.return_status] || ri.return_status;
        const isPending = ri.return_status === 'Đang xử lý';

        rb.style.display = 'block';
        rb.innerHTML = `
        <div class="section-divider">Yêu cầu trả hàng</div>
        <div class="return-block">
            <div class="return-block-head">
                <span class="material-symbols-outlined">assignment_return</span>
                Khách hàng yêu cầu trả hàng
            </div>
            <div class="return-block-body">
                <div class="return-info-row">
                    <div class="return-info-item">
                        <div class="info-label">Mã yêu cầu</div>
                        <div class="info-val" style="font-size:13px;font-family:monospace">${ri.return_id}</div>
                    </div>
                    <div class="return-info-item">
                        <div class="info-label">Ngày yêu cầu</div>
                        <div class="info-val" style="font-size:13px">${ri.request_date}</div>
                    </div>
                    <div class="return-info-item">
                        <div class="info-label">Trạng thái</div>
                        <div class="info-val"><span class="rs-badge ${rsc}">${rsl}</span></div>
                    </div>
                </div>
                <div class="info-label mb-1">Lý do từ khách hàng</div>
                <div class="return-reason">${ri.reason || '(Không có lý do)'}</div>
                ${isPending ? `
                <div class="info-label mb-2">Xử lý yêu cầu</div>
                <div class="return-actions">
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="handle_return" value="1">
                        <input type="hidden" name="return_id" value="${ri.return_id}">
                        <input type="hidden" name="return_status" value="Đã hoàn tiền">
                        <input type="hidden" name="search" value="">
                        <input type="hidden" name="filter_val" value="">
                        <input type="hidden" name="page_val" value="1">
                        <button type="submit" class="btn-rs btn-rs-refund">✓ Đồng ý hoàn tiền</button>
                    </form>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="handle_return" value="1">
                        <input type="hidden" name="return_id" value="${ri.return_id}">
                        <input type="hidden" name="return_status" value="Đã đổi hàng">
                        <input type="hidden" name="search" value="">
                        <input type="hidden" name="filter_val" value="">
                        <input type="hidden" name="page_val" value="1">
                        <button type="submit" class="btn-rs btn-rs-exchange">⇄ Đổi hàng</button>
                    </form>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="handle_return" value="1">
                        <input type="hidden" name="return_id" value="${ri.return_id}">
                        <input type="hidden" name="return_status" value="Từ chối">
                        <input type="hidden" name="search" value="">
                        <input type="hidden" name="filter_val" value="">
                        <input type="hidden" name="page_val" value="1">
                        <button type="submit" class="btn-rs btn-rs-reject">✕ Từ chối</button>
                    </form>
                </div>` : ''}
            </div>
        </div>`;
    } else {
        rb.style.display = 'none';
        rb.innerHTML = '';
    }

    document.getElementById('modalOverlay').classList.add('show');
}
function closeModal() { document.getElementById('modalOverlay').classList.remove('show'); }
document.getElementById('modalOverlay').addEventListener('click', function(e) { if(e.target===this) closeModal(); });

// ── ĐỒNG HỒ REALTIME (GMT+7) ──
function updateClock() {
    const now = new Date();
    // Chuyển về GMT+7
    const vn = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Ho_Chi_Minh' }));
    const hh   = String(vn.getHours()).padStart(2, '0');
    const mm   = String(vn.getMinutes()).padStart(2, '0');
    const ss   = String(vn.getSeconds()).padStart(2, '0');
    const ampm = vn.getHours() >= 12 ? 'PM' : 'AM';
    const dd   = String(vn.getDate()).padStart(2, '0');
    const mo   = vn.getMonth() + 1;
    const yy   = vn.getFullYear();
    document.getElementById('live-clock').textContent =
        `${hh}:${mm}:${ss} ${ampm}, ${dd} tháng ${mo}, ${yy}`;
}
updateClock();
setInterval(updateClock, 1000);

// ── THỜI GIAN TƯƠNG ĐỐI REALTIME ──
function timeAgo(dateStr) {
    if (!dateStr) return '';

    let date;

    // Format ISO từ DB: "2025-05-25 14:30:00" (UTC)
    if (/^\d{4}-\d{2}-\d{2}/.test(dateStr)) {
        // Thêm 'Z' để JS hiểu là UTC, tránh bị parse thành local time
        date = new Date(dateStr.replace(' ', 'T') + 'Z');

    // Format fallback từ PHP: "14:30:00 25-05-2025"
    } else if (/^\d{2}:\d{2}:\d{2} \d{2}-\d{2}-\d{4}/.test(dateStr)) {
        const [time, dmy] = dateStr.split(' ');
        const [dd, mo, yy] = dmy.split('-');
        date = new Date(`${yy}-${mo}-${dd}T${time}Z`); // cũng coi là UTC

    } else {
        return dateStr; // Không nhận ra format thì hiện nguyên
    }

    if (isNaN(date)) return dateStr;

    const now  = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60)    return 'Vừa xong';
    if (diff < 3600)  return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';

    // Qua 24h → hiện ngày giờ theo GMT+7
    const vn = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Ho_Chi_Minh' }));
    const hh = String(vn.getHours()).padStart(2,'0');
    const mm = String(vn.getMinutes()).padStart(2,'0');
    const dd = String(vn.getDate()).padStart(2,'0');
    const mo = String(vn.getMonth()+1).padStart(2,'0');
    const yy = vn.getFullYear();
    return `${dd}/${mo}/${yy}`;
}

function updateAllTimes() {
    document.querySelectorAll('[data-time]').forEach(el => {
        el.textContent = timeAgo(el.dataset.time);
    });
}
updateAllTimes();
setInterval(updateAllTimes, 30000); // cập nhật mỗi 30 giây

function confirmDelete(o) {
    document.getElementById('del-desc').textContent =
        `Bạn có chắc muốn xoá đơn hàng của "${o.name}"? Hành động này không thể hoàn tác.`;
    document.getElementById('deleteOverlay').classList.add('show');
}
function closeDelete() { document.getElementById('deleteOverlay').classList.remove('show'); }
document.getElementById('deleteOverlay').addEventListener('click', function(e) { if(e.target===this) closeDelete(); });
</script>
</body>
</html>