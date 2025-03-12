<?php
require_once '../includes/auth.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    
    // Basic validation
    $errors = [];
    if (empty($username)) $errors[] = "اسم المستخدم مطلوب";
    if (empty($password)) $errors[] = "كلمة المرور مطلوبة";
    if (empty($email)) $errors[] = "البريد الإلكتروني مطلوب";
    if (empty($full_name)) $errors[] = "الاسم الكامل مطلوب";
    if (!in_array($user_type, ['student', 'doctor'])) $errors[] = "نوع المستخدم غير صالح";
    
    if (empty($errors)) {
        if (register($username, $password, $email, $full_name, $user_type)) {
            header('Location: login.php?registered=1');
            exit();
        } else {
            $errors[] = "حدث خطأ أثناء التسجيل. قد يكون اسم المستخدم أو البريد الإلكتروني مستخدماً بالفعل.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد - جامعة القدس المفتوحة</title>
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
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            margin: 0 1rem;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }

        input, select {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
        }

        select {
            padding-right: 15px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2395a5a6' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 15px center;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2980b9;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #3498db;
        }

        .errors {
            background: #ff6b6b;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .errors ul {
            list-style-position: inside;
            margin: 0;
            padding: 0;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }

        .login-link a {
            color: #2980b9;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>جامعة القدس المفتوحة</h1>
            <p>تسجيل حساب جديد</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="اسم المستخدم" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="كلمة المرور" required>
            </div>
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="البريد الإلكتروني" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="full_name" placeholder="الاسم الكامل" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <i class="fas fa-user-graduate"></i>
                <select name="user_type" required>
                    <option value="">اختر نوع الحساب</option>
                    <option value="student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'student') ? 'selected' : ''; ?>>طالب</option>
                    <option value="doctor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'doctor') ? 'selected' : ''; ?>>مدرس</option>
                </select>
            </div>
            <button type="submit" name="register">تسجيل</button>
        </form>
        
        <div class="login-link">
            <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
        </div>
    </div>
</body>
</html>
