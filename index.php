<?php
require_once 'includes/auth.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if (isDoctor()) {
        header('Location: /ss/courses/manage.php');
    } else {
        header('Location: /ss/courses/enrolled.php');
    }
    exit();
}

$pageTitle = 'جامعة القدس المفتوحة - نظام التعليم الإلكتروني';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f4f6f9;
        }

        .navbar {
            background: #2c3e50;
            padding: 1rem;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background: #2980b9;
            color: white;
        }

        .btn-primary:hover {
            background: #3498db;
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
        }

        .hero {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 4rem 1rem;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
        }

        .features {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .feature-card i {
            font-size: 3rem;
            color: #2980b9;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        footer {
            background: #2c3e50;
            color: white;
            padding: 2rem 1rem;
            margin-top: auto;
            text-align: center;
        }

        footer p {
            max-width: 1200px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/ss/" class="navbar-brand">جامعة القدس المفتوحة</a>
            <div class="auth-buttons">
                <a href="/ss/auth/login.php" class="btn btn-outline">تسجيل الدخول</a>
                <a href="/ss/auth/register.php" class="btn btn-primary">إنشاء حساب</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <h1>نظام التعليم الإلكتروني</h1>
        <p>منصة تعليمية متكاملة تتيح للطلاب الوصول إلى المحتوى التعليمي والتفاعل مع المدرسين في أي وقت ومن أي مكان</p>
    </section>

    <section class="features">
        <div class="feature-card">
            <i class="fas fa-book"></i>
            <h3>مساقات تفاعلية</h3>
            <p>مجموعة متنوعة من المساقات التعليمية المصممة خصيصاً لتلبية احتياجات الطلاب</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-video"></i>
            <h3>محتوى مرئي</h3>
            <p>فيديوهات تعليمية عالية الجودة تساعد في فهم المواد الدراسية بشكل أفضل</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-tasks"></i>
            <h3>اختبارات وتقييم</h3>
            <p>نظام متكامل للاختبارات يساعد في تقييم مستوى الطالب ومتابعة تقدمه</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-users"></i>
            <h3>تواصل مباشر</h3>
            <p>تواصل مباشر مع المدرسين والحصول على الدعم المطلوب في الوقت المناسب</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-mobile-alt"></i>
            <h3>تصميم متجاوب</h3>
            <p>واجهة سهلة الاستخدام تعمل على جميع الأجهزة بكفاءة عالية</p>
        </div>

        <div class="feature-card">
            <i class="fas fa-clock"></i>
            <h3>تعلم في أي وقت</h3>
            <p>الوصول إلى المحتوى التعليمي في أي وقت ومن أي مكان بكل سهولة</p>
        </div>
    </section>

    <footer>
        <p>جميع الحقوق محفوظة © <?php echo date('Y'); ?> جامعة القدس المفتوحة</p>
    </footer>
</body>
</html>
