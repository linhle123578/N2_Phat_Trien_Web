<?php
class ProductModel {
    private $conn;

    public function __construct() {
        // Kết nối an toàn bảo mật lên hệ thống Cloud TiDB bằng cờ SSL bắt buộc
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com",
            "3YHrkxqAKWynehu.root",
            "BzDRrZAdAT2jLuyd",
            "db_web_farm2home",
            4000,
            NULL,
            MYSQLI_CLIENT_SSL
        );
        mysqli_set_charset($this->conn, "utf8mb4");

        if (mysqli_connect_errno()) {
            die("Kết nối database đám mây thất bại: " . mysqli_connect_error());
        }
    }

    public function getCategories() {
        $query = "SELECT * FROM category ORDER BY name ASC";
        return mysqli_query($this->conn, $query);
    }

    public function getProducts($category = '', $search = '', $sort = 'latest', $limit = 9, $offset = 0) {
        $conn = $this->conn;
        $sql = "SELECT p.* FROM product p WHERE 1=1";
        
        // Sửa logic tìm kiếm tại đây
        if (!empty($search)) {
            $search_esc = mysqli_real_escape_string($conn, $search);
            // Thêm COLLATE utf8mb4_unicode_ci để bỏ qua dấu và hoa/thường
            $sql .= " AND p.product_name LIKE '%$search_esc%' COLLATE utf8mb4_unicode_ci";
        }
        
        if (!empty($category)) {
            $cat_esc = mysqli_real_escape_string($conn, $category);
            $sql .= " AND p.category_id = '$cat_esc'";
        }

        $order_by = ($sort === 'price_asc') ? "p.price ASC" : (($sort === 'price_desc') ? "p.price DESC" : "p.product_id DESC");
        $sql .= " ORDER BY $order_by LIMIT $limit OFFSET $offset";
        
        return mysqli_query($conn, $sql);
    }

    public function getTotalProductsCount($category = '', $search = '') {
        $sql = "SELECT COUNT(*) as total FROM product WHERE 1=1";
        
        if (!empty($search)) {
            $search_esc = mysqli_real_escape_string($this->conn, $search);
            // Tương tự cho hàm đếm
            $sql .= " AND product_name LIKE '%$search_esc%' COLLATE utf8mb4_unicode_ci";
        }
        
        if (!empty($category)) {
            $cat_esc = mysqli_real_escape_string($this->conn, $category);
            $sql .= " AND category_id = '$cat_esc'";
        }
        
        $res = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($res);
        return $row['total'];
    }

    public function getCartCount($customer_id) {
        $cus_escaped = mysqli_real_escape_string($this->conn, $customer_id);
        $query = "SELECT COUNT(ci.product_id) AS total_qty 
                  FROM cart c 
                  JOIN cartitem ci ON c.cart_id = ci.cart_id 
                  WHERE c.customer_id = '$cus_escaped'";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        return $row['total_qty'] ? (int)$row['total_qty'] : 0;
    }

    public function addToCart($customer_id, $product_id, $quantity = 1) {
        $cus_escaped = mysqli_real_escape_string($this->conn, $customer_id);
        $prod_escaped = mysqli_real_escape_string($this->conn, $product_id);

        // 1. Tìm hoặc tạo mới Giỏ hàng của Khách hàng
        $cart_query = "SELECT cart_id FROM cart WHERE customer_id = '$cus_escaped' LIMIT 1";
        $cart_res = mysqli_query($this->conn, $cart_query);
        
        if (mysqli_num_rows($cart_res) > 0) {
            $cart_row = mysqli_fetch_assoc($cart_res);
            $cart_id = $cart_row['cart_id'];
        } else {
            // Tạo mã giỏ hàng ngẫu nhiên duy nhất nếu chưa có
            $cart_id = 'CRT' . rand(1000, 9999) . time();
            $insert_cart = "INSERT INTO cart (cart_id, customer_id) VALUES ('$cart_id', '$cus_escaped')";
            mysqli_query($this->conn, $insert_cart);
        }

        // 2. Kiểm tra xem sản phẩm đã nằm trong giỏ chưa
        $item_query = "SELECT quantity FROM cartitem WHERE cart_id = '$cart_id' AND product_id = '$prod_escaped' LIMIT 1";
        $item_res = mysqli_query($this->conn, $item_query);

        if (mysqli_num_rows($item_res) > 0) {
            // Đã có thì tăng số lượng lên 1
            $item_row = mysqli_fetch_assoc($item_res);
            $new_qty = $item_row['quantity'] + $quantity;
            $update_item = "UPDATE cartitem SET quantity = $new_qty WHERE cart_id = '$cart_id' AND product_id = '$prod_escaped'";
            return mysqli_query($this->conn, $update_item);
        } else {
            // Chưa có thì tạo bản ghi Item mới
            $cart_item_id = 'CTI-' . strtoupper(substr(uniqid(), -8));
            $insert_item = "INSERT INTO cartitem (cart_item_id, cart_id, product_id, quantity) VALUES ('$cart_item_id', '$cart_id', '$prod_escaped', $quantity)";
            return mysqli_query($this->conn, $insert_item);
        }
    }

    public function __destruct() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
}
?>
