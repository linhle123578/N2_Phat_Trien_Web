<?php
require_once __DIR__ . '/../../models/ProductDetailModel.php';

class ProductDetailController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new ProductDetailModel($conn);
    }
//
    public function index()
    {
        $current_id = $_GET['id'] ?? '';

        if (empty($current_id)) {
            $product = $this->model->getFirstProduct();
        } else {
            $product = $this->model->getProductById($current_id);
        }

        if (!$product) {
            die("Sản phẩm không tồn tại hoặc cơ sở dữ liệu chưa có dữ liệu!");
        }

        $related_products = $this->model->getRelatedProducts($product['category_id'], $product['product_id']);

        $data = compact('product', 'related_products');
        $this->render('ProductDetail', $data);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . "/../../views/customer/{$view}.php";
    }
}
