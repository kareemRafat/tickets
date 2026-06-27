<?php
/**
 * System Audit Logging Helper
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Logs an administrative or support action to the audit_logs table.
 * 
 * @param string $action Description of action performed (e.g., 'إضافة موظف جديد')
 * @param string|null $table_name Target table affected (e.g., 'employees')
 * @param int|null $record_id Primary key ID of the affected record
 * @param array|null $old_values Associative array of state before changes
 * @param array|null $new_values Associative array of state after changes
 * @return void
 */
function log_audit_action($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Audit logs track employee/admin actions. If session doesn't contain user_id, ignore.
    $employee_id = $_SESSION['user_id'] ?? null;
    if (!$employee_id) {
        return;
    }
    
    try {
        $db = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $old_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
        $new_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (employee_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (:employee_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'employee_id' => $employee_id,
            'action' => $action,
            'table_name' => $table_name,
            'record_id' => $record_id,
            'old_values' => $old_json,
            'new_values' => $new_json,
            'ip_address' => $ip,
            'user_agent' => $ua
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log audit activity: " . $e->getMessage());
    }
}
