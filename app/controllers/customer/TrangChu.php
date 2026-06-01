<?php

require_once "../core/controller.php";
require_once "../app/models/TrangChuModel.php";

class HomeController extends controller {

    public function index() {
        $this->view("customer/TrangChu");
    }
}
