<?php

class Database {

    private $conn;

    public function connect() {

        $config = require __DIR__ . '/../config/database.php';

        $parts = parse_url($config['url']);

        $host = $parts['host'];
        $port = $parts['port'];
        $user = $parts['user'];
        $pass = $parts['pass'];
        $db   = ltrim($parts['path'], '/');

        $this->conn = mysqli_init();

        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);

        $ok = mysqli_real_connect(
            $this->conn,
            $host,
            $user,
            $pass,
            $db,
            $port,
            NULL,
            MYSQLI_CLIENT_SSL
        );

        if (!$ok) {
            die("DB connection failed: " . mysqli_connect_error());
        }

        return $this->conn;
    }
}