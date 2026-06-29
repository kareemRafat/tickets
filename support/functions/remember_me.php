<?php

require_once __DIR__ . '/../../functions/remember_me.php';

function handle_employee_remember_login($userId) {
    create_remember_token($userId, 'employee');
}

function process_employee_remember_login() {
    $tokenData = validate_remember_token();
    if (!$tokenData || $tokenData['role'] !== 'employee') {
        return false;
    }

    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = :id AND role = 'employee' AND status = 'active'");
    $stmt->execute(['id' => $tokenData['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        clear_remember_cookie();
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_branch_id'] = $user['branch_id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $update = $db->prepare("UPDATE employees SET last_login_at = NOW() WHERE id = :id");
    $update->execute(['id' => $user['id']]);

    return true;
}
