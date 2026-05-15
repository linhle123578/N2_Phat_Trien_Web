<?php

require_once './core/Database.php';

$database = new Database();

$conn = $database->connect();

$sql = "SHOW TABLES";

$result = $conn->query($sql);

while($row = $result->fetch_array()) {

    echo $row[0] . "<br>";

}
?>