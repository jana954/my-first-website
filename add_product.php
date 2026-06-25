<?php
session_start();
require_once("config.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_POST['add'])) {
    $name = trim($_POST['product_name']);
    $desc = trim($_POST['description']);
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'];
    $category = $_POST['category'];
    $status = $_POST['status'];

    $image = "";
    if (!empty($_FILES['image']['name'])) {
        $image = basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $image);
    }

    $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, stock_quantity, category, image_file, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdisss", $name, $desc, $price, $stock, $category, $image, $status);
    $stmt->execute();

    header("Location: manage_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | RASHFA</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Georgia, serif;
            background: url("images/coffee-bg.jpg") center center/cover no-repeat fixed;
            min-height: 100vh;
            color: white;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            z-index: 0;
        }

        .navbar {
            position: relative;
            z-index: 2;
            width: 100%;
            background: rgba(0, 0, 0, 0.88);
            border-bottom: 1px solid #b9924e;
            padding: 25px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 2px;
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 17px;
            transition: 0.3s;
        }

        .nav-links a:hover {
            color: #d4ad63;
        }

        .container {
            position: relative;
            z-index: 2;
            width: 92%;
            max-width: 800px;
            margin: 50px auto;
            background: rgba(15, 15, 15, 0.84);
            border: 1px solid rgba(185, 146, 78, 0.8);
            border-radius: 18px;
            padding: 35px;
            backdrop-filter: blur(6px);
            box-shadow: 0 0 25px rgba(0,0,0,0.35);
        }

        .page-title {
            font-size: 42px;
            color: #d4ad63;
            margin-bottom: 28px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #f0c97d;
            font-size: 17px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.05);
            color: white;
            border-radius: 10px;
            outline: none;
            font-size: 15px;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: #d4ad63;
            box-shadow: 0 0 8px rgba(212, 173, 99, 0.25);
        }

        .form-control::placeholder {
            color: #bcbcbc;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
        }

        .file-input {
            color: white;
            font-size: 15px;
        }

        .btn-row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 14px 22px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            transition: 0.3s;
            text-align: center;
        }

        .btn-gold {
            background: #c9a15d;
            color: black;
        }

        .btn-gold:hover {
            background: #ddb56f;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: #d4ad63;
            border: 1px solid #d4ad63;
        }

        .btn-outline:hover {
            background: #d4ad63;
            color: black;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }

            .container {
                width: 95%;
                padding: 22px;
            }

            .page-title {
                font-size: 32px;
            }

            .btn-row {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">RASHFA</div>

        <div class="nav-links">
            <a href="index.html">HOME</a>
            <a href="product.php">PRODUCTS</a>
            <a href="contact.php">CONTACT</a>
            <a href="cart.php">CART</a>
        </div>
    </header>

    <div class="container">
        <h2 class="page-title">Add Product</h2>

        <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" class="form-control" placeholder="Enter product name" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" placeholder="Enter product description"></textarea>
            </div>

            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Enter price" required>
            </div>

            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" class="form-control" placeholder="Enter stock quantity" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="Coffee">Coffee</option>
                    <option value="Sweets">Sweets</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="available">available</option>
                    <option value="not available">not available</option>
                </select>
            </div>

            <div class="form-group">
                <label>Upload Image</label>
                <input type="file" name="image" class="file-input" required>
            </div>

            <div class="btn-row">
                <button type="submit" name="add" class="btn btn-gold">Add Product</button>
                <a href="manage_products.php" class="btn btn-outline">Back</a>
            </div>

        </form>
    </div>
<script>
function validateForm() {
    let price = document.forms[0]["price"].value;
    let stock = document.forms[0]["stock_quantity"].value;

    if(price <= 0){
        alert("Price must be greater than 0");
        return false;
    }

    if(stock < 0){
        alert("Stock cannot be negative");
        return false;
    }

    return true;
}
</script>
</body>
</html>