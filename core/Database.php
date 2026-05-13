<?php

class Database {

    private $host = "localhost";

    private $username = "root";

    private $password = "";

    private $dbname = "db_web_farm2home";

    public $conn;

    public function connect(){

        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->dbname
        );

        if($this->conn->connect_error){

            die("Kết nối thất bại");

        }

        return $this->conn;

    }

}