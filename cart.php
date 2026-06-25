<?php
session_start();
require_once("config.php");
// تأكيد تفعيل السلة كمصفوفة فارغة إذا لم تكن موجودة
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// متغيرات الفاتورة والتحكم بالطباعة في نفس الصفحة
$show_invoice = false;
$cart_errors = [];
$order_number = rand(10000, 99999);
$order_date = date("Y-m-d H:i");

// === 1. معالجة إضافة منتج جديد قادم من صفحة product.php ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $p_id = $_POST['id'];
    $p_name = $_POST['name'];
    $p_price = floatval($_POST['price']);

    // استقبال رابط الصورة المخصص المرسل من المنيو
    if (isset($_POST['product_image']) && !empty($_POST['product_image'])) {
        $p_img = $_POST['product_image'];
    } else {
        $p_img = "https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?q=80&w=200";
    }

    // التحقق من المخزون قبل إضافة المنتج للسلة
    $check = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $check->bind_param("i", $p_id);
    $check->execute();
    $stockData = $check->get_result()->fetch_assoc();
    $available_stock = $stockData ? (int)$stockData['stock_quantity'] : 0;

    $current_qty = isset($_SESSION['cart'][$p_id]) ? $_SESSION['cart'][$p_id]['quantity'] : 0;

    if ($available_stock <= 0 || $current_qty + 1 > $available_stock) {
        $cart_errors[$p_id] = "عذراً، الكمية المطلوبة غير متوفرة في المخزون.";
    } else {
        // إضافة المنتج أو زيادة الكمية داخل السلة بشكل مستقر
        if (isset($_SESSION['cart'][$p_id])) {
            $_SESSION['cart'][$p_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$p_id] = [
                'name' => $p_name,
                'price' => $p_price,
                'img' => $p_img,
                'quantity' => 1
            ];
        }
    }
}

// === 2. معالجة زر تأكيد وإتمام الطلب وحفظ جميع المنتجات في الكوكيز ===
if (isset($_POST['checkout_success'])) {
    if (!empty($_SESSION['cart'])) {

        $has_stock_error = false;

        // أولاً: التأكد من توفر جميع الكميات قبل خصم أي منتج من المخزون
        foreach ($_SESSION['cart'] as $p_id => $item) {

            $qty = $item['quantity'];

            $check = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
            $check->bind_param("i", $p_id);
            $check->execute();

            $stockData = $check->get_result()->fetch_assoc();

            if (!$stockData || $stockData['stock_quantity'] < $qty) {
                $cart_errors[$p_id] = "عذراً، الكمية المطلوبة غير متوفرة في المخزون.";
                $has_stock_error = true;
            }
        }

        // ثانياً: إذا كل الكميات متوفرة، نخصم من المخزون ونكمل الطلب
        if (!$has_stock_error) {

            // نقل المشتريات الحالية إلى مصفوفة الفاتورة المؤقتة
            $_SESSION['ordered_items'] = $_SESSION['cart'];

            foreach ($_SESSION['cart'] as $p_id => $item) {

                $qty = $item['quantity'];

                $stmt = $conn->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE product_id = ?
                ");

                $stmt->bind_param("ii", $qty, $p_id);
                $stmt->execute();
            }

            $show_invoice = true; // تفعيل وضع الفاتورة والطباعة

            // استخراج أسماء جميع المنتجات الموجودة في السلة حالياً في مصفوفة بسيطة
            $all_product_names = [];
            foreach ($_SESSION['cart'] as $item) {
                $all_product_names[] = $item['name'];
            }

            // تحويل مصفوفة الأسماء إلى نص JSON مشفر عشان نقدر نخزنه داخل الكوكيز
            $cookie_value = json_encode($all_product_names, JSON_UNESCAPED_UNICODE);

            // تخزين جميع المنتجات داخل الكوكيز لمدة 30 يوماً
            setcookie('past_purchases', $cookie_value, time() + (86400 * 30), "/");

            // تفريغ السلة الأساسية لدورة طلب جديدة ونظيفة
            $_SESSION['cart'] = [];
        }
    }
}

