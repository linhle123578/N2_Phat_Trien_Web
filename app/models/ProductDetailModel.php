<?php
class ProductDetailModel
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    public function getFirstProduct(): ?array
    {
        $query = "SELECT * FROM product LIMIT 1";
        $result = mysqli_query($this->conn, $query);
        $row = mysqli_fetch_assoc($result);
        return $row ?: null;
    }
    public function getProductById(string $id): ?array
    {
        $query = "SELECT * FROM product WHERE product_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }
    public function getRelatedProducts(string $category_id, string $exclude_id, int $limit = 4): array
    {
        $query = "SELECT * FROM product WHERE category_id = ? AND product_id != ? LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) return [];
        mysqli_stmt_bind_param($stmt, "ssi", $category_id, $exclude_id, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $products;
    }
}
