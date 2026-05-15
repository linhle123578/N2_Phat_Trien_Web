<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$conn = mysqli_connect("localhost", "root", "", "ecommerce_db");
mysqli_set_charset($conn, "utf8");

$action = $_POST['action'] ?? '';

// XÓA 1 sản phẩm khỏi giỏ hàng
if ($action === 'xoa') {
    $cart_item_id = mysqli_real_escape_string($conn, $_POST['cart_item_id']);
    mysqli_query($conn, "DELETE FROM cartitem WHERE cart_item_id = '$cart_item_id'");
    // fetch() trong giohang.php sẽ reload trang sau khi nhận response
    exit();
}

// ĐẶT HÀNG: tạo order từ các item được chọn
if ($action === 'dat_hang') {
    $selected = $_POST['selected'] ?? [];   // mảng cart_item_id được chọn
    $qty_map  = $_POST['qty']      ?? [];   // qty[cart_item_id] => số lượng

    if (empty($selected)) {
        header("Location: giohang.php?msg=noselect");
        exit();
    }

    // Lấy thông tin các item được chọn từ DB
    $escaped = array_map(fn($id) => mysqli_real_escape_string($conn, $id), $selected);
    $in      = "'" . implode("','", $escaped) . "'";

    $sql_items = "SELECT ci.cart_item_id, ci.product_id, ci.unit_price, p.stock
                  FROM cartitem ci
                  JOIN product p ON ci.product_id = p.product_id
                  WHERE ci.cart_item_id IN ($in)";
    $res   = mysqli_query($conn, $sql_items);
    $items = [];
    while ($r = mysqli_fetch_assoc($res)) $items[] = $r;

    if (empty($items)) {
        header("Location: giohang.php?msg=error");
        exit();
    }

    // Tính tổng tiền (dùng qty từ POST để khớp với số lượng KH đã chỉnh)
    $total_amount = 0;
    foreach ($items as $item) {
        $qty           = max(1, (int)($qty_map[$item['cart_item_id']] ?? 1));
        $total_amount += $item['unit_price'] * $qty;
    }

    // Lấy địa chỉ mặc định của KH
    $r_addr     = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT address_id FROM address
         WHERE customer_id = '$customer_id' AND is_default = 1
         LIMIT 1"));
    $address_id = $r_addr ? "'" . $r_addr['address_id'] . "'" : "NULL";

    // Tạo order
    $order_id  = 'ORD-' . strtoupper(substr(uniqid(), -8));
    $ok = mysqli_query($conn,
        "INSERT INTO `order` (order_id, customer_id, address_id, order_status, total_amount, created_at)
         VALUES ('$order_id', '$customer_id', $address_id, 'pending', $total_amount, NOW())");

    if (!$ok) {
        header("Location: giohang.php?msg=error");
        exit();
    }

    // Insert orderitem + xóa khỏi cartitem
    foreach ($items as $item) {
        $qty           = max(1, (int)($qty_map[$item['cart_item_id']] ?? 1));
        $order_item_id = 'OI-' . strtoupper(substr(uniqid(), -8));
        $pid           = $item['product_id'];
        $price         = $item['unit_price'];

        mysqli_query($conn,
            "INSERT INTO orderitem (order_item_id, order_id, product_id, quantity, price)
             VALUES ('$order_item_id', '$order_id', '$pid', $qty, $price)");

        mysqli_query($conn,
            "DELETE FROM cartitem WHERE cart_item_id = '{$item['cart_item_id']}'");
    }

    mysqli_close($conn);
    header("Location: dathang_success.php?order_id=$order_id");
    exit();
}

mysqli_close($conn);
header("Location: giohang.php");
?>
