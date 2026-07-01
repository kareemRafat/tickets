<?php
/**
 * Configuration file
 * Sets up error reporting, secure sessions, and global constants.
 */

// Egypt timezone
date_default_timezone_set('Africa/Cairo');

// Error reporting levels
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production

// Session security configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
}

// Enable secure cookies if HTTPS is active
$isSecure = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $isSecure = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isSecure = true;
}

if (session_status() === PHP_SESSION_NONE) {
    if ($isSecure) {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.cookie_samesite', 'Strict');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global constant definitions
if (!defined('SYSTEM_NAME')) {
    define('SYSTEM_NAME', 'تذاكر خدمة العملاء');
}

if (!defined('BASE_URL')) {
    $protocol = $isSecure ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Detect base directory dynamically
    // Get the absolute paths to compute the subfolder relative to DOCUMENT_ROOT
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
    $currentDir = str_replace('\\', '/', __DIR__);
    $rootDir = str_replace('\\', '/', dirname($currentDir)); // Parent of config/ is root
    
    $subDir = '';
    if (!empty($docRoot) && strpos($rootDir, $docRoot) === 0) {
        $subDir = substr($rootDir, strlen($docRoot));
    }
    
    $subDir = trim($subDir, '/');
    
    $base = $protocol . '://' . $host;
    if ($subDir !== '') {
        $base .= '/' . $subDir . '/';
    } else {
        $base .= '/';
    }
    
    define('BASE_URL', $base);
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
