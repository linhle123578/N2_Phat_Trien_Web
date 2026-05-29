<?php
<<<<<<< HEAD
ob_start();
include_once __DIR__ . '/../layouts/header.php';
=======
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];

    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    mysqli_real_connect(
        $conn,
        "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
        "3YHrkxqAKWynehu.root",
        "BzDRrZAdAT2jLuyd",
        "db_web_farm2home",
        4000, NULL, MYSQLI_CLIENT_SSL
    );
    mysqli_set_charset($conn, "utf8mb4");

    $stmt = mysqli_prepare($conn, "UPDATE `order` SET order_status = 'cancelled' WHERE order_id = ? AND order_status = 'pending'");
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

// Quay lại trang lịch sử đơn hàng
<<<<<<< HEAD
$referer = $_SERVER['HTTP_REFERER'] ?? '../../../app/views/customer/OrderHistory.php';
header("Location: " . $referer);
exit;
?>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
=======
$referer = $_SERVER['HTTP_REFERER'] ?? '../../../public/index.php?page=orders';
header("Location: " . $referer);
exit;
?>
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f
