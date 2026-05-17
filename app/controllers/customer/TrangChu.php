<?php

require_once "../core/controller.php";
require_once "../app/models/TrangChuModel.php";

class HomeController extends controller {

    public function index() {

        // 🔥 chỉ truyền sản phẩm nổi bật
        //$GLOBALS['featuredProducts'] = $featuredProducts;

        $this->view("customer/TrangChu");
    }
}