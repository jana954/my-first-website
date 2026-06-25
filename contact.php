<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تواصل معنا | رشفة</title>
    <style>
        :root { --gold: #c5a059; --dark: #0c0c0c; }
        body { 
            background: var(--dark); color: white; font-family: serif; margin: 0; 
            background-image: linear-gradient(rgba(0,0,0,0.85), rgba(0,0,0,0.85)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=1200');
            background-attachment: fixed; background-size: cover;
        }
        header { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px 5%; background: rgba(0,0,0,0.9); border-bottom: 1px solid var(--gold);
            position: fixed; top: 0; width: 100%; box-sizing: border-box; z-index: 1000;
        }
        .nav-links a { color: white; text-decoration: none; margin: 0 15px; font-size: 14px; }
        .contact-container {
            max-width: 600px; margin: 150px auto 50px; background: rgba(20, 20, 20, 0.9);
            padding: 40px; border-radius: 10px; border: 1px solid var(--gold); text-align: center;
        }
        input, textarea {
            width: 100%; padding: 12px; margin: 10px 0; background: #222; border: 1px solid #444; color: white; border-radius: 5px;
        }
        .send-btn {
            background: var(--gold); color: black; border: none; padding: 15px; width: 100%; font-weight: bold; cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-links"><a href="cart.php">CART 🛒</a><a href="admin_login.php">ADMIN</a></div>
        <nav class="nav-links">
            <a href="contact.php" style="color: var(--gold);">CONTACT</a>
            <a href="product.php">PRODUCTS</a>
            <a href="index.html">HOME</a>
        </nav>
        <a href="index.html" style="color:white; text-decoration:none; font-size:24px; letter-spacing:2px;">RASHFA</a>
    </header>

    <div class="contact-container">
        <h2 style="color: var(--gold);">تواصل معنا</h2>
        <form action="#" method="POST" onsubmit="return validateContactForm()">
            <input type="text" placeholder="الاسم الكامل" required>
            <input type="email" placeholder="البريد الإلكتروني" required>
            <textarea rows="5" placeholder="رسالتك هنا..."></textarea>
            <button type="submit" class="send-btn">إرسال الرسالة</button>
        </form>
    </div>


<section id="location" style="padding: 60px 10%; background-color: var(--dark-bg); text-align: center;">
    <h2 style="color: var(--gold); font-size: 32px; margin-bottom: 20px; font-family: 'Times New Roman', serif;">موقعنا - OUR LOCATION</h2>
    <p style="color: #bbb; margin-bottom: 30px; font-size: 18px;">📍 جامعة الإمام عبدالرحمن بن فيصل - الحرم الغربي (الراكة)</p>
    
    <div class="map-frame" style="border: 2px solid var(--gold); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.7);">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3575.4746141445!2d50.187422!3d26.343564!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e49e8939c017997%3A0xc3c54d38c62c2f70!2z2KzYp9mF2LnYp9mEINin2YTYpdmF2KfZhSDYudio2K_Yp9mE2LHYrdmF2YYg2KjZhiDZgdmK2LXYhA!5e0!3m2!1sar!2ssa!4v1713885000000!5m2!1sar!2ssa" 
            width="100%" 
            height="450" 
            style="border:0; filter: grayscale(20%) contrast(1.1);" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <div style="margin-top: 20px; color: var(--gold); font-style: italic;">
        * نتشرف بزيارتكم في فرعنا داخل الحرم الجامعي بالراكة.
    </div>
</section>
<script>
function validateContactForm() {
    let message = document.querySelector("textarea").value.trim();

    if (message === "") {
        alert("Please write your message before sending.");
        return false;
    }

    alert("Your message has been sent successfully.");
    return true;
}
</script>
</body>
</html>