<?php
/**
 * Authentication Middleware Gatekeeper
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Ensures the user is logged in as an Admin.
 * If not, redirects to the admin login page with an error.
 */
function require_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        $_SESSION['error'] = 'عذراً، يجب تسجيل الدخول كمدير نظام للوصول إلى هذه الصفحة.';
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit();
    }
}

/**
 * Ensures the user is logged in as an Employee (Support).
 * If not, redirects to the support login page with an error.
 */
function require_employee() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'employee') {
        $_SESSION['error'] = 'عذراً، يجب تسجيل الدخول كموظف دعم للوصول إلى هذه الصفحة.';
        header('Location: ' . BASE_URL . 'support/login.php');
        exit();
    }
}

/**
 * Ensures the student session has a verified National ID.
 * If not, redirects to the student landing page.
 */
function require_student() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['student_national_id'])) {
        $_SESSION['error'] = 'يرجى إدخال الرقم القومي للتحقق والوصول إلى بوابة الطلاب.';
        header('Location: ' . BASE_URL . 'students/index.php');
        exit();
    }
}
