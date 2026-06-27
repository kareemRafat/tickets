<?php
/**
 * Security utilities manager
 * Handles CSRF tokens, XSS protection, and password hashing helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates a CSRF token and saves it in the session if not exists.
 * 
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies if the provided token matches the CSRF token in the session.
 * 
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitizes inputs recursively to prevent XSS (cross-site scripting) attacks.
 * 
 * @param mixed $data
 * @return mixed
 */
function xss_clean($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = xss_clean($value);
        }
    } else {
        $data = htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Hashes passwords securely using default php algorithm.
 * 
 * @param string $password
 * @return string
 */
function security_hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifies password against bcrypt hash.
 * 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function security_verify_password($password, $hash) {
    return password_verify($password, $hash);
}
