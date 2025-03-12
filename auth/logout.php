<?php
require_once '../includes/auth.php';

// Ensure we have a valid session before destroying it
if (session_status() === PHP_SESSION_ACTIVE) {
    // Clear all session variables
    $_SESSION = array();

    // Get session cookie parameters
    $params = session_get_cookie_params();

    // Clear session cookie with secure parameters matching our security improvements
    setcookie(
        session_name(),
        '',
        time() - 3600,
        '/ss/',
        $params['domain'],
        true, // Secure flag
        true  // HttpOnly flag
    );

    // Destroy the session
    session_destroy();
}

// Redirect to login page with /ss/ base path as per our security improvements
header('Location: /ss/auth/login.php');
exit();
