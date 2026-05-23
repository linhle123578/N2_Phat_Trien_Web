<?php
class ProductModel {
    private $conn;

    public function __construct() {
        // Khởi tạo kết nối TiDB Cloud với cấu trúc SSL y chang CartModel của bạn
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

    // Lấy danh sách category trả về đối tượng mysqli_result cho vòng lặp while
    public function getCategories() {
        $sql = "SELECT * FROM category ORDER BY name ASC";
        return $this->conn->query($sql);
    }

    // Lấy danh sách sản phẩm kết hợp bộ lọc (Trả về đối tượng mysqli_result)
    public function getProducts($category_filter = '', $search_filter = '', $sort_filter = '') {
        $category_filter = $this->conn->real_escape_string($category_filter);
        $search_filter = $this->conn->real_escape_string($search_filter);
        $sort_filter = $this->conn->real_escape_string($sort_filter);

        $sql = "SELECT p.*, c.name AS category_name 
                FROM product p 
                JOIN category c ON p.category_id = c.category_id 
                WHERE 1=1";

        if (!empty($category_filter)) {
            $sql .= " AND p.category_id = '$category_filter'";
        }
        if (!empty($search_filter)) {
            $sql .= " AND p.product_name LIKE '%$search_filter%'";
        }

        // Logic sắp xếp gốc của bạn
        if ($sort_filter == 'price_asc') {
            $sql .= " ORDER BY p.unit_price ASC";
        } elseif ($sort_filter == 'price_desc') {
            $sql .= " ORDER BY p.unit_price DESC";
        } elseif ($sort_filter == 'name_asc') {
            $sql .= " ORDER BY p.product_name ASC";
        } else {
            $sql .= " ORDER BY p.product_id DESC";
        }

        return $this->conn->query($sql);
    }

    // Thêm sản phẩm vào giỏ hàng hoặc cập nhật số lượng
    public function addToCart($customer_id, $product_id, $quantity = 1) {
        $customer_id = $this->conn->real_escape_string($customer_id);
        $product_id = $this->conn->real_escape_string($product_id);
        
        // Lấy giá hiện tại của sản phẩm
        $p_res = $this->conn->query("SELECT unit_price FROM product WHERE product_id = '$product_id' LIMIT 1");
        if ($p_res->num_rows == 0) return false;
        $product = $p_res->fetch_assoc();
        $unit_price = $product['unit_price'];

        // Kiểm tra xem sản phẩm đã nằm trong giỏ hàng chưa
        $check = $this->conn->query("SELECT cart_item_id, quantity FROM cart_item WHERE customer_id = '$customer_id' AND product_id = '$product_id' LIMIT 1");
        
        if ($check->num_rows > 0) {
            $item = $check->fetch_assoc();
            $new_qty = $item['quantity'] + $quantity;
            $cart_item_id = $item['cart_item_id'];
            return $this->conn->query("UPDATE cart_item SET quantity = $new_qty WHERE cart_item_id = '$cart_item_id'");
        } else {
            $cart_item_id = 'CI-' . strtoupper(substr(uniqid(), -8));
            return $this->conn->query("INSERT INTO cart_item (cart_item_id, customer_id, product_id, quantity, unit_price, created_at) 
                                      VALUES ('$cart_item_id', '$customer_id', '$product_id', $quantity, $unit_price, NOW())");
        }
    }

    // Đếm tổng số mặt hàng trong giỏ để hiển thị lên Badge icon
    public function getCartCount($customer_id) {
        $customer_id = $this->conn->real_escape_string($customer_id);
        $res = $this->conn->query("SELECT COUNT(*) as total FROM cart_item WHERE customer_id = '$customer_id'");
        $row = $res->fetch_assoc();
        return $row['total'] ?? 0;
    }
}