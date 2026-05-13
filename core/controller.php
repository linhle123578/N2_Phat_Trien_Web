<?php

class Controller {

    public function view($viewName){

        require_once "../app/views/" . $viewName . ".php";

    }

}