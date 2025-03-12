<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Buffer output to prevent headers already sent errors
ob_start();

require_once __DIR__ . '/auth.php';
validateSession();

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'جامعة القدس المفتوحة - نظام التعليم الإلكتروني';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
            flex-direction: column;
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

        .navbar-menu {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .navbar-menu a:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
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

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #2ecc71;
            color: white;
        }

        .alert-error {
            background: #e74c3c;
            color: white;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: right;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .text-center {
            text-align: center;
        }

        @media (max-width: 768px) {
            .navbar-menu {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/ss/" class="navbar-brand">جامعة القدس المفتوحة</a>
            <div class="navbar-menu">
                <?php if (isDoctor()): ?>
                    <a href="/ss/courses/manage.php"><i class="fas fa-book"></i> المساقات</a>
                <?php else: ?>
                    <a href="/ss/courses/list.php"><i class="fas fa-book"></i> المساقات المتاحة</a>
                    <a href="/ss/courses/enrolled.php"><i class="fas fa-graduation-cap"></i> مساقاتي</a>
                <?php endif; ?>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="/ss/auth/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
