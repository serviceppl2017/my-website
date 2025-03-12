<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '11123322231');
define('DB_NAME', 'quds_elearning');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

$conn->select_db(DB_NAME);

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
