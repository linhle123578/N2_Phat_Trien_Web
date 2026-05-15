<?php

class Database {

    private $conn;

    public function __construct() {

        $config = require __DIR__ . '/../config/database.php';

        $this->conn = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['dbname'],
            $config['port']
        );

        if ($this->conn->connect_error) {
            die("Connection failed");
        }
    }

    public function connect() {
        return $this->conn;
    }
}