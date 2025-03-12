<?php
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_path' => '/ss/'
]);

require_once __DIR__ . '/../config/database.php';

function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        session_destroy();
        header('Location: /ss/auth/login.php');
        exit();
    }
    $_SESSION['full_name'] = $_SESSION['full_name'] ?? 'مستخدم';
}

function login($username, $password) {
    global $conn;
    
    $username = $conn->real_escape_string($username);
    $stmt = $conn->prepare("SELECT id, password, full_name, user_type FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
    }
    return false;
}

function register($username, $password, $email, $full_name, $user_type) {
    global $conn;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $email, $full_name, $user_type);
    
    return $stmt->execute();
}

function isDoctor() {
    return $_SESSION['user_type'] === 'doctor';
}

function isStudent() {
    return $_SESSION['user_type'] === 'student';
}

function logout() {
    session_destroy();
    header('Location: /ss/auth/login.php');
    exit();
}
