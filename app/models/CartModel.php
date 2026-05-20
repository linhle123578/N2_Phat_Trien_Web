<?php
class CartModel {
    private $conn;

    public function __construct() {
        // Khởi tạo kết nối cho database đám mây (yêu cầu SSL)
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        
        // Thực hiện kết nối tới TiDB Cloud
        $success = mysqli_real_connect(
            $this->conn,
            "gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com", // Host
            "3YHrkxqAKWynehu.root",                                  // User
            "BzDRrZAdAT2jLuyd",                                      // Password
            "db_web_farm2home",                                      // Database
            4000,                                                    // Port
            NULL,
            MYSQLI_CLIENT_SSL
        );

        // Kiểm tra kết nối
        if (!$success) {
            die("Kết nối database thất bại: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->conn, "utf8");
    }

    // Lấy danh sách giỏ hàng
    public function getCartItems($customer_id) {
        $customer_id = $this->conn->real_escape_string($customer_id);
        $sql = "SELECT ci.cart_item_id, ci.product_id, ci.quantity, ci.unit_price, p.product_name, p.product_image, p.stock
        FROM cart c
        JOIN cartitem ci ON c.cart_id = ci.cart_id
        JOIN product p ON ci.product_id = p.product_id
        WHERE c.customer_id = '$customer_id'";
        
        $result = $this->conn->query($sql);
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    // Xóa 1 sản phẩm khỏi giỏ
    public function deleteItem($cart_item_id) {
        $id = $this->conn->real_escape_string($cart_item_id);
        return $this->conn->query("DELETE FROM cartitem WHERE cart_item_id = '$id'");
    }

    // Xử lý logic tạo đơn hàng
    public function createOrder($customer_id, $selected_ids, $qty_map) {
        $escaped_ids = array_map(fn($id) => $this->conn->real_escape_string($id), $selected_ids);
        $in = "'" . implode("','", $escaped_ids) . "'";

        $sql_items = "SELECT ci.cart_item_id, ci.product_id, ci.unit_price 
                      FROM cartitem ci WHERE ci.cart_item_id IN ($in)";
        $res = $this->conn->query($sql_items);
        $items = [];
        while ($r = $res->fetch_assoc()) $items[] = $r;

        if (empty($items)) return false;

        $total_amount = 0;
        foreach ($items as $item) {
            $qty = max(1, (int)($qty_map[$item['cart_item_id']] ?? 1));
            $total_amount += $item['unit_price'] * $qty;
        }

        // Lấy địa chỉ
        $r_addr = $this->conn->query("SELECT address_id FROM address WHERE customer_id = '$customer_id' AND is_default = 1 LIMIT 1")->fetch_assoc();
        $address_id = $r_addr ? "'" . $r_addr['address_id'] . "'" : "NULL";

        // Insert Order
        $order_id = 'ORD-' . strtoupper(substr(uniqid(), -8));
        $ok = $this->conn->query("INSERT INTO `order` (order_id, customer_id, address_id, order_status, total_amount, created_at)
                                  VALUES ('$order_id', '$customer_id', $address_id, 'pending', $total_amount, NOW())");
        if (!$ok) return false;

        // Insert Order items & Delete from cart
        foreach ($items as $item) {
            $qty = max(1, (int)($qty_map[$item['cart_item_id']] ?? 1));
            $order_item_id = 'OI-' . strtoupper(substr(uniqid(), -8));
            $pid = $item['product_id'];
            $price = $item['unit_price'];

            $this->conn->query("INSERT INTO orderitem (order_item_id, order_id, product_id, quantity, price)
                                VALUES ('$order_item_id', '$order_id', '$pid', $qty, $price)");
            $this->conn->query("DELETE FROM cartitem WHERE cart_item_id = '{$item['cart_item_id']}'");
        }
        return $order_id;
    }
}
?>