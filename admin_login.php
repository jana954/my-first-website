<?php
session_start();
error_reporting(0);
@include("config.php"); 

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    if(isset($conn)) {
        $result = $conn->query("SELECT * FROM admin WHERE admin_name='$username' AND password='$password'");
        if ($result->num_rows > 0) {
            $_SESSION['admin'] = $username;
            header("Location: manage_products.php");
            exit();
        } else { $error = "بيانات الدخول غير صحيحة"; }
    } else {
    $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
}
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الإدارة | رشفة</title>
    <style>
        :root {
            --gold-color: #c5a059;
            --dark-bg: #0c0c0c;
            --overlay: rgba(0, 0, 0, 0.7);
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--dark-bg);
            background-image: linear-gradient(var(--overlay), var(--overlay)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=2000');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            font-family: 'Times New Roman', serif; 
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 8%;
            background: rgba(0, 0, 0, 0.85);
            border-bottom: 1px solid rgba(197, 160, 89, 0.3);
            position: fixed;
            top: 0; width: 100%; box-sizing: border-box; z-index: 1000;
        }

        .brand-name {
            color: white;
            font-size: 28px;
            letter-spacing: 3px;
            text-decoration: none;
            font-weight: bold;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-size: 14px;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        nav a:hover { color: var(--gold-color); }

        .login-wrapper {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            background: rgba(18, 18, 18, 0.9);
            padding: 50px 40px;
            border-radius: 5px; 
            border: 1px solid var(--gold-color);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }

        .login-card h2 {
            color: var(--gold-color);
            font-size: 30px;
            margin-bottom: 35px;
            letter-spacing: 2px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: right;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            background: transparent;
            border: 1px solid #444;
            color: white;
            border-radius: 0;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: var(--gold-color);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--gold-color);
            color: black;
            border: none;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.4s;
        }

        .btn-submit:hover {
            background: white;
            transform: scale(1.02);
        }

        .error-text {
            color: #ff4b4b;
            font-size: 14px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <header>
        <div class="side-nav">
            <a href="cart.php">CART 🛒</a>
        </div>
        <nav>
            <a href="contact.php">CONTACT</a>
            <a href="product.php">PRODUCTS</a>
            <a href="index.html">HOME</a>
        </nav>
        <a href="index.html" class="brand-name">RASHFA</a>
    </header>

    <div class="login-wrapper">
        <div class="login-card">
            <h2>ADMIN LOGIN</h2>
            
            <?php if($error) echo "<div class='error-text'>$error</div>"; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="USERNAME" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="PASSWORD" required>
                </div>
                <button type="submit" name="login" class="btn-submit">LOGIN</button>
            </form>
        </div>
    </div>

</body>
</html>