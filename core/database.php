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

        // Không sử dụng file pem nữa theo yêu cầu
        mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
<<<<<<< HEAD
=======

        // Bỏ qua xác thực chứng chỉ SSL
        mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
>>>>>>> b0de28287d8381b6f88c230b9818ee9e6a08010f

        $ok = @mysqli_real_connect(
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
