<?php
/**
 * Rate Limiter and Security Headers Middleware
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sets secure HTTP headers on the response to safeguard against common attacks.
 * 
 * @return void
 */
function set_security_headers() {
    if (!headers_sent()) {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Content Security Policy permitting local resources and Google Fonts
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none';");
    }
}

/**
 * Checks if a login attempt is permitted based on recent failure history.
 * Blocks logins if there are 5 or more failed attempts in the last 15 minutes.
 * 
 * @param string $username The attempted username/email.
 * @return bool True if allowed, false if locked out.
 */
function is_login_allowed($username) {
    try {
        $db = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Count failed attempts from this IP or username in the last 15 minutes
        $stmt = $db->prepare("
            SELECT COUNT(*) as failures 
            FROM login_attempts 
            WHERE (ip_address = :ip OR username = :username) 
              AND is_success = 0 
              AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        
        $stmt->execute([
            'ip' => $ip,
            'username' => $username
        ]);
        
        $row = $stmt->fetch();
        $failures = (int)($row['failures'] ?? 0);
        
        return $failures < 5;
    } catch (PDOException $e) {
        // Fallback to true if database is unavailable to prevent lockouts, but log error
        error_log("Rate limiter database error: " . $e->getMessage());
        return true;
    }
}

/**
 * Logs a login attempt to the database for rate limiting tracking.
 * 
 * @param string $username The attempted username/email.
 * @param bool $is_success True if the login succeeded, false otherwise.
 * @return void
 */
function log_login_attempt($username, $is_success) {
    try {
        $db = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $db->prepare("
            INSERT INTO login_attempts (username, ip_address, is_success) 
            VALUES (:username, :ip, :is_success)
        ");
        
        $stmt->execute([
            'username' => $username,
            'ip' => $ip,
            'is_success' => $is_success ? 1 : 0
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}
