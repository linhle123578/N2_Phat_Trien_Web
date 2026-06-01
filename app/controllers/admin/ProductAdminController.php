<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

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

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu!']);
    exit;
}

$action = $_POST['action'] ?? '';

// Xử lý upload ảnh
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExts)) {
        return ['error' => 'Chỉ chấp nhận file ảnh định dạng JPG, PNG, WEBP.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Kích thước file ảnh không được vượt quá 5MB.'];
    }

    $newFileName = uniqid('img_', true) . '.' . $extension;
    $uploadDir = __DIR__ . '/../../../Media/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploadPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['filename' => $newFileName];
    } else {
        return ['error' => 'Không thể lưu file ảnh lên server.'];
    }
}


if ($action === 'add') {
    $name = trim($_POST['product_name'] ?? '');
    $categoryId = trim($_POST['category_id'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'kg');
    $imageName = trim($_POST['product_image_name'] ?? '');

    if (empty($name) || empty($categoryId) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }

    // Tự động sinh Product ID
    $res = mysqli_query($conn, "SELECT product_id FROM product ORDER BY CAST(SUBSTRING(product_id, 3) AS UNSIGNED) DESC LIMIT 1");
    $newIdStr = 'SP001';
    if ($row = mysqli_fetch_assoc($res)) {
        $lastNum = (int)preg_replace('/\D/', '', $row['product_id']);
        $newIdStr = 'SP' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    }

    // Xử lý file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = handleImageUpload($_FILES['image_file']);
        if (isset($uploadRes['error'])) {
            echo json_encode(['success' => false, 'message' => $uploadRes['error']]);
            exit;
        }
        $imageName = $uploadRes['filename'];
    }

    // Insert
    $stmt = mysqli_prepare($conn, "INSERT INTO product (product_id, product_name, price, stock, unit, product_image, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssiisss", $newIdStr, $name, $price, $stock, $unit, $imageName, $categoryId);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi lưu DB: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);

} elseif ($action === 'edit') {
    $id = trim($_POST['product_id'] ?? '');
    $name = trim($_POST['product_name'] ?? '');
    $categoryId = trim($_POST['category_id'] ?? '');
    $price = intval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'kg');
    $imageName = trim($_POST['product_image_name'] ?? '');

    if (empty($id) || empty($name) || empty($categoryId) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }

    // Xử lý file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadRes = handleImageUpload($_FILES['image_file']);
        if (isset($uploadRes['error'])) {
            echo json_encode(['success' => false, 'message' => $uploadRes['error']]);
            exit;
        }
        $imageName = $uploadRes['filename'];
    }

    $stmt = mysqli_prepare($conn, "UPDATE product SET product_name=?, price=?, stock=?, unit=?, product_image=?, category_id=? WHERE product_id=?");
    mysqli_stmt_bind_param($stmt, "siissss", $name, $price, $stock, $unit, $imageName, $categoryId, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật DB: ' . mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);

} elseif ($action === 'delete') {
    $id = trim($_POST['product_id'] ?? '');
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy ID sản phẩm.']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM product WHERE product_id=?");
    mysqli_stmt_bind_param($stmt, "s", $id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
    } else {
        $err = mysqli_error($conn);
        if (strpos($err, 'foreign key constraint') !== false) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa vì sản phẩm này đã có trong đơn hàng.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa DB: ' . $err]);
        }
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

mysqli_close($conn);
?>
