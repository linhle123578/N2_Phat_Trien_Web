<?php

class OrderDetailModel {
    private $conn;

    public function __construct() {
        $this->conn = mysqli_init();
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
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
        mysqli_set_charset($this->conn, "utf8");
    }

    /**
     * Thêm 1 sản phẩm vào orderitem (dùng bảng orderitem theo chuẩn CartModel)
     */
    public function addDetail($order_id, $product_id, $price, $quantity) {
        $oid   = $this->conn->real_escape_string($order_id);
        $pid   = $this->conn->real_escape_string($product_id);
        $p     = (float)$price;
        $qty   = (int)$quantity;
        $iid   = 'OI-' . strtoupper(substr(uniqid(), -8));

        return $this->conn->query(
            "INSERT INTO orderitem (order_item_id, order_id, product_id, quantity, price)
             VALUES ('$iid', '$oid', '$pid', $qty, $p)"
        );
    }
}
?>
