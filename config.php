<?php
$conn = mysqli_connect("127.0.0.1", "root", "", "coffee_shop");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>