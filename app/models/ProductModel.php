<?php
class ProductModel {
    private $conn;

    public function __construct() {
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        $success = mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3YHrkxqAKWynehu.root",
            "BzDRrZAdAT2jLuyd",
            "db_web_farm2home",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        if (!$success) {
            die("Kết nối database thất bại: " . mysqli_connect_error());
        }
        mysqli_set_charset($this->conn, "utf8mb4");
    }

    // Lấy danh sách danh mục để làm bộ lọc sidebar
    public function getCategories() {
        $sql = "SELECT * FROM category ORDER BY name ASC";
        $result = mysqli_query($this->conn, $sql);
        $categories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        return $categories;
    }

    // Lấy danh sách sản phẩm theo bộ lọc tìm kiếm, danh mục và sắp xếp
    public function getProducts($category_filter, $search_filter, $sort_filter) {
        $category_filter = mysqli_real_escape_string($this->conn, $category_filter);
        $search_filter = mysqli_real_escape_string($this->conn, $search_filter);

        $sql = "SELECT p.*, c.name AS category_name 
                FROM product p 
                JOIN category c ON p.category_id = c.category_id 
                WHERE 1=1";

        if (!empty($category_filter)) {
            $sql .= " AND p.category_id = '$category_filter'";
        }

        if (!empty($search_filter)) {
            $sql .= " AND (p.product_name LIKE '%$search_filter%' OR p.description LIKE '%$search_filter%')";
        }

        // Logic sắp xếp
        if ($sort_filter === 'price_asc') {
            $sql .= " ORDER BY p.unit_price ASC";
        } elseif ($sort_filter === 'price_desc') {
            $sql .= " ORDER BY p.unit_price DESC";
        } else {
            $sql .= " ORDER BY p.product_id DESC"; // Mặc định mới nhất
        }

        $result = mysqli_query($this->conn, $sql);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        return $products;
    }

    // Thêm sản phẩm vào database giỏ hàng
    public function addToCart($customer_id, $product_id, $quantity = 1) {
        $customer_id = mysqli_real_escape_string($this->conn, $customer_id);
        $product_id = mysqli_real_escape_string($this->conn, $product_id);
        $quantity = (int)$quantity;

        // 1. Tìm hoặc tạo Cart cho Customer nếu chưa có
        $cart_sql = "SELECT cart_id FROM cart WHERE customer_id = '$customer_id' LIMIT 1";
        $cart_res = mysqli_query($this->conn, $cart_sql);
        
        if (mysqli_num_rows($cart_res) > 0) {
            $cart = mysqli_fetch_assoc($cart_res);
            $cart_id = $cart['cart_id'];
        } else {
            $cart_id = 'CRT-' . strtoupper(substr(uniqid(), -8));
            mysqli_query($this->conn, "INSERT INTO cart (cart_id, customer_id, created_at) VALUES ('$cart_id', '$customer_id', NOW())");
        }

        // 2. Lấy đơn giá sản phẩm hiện tại
        $prod_res = mysqli_query($this->conn, "SELECT unit_price FROM product WHERE product_id = '$product_id' LIMIT 1");
        $prod = mysqli_fetch_assoc($prod_res);
        if (!$prod) return false;
        $unit_price = $prod['unit_price'];

        // 3. Kiểm tra xem sản phẩm đã nằm trong giỏ hàng (cartitem) chưa
        $item_sql = "SELECT cart_item_id, quantity FROM cartitem WHERE cart_id = '$cart_id' AND product_id = '$product_id' LIMIT 1";
        $item_res = mysqli_query($this->conn, $item_sql);

        if (mysqli_num_rows($item_res) > 0) {
            // Đã có mặt -> Tiến hành cộng dồn số lượng
            $item = mysqli_fetch_assoc($item_res);
            $new_qty = $item['quantity'] + $quantity;
            $cart_item_id = $item['cart_item_id'];
            return mysqli_query($this->conn, "UPDATE cartitem SET quantity = $new_qty, updated_at = NOW() WHERE cart_item_id = '$cart_item_id'");
        } else {
            // Chưa có -> Tiến hành thêm mới dòng item
            $cart_item_id = 'CI-' . strtoupper(substr(uniqid(), -8));
            return mysqli_query($this->conn, "INSERT INTO cartitem (cart_item_id, cart_id, product_id, quantity, unit_price, created_at) 
                                              VALUES ('$cart_item_id', '$cart_id', '$product_id', $quantity, $unit_price, NOW())");
        }
    }

    // Đếm tổng số lượng item độc nhất trong giỏ hàng để cập nhật Badge
    public function getCartBadgeCount($customer_id) {
        $customer_id = mysqli_real_escape_string($this->conn, $customer_id);
        $sql = "SELECT COUNT(ci.cart_item_id) AS total FROM cartitem ci 
                JOIN cart c ON ci.cart_id = c.cart_id 
                WHERE c.customer_id = '$customer_id'";
        $res = mysqli_query($this->conn, $sql);
        $data = mysqli_fetch_assoc($res);
        return $data ? (int)$data['total'] : 0;
    }
} 
?>