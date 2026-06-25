<?php
session_start();
require_once("config.php");
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: manage_products.php");
exit();
?>