<?php
/**
 * Support Employee Logout controller
 */
require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear employee session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start a new session for the guest status message
session_start();
$_SESSION['success'] = 'تم تسجيل الخروج بنجاح من نظام الدعم الفني.';
header('Location: ' . BASE_URL . 'support/login.php');
exit();