// === 3. معالجة تحديثات السلة العادية (تعديل الكمية أو الحذف) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$show_invoice) {
    // تعديل الكمية
    if (isset($_POST['update_qty'])) {
        $p_id = $_POST['product_id'];
        $new_qty = intval($_POST['quantity']);
        if ($new_qty > 0 && isset($_SESSION['cart'][$p_id])) {

            $check = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
            $check->bind_param("i", $p_id);
            $check->execute();

            $stockData = $check->get_result()->fetch_assoc();
            $available_stock = $stockData ? (int)$stockData['stock_quantity'] : 0;

            if ($new_qty <= $available_stock) {
                $_SESSION['cart'][$p_id]['quantity'] = $new_qty;
            } else {
                $cart_errors[$p_id] = "عذراً، الكمية المطلوبة غير متوفرة في المخزون.";
            }
        }
    }
    // حذف صنف واحد
    if (isset($_POST['delete_item'])) {
        $p_id = $_POST['product_id'];
        unset($_SESSION['cart'][$p_id]);
    }
    // مسح السلة كاملة
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    }
}

// === 4. حساب الإجمالي الكلي بناءً على الوضع الحالي (سلة أم فاتورة) ===
$total_cart_price = 0;
$items_to_display = $show_invoice ? (isset($_SESSION['ordered_items']) ? $_SESSION['ordered_items'] : []) : $_SESSION['cart'];
foreach ($items_to_display as $item) {
    $total_cart_price += ($item['price'] * $item['quantity']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php echo $show_invoice ? "فاتورة الشراء" : "سلة المشتريات"; ?> - رشفة</title>
    <style>
        :root { --gold: #c5a059; --dark: #0c0c0c; --card-bg: rgba(20, 20, 20, 0.95); }
        body { 
            background: var(--dark); color: white; font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; 
            background-image: linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=1200');
            background-attachment: fixed; background-size: cover;
        }
        header { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px 5%; background: rgba(0,0,0,0.9); border-bottom: 1px solid var(--gold);
            position: fixed; top: 0; width: 100%; box-sizing: border-box; z-index: 1000;
        }
        .nav-links a { color: white; text-decoration: none; margin: 0 15px; font-size: 14px; }
        .brand { font-size: 24px; font-weight: bold; color: white; text-decoration: none; letter-spacing: 2px; }

        .cart-container { max-width: 1200px; margin: 130px auto 60px; padding: 0 4%; display: flex; gap: 30px; }
        .items-area { flex: 1.7; }
        .summary-area { flex: 1; }
        .panel { background: var(--card-bg); border: 1px solid #2a2a2a; border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); }
        .panel-title { font-size: 22px; color: var(--gold); margin-top: 0; margin-bottom: 25px; border-bottom: 1px solid #333; padding-bottom: 12px; }

        .product-strip { display: flex; align-items: center; justify-content: space-between; background: rgba(30, 30, 30, 0.7); border: 1px solid #222; padding: 20px; border-radius: 10px; margin-bottom: 15px; }
        .product-main-info { display: flex; align-items: center; gap: 20px; }
        .strip-img { width: 85px; height: 85px; object-fit: cover; border-radius: 8px; border: 1px solid #333; }
        .strip-text h4 { margin: 0 0 8px 0; font-size: 18px; }
        .strip-text span { color: var(--gold); font-weight: bold; }

        .qty-box { display: flex; align-items: center; gap: 10px; }
        .qty-num { width: 55px; background: #111; border: 1px solid #444; color: white; text-align: center; padding: 8px; border-radius: 6px; }
        .btn-action { background: none; border: none; cursor: pointer; font-size: 14px; padding: 6px 12px; border-radius: 5px; font-weight: bold; }
        .btn-save { background: rgba(197, 160, 89, 0.15); color: var(--gold); border: 1px solid var(--gold); }
        .btn-remove { color: #ff4d4d; }

        .bill-row { display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 15px; color: #b3b3b3; }
        .bill-total { border-top: 1px solid #333; padding-top: 18px; margin-top: 18px; font-size: 19px; color: white; font-weight: bold; }
        .price-amount { color: var(--gold); }

        .btn-main-checkout { background: linear-gradient(135deg, var(--gold), #b48f48); color: black; border: none; width: 100%; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 25px; }
        .btn-empty-all { background: none; border: 1px solid #ff4d4d; color: #ff4d4d; width: 100%; padding: 10px; border-radius: 8px; cursor: pointer; margin-top: 12px; }
        
        .past-cookie-box { background: rgba(197, 160, 89, 0.05); border: 1px dashed rgba(197, 160, 89, 0.3); border-radius: 8px; padding: 15px; margin-top: 25px; text-align: right; }
        .past-item-tag { display: inline-block; background: rgba(197, 160, 89, 0.15); color: var(--gold); padding: 3px 8px; border-radius: 4px; margin: 3px; font-size: 13px; border: 1px solid rgba(197, 160, 89, 0.3); }

        /* واجهة الفاتورة الرسمية للطباعة التلقائية */
        .invoice-view { max-width: 800px; margin: 140px auto 40px; background: white; color: #333; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid var(--gold); padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .invoice-table th { background: #0c0c0c; color: white; padding: 12px; text-align: right; }
        .invoice-table td { padding: 12px; border-bottom: 1px solid #eee; color: #333; }
        .btn-print-now { background: var(--gold); color: black; padding: 12px 30px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; }

        @media print {
            header, .btn-print-block, .no-print { display: none !important; }
            body { background: white !important; background-image: none !important; color: black !important; }
            .invoice-view { margin: 0; padding: 0; border: none; box-shadow: none; }
        }
    </style>
</head>
<body>

    <header class="no-print">
        <div class="nav-links"><a href="cart.php" style="color: var(--gold);">CART 🛒</a><a href="admin_login.php">ADMIN</a></div>
        <nav class="nav-links">
            <a href="contact.php">CONTACT</a>
            <a href="product.php">PRODUCTS</a>
            <a href="index.html">HOME</a>
        </nav>
        <a href="index.html" class="brand">RASHFA</a>
    </header>

    <?php if ($show_invoice): ?>
        <div class="invoice-view">
            <div class="invoice-header">
                <div>
                    <h2 style="margin: 0; color: #0c0c0c;">RASHFA | رشفة</h2>
                    <p style="margin: 5px 0 0; color: #666; font-size: 14px;">شكراً لطلبكم من رشفة!</p>
                </div>
                <div style="text-align: left; font-size: 14px; color: #555;">
                    <div><strong>رقم الفاتورة:</strong> #<?php echo $order_number; ?></div>
                    <div><strong>التاريخ:</strong> <?php echo $order_date; ?></div>
                </div>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th style="text-align: center;">السعر</th>
                        <th style="text-align: center;">الكمية</th>
                        <th style="text-align: left;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['ordered_items'] as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                        <td style="text-align: center;"><?php echo $item['price']; ?> ريال</td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: left; color: #b48f48; font-weight: bold;"><?php echo ($item['price'] * $item['quantity']); ?> ريال</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: #fdfaf4; font-weight: bold; font-size: 16px;">
                        <td colspan="3" style="text-align: left; border-top: 2px solid var(--gold);">الإجمالي النهائي الخاضع للضريبة:</td>
                        <td style="text-align: left; border-top: 2px solid var(--gold); color: #0c0c0c;"><?php echo $total_cart_price; ?> ريال</td>
                    </tr>
                </tbody>
            </table>

            <div class="btn-print-block" style="margin-top: 30px; display: flex; justify-content: space-between;">
                <a href="product.php" style="color: #666; text-decoration: none; align-self: center; font-weight: bold;">← العودة للمنيو لشراء منتجات جديدة</a>
                <button onclick="window.print();" class="btn-print-now">طباعة الفاتورة الفورية 🖨️</button>
            </div>
        </div>

        <script>
            window.onload = function() { window.print(); };
        </script>

    <?php else: ?>
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="invoice-view panel" style="max-width: 500px; text-align: center; background: var(--card-bg); margin-top: 180px;">
                <div style="font-size: 50px; margin-bottom: 15px;">🛒</div>
                <h3 style="color: var(--gold); margin: 0 0 10px 0;">السلة فارغة حالياً</h3>
                <p style="color: #aaa; font-size: 14px; margin-bottom: 20px;">لم تقم بإضافة رشفات إلى سلتك بعد.</p>
                <a href="product.php" style="display: inline-block; background: var(--gold); color: black; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold;">العودة للمنيو لتصفح المنتجات</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="items-area panel">
                    <h3 class="panel-title">الأصناف المختارة في سلتك ☕</h3>
                    <?php foreach ($_SESSION['cart'] as $p_id => $item): ?>
                    <div class="product-strip">
                        <div class="product-main-info">
                            <img src="<?php echo htmlspecialchars($item['img']); ?>" class="strip-img" alt="">
                            <div class="strip-text">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <span><?php echo $item['price']; ?> ريال</span>
                            </div>
                        </div>
                        <div class="product-actions">
                            <form method="POST" class="qty-box">
                                <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                <input type="number" name="quantity" class="qty-num" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="submit" name="update_qty" class="btn-action btn-save">تعديل</button>
                            </form>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $p_id; ?>">
                                <button type="submit" name="delete_item" class="btn-action btn-remove">حذف ✖</button>
                            </form>

                            <?php if (isset($cart_errors[$p_id])): ?>
                                <div style="
                                    color:#ff8d8d;
                                    border:1px solid #ff4d4d;
                                    background:rgba(255, 77, 77, 0.08);
                                    padding:8px 10px;
                                    border-radius:6px;
                                    margin-top:10px;
                                    font-size:13px;
                                    font-weight:bold;
                                    text-align:center;
                                ">
                                    <?php echo $cart_errors[$p_id]; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 20px; text-align: right;">
                        <a href="product.php" style="color: var(--gold); text-decoration: none; font-size: 15px; font-weight: bold;">➕ إضافة المزيد من المنتجات من المنيو</a>
                    </div>
                </div>

                <div class="summary-area panel">
                    <h3 class="panel-title">ملخص الفاتورة 🧾</h3>
                    <div class="bill-row">
                        <span>عدد الأصناف الفريدة:</span>
                        <span><?php echo count($_SESSION['cart']); ?></span>
                    </div>
                    <div class="bill-row">
                        <span>رسوم التوصيل والخدمة:</span>
                        <span style="color: #55efc4; font-weight: bold;">مجانًا</span>
                    </div>
                    <div class="bill-row bill-total">
                        <span>الإجمالي النهائي:</span>
                        <span class="price-amount"><?php echo $total_cart_price; ?> ريال</span>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="checkout_success" class="btn-main-checkout">تأكيد وإتمام الطلب 👍</button>
                        <button type="submit" name="clear_cart" class="btn-empty-all">مسح السلة بالكامل 🗑️</button>
                    </form>

                    <?php if (isset($_COOKIE['past_purchases'])): ?>
                    <div class="past-cookie-box">
                        <p style="margin: 0 0 10px 0; font-size: 14px; color: #ccc;">مرحباً بعودتك! رشفاتك المفضلة السابقة (Cookies):</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            <?php
                            // تحويل النص المحفوظ بالكوكيز مجدداً إلى مصفوفة وعرضها بالكامل
                            $past_items = json_decode($_COOKIE['past_purchases'], true);
                            if (is_array($past_items)) {
                                foreach ($past_items as $past_name) {
                                    echo '<span class="past-item-tag">✨ ' . htmlspecialchars($past_name) . '</span>';
                                }
                            } else {
                                echo '<span class="past-item-tag">✨ ' . htmlspecialchars($_COOKIE['past_purchases']) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</body>
</html>