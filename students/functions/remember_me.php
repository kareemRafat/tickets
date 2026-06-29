<?php

require_once __DIR__ . '/../../functions/remember_me.php';

function handle_student_remember_login($studentId) {
    create_remember_token($studentId, 'student');
}

function process_student_remember_login() {
    $tokenData = validate_remember_token();
    if (!$tokenData || $tokenData['role'] !== 'student') {
        return false;
    }

    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM all_students WHERE id = :id");
    $stmt->execute(['id' => $tokenData['user_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        clear_remember_cookie();
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['student_id'] = $student['id'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_national_id'] = $student['national_id'];
    $_SESSION['student_branch_id'] = $student['branch_id'];
    $_SESSION['student_email'] = $student['email'];
    $_SESSION['student_phone'] = $student['phone'];
    $_SESSION['student_code'] = $student['student_code'];
    $_SESSION['user_role'] = 'student';
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return true;
}
