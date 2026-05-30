<?php
ob_start();
include_once __DIR__ . '/../layouts/header.php';
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

    // 1. Fetch items to restore stock
    $stmtItems = mysqli_prepare($conn, "SELECT product_id, quantity FROM orderitem WHERE order_id = ?");
    mysqli_stmt_bind_param($stmtItems, 's', $order_id);
    mysqli_stmt_execute($stmtItems);
    $resultItems = mysqli_stmt_get_result($stmtItems);
    $items = [];
    while ($row = mysqli_fetch_assoc($resultItems)) {
        $items[] = $row;
    }
    mysqli_stmt_close($stmtItems);

    // 2. Update order status to 'cancelled'
    $stmt = mysqli_prepare($conn, "UPDATE `order` SET order_status = 'cancelled' WHERE order_id = ? AND (order_status = 'pending' OR order_status = 'Chờ xác nhận')");
    mysqli_stmt_bind_param($stmt, 's', $order_id);
    mysqli_stmt_execute($stmt);

    // 3. If successfully updated, restore stock
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $stmtUpdateStock = mysqli_prepare($conn, "UPDATE product SET stock = stock + ? WHERE product_id = ?");
        foreach ($items as $item) {
            mysqli_stmt_bind_param($stmtUpdateStock, 'is', $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($stmtUpdateStock);
        }
        mysqli_stmt_close($stmtUpdateStock);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

// Quay lại trang lịch sử đơn hàng
$referer = $_SERVER['HTTP_REFERER'] ?? '../../../app/views/customer/OrderHistory.php';
header("Location: " . $referer);
exit;
?>
