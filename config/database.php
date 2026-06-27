<?php
/**
 * Database connection manager
 * Returns a configured PDO instance.
 */

require_once __DIR__ . '/config.php';

// Database configuration settings
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'tickets');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

/**
 * Establishes and returns a PDO database connection.
 * Uses a static variable to implement the Singleton pattern for the connection instance.
 * 
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log connection error
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Display error if in development mode, otherwise show user-friendly message
            if (ini_get('display_errors') == 1) {
                throw new PDOException("Connection failed: " . $e->getMessage(), (int)$e->getCode());
            } else {
                die("A database connection error occurred. Please contact the administrator.");
            }
        }
    }
    
    return $pdo;
}
