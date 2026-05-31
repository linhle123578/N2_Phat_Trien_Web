<?php

require_once "core/model.php";

class ProductModel extends model {

    // Lấy sản phẩm
    public function getFeaturedProducts() {

        $sql = "
            SELECT *
            FROM product
        ";

        $result = mysqli_query($this->db, $sql);

        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }
        }

        return $products;
    }
}
