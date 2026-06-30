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
        if (isset($_SESSION['student_national_id'])) {
            header('Location: ' . BASE_URL . 'students/index.php');
            exit();
        }
        if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'employee') {
            header('Location: ' . BASE_URL . 'support/index.php');
            exit();
        }
        $_SESSION['error'] = 'عذراً، يجب تسجيل الدخول كمدير نظام للوصول إلى هذه الصفحة.';
        header('Location: ' . BASE_URL . 'admin/auth/login.php');
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
        if (isset($_SESSION['student_national_id'])) {
            header('Location: ' . BASE_URL . 'students/index.php');
            exit();
        }
        if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
            header('Location: ' . BASE_URL . 'admin/index.php');
            exit();
        }
        $_SESSION['error'] = 'عذراً، يجب تسجيل الدخول كموظف دعم للوصول إلى هذه الصفحة.';
        header('Location: ' . BASE_URL . 'support/auth/login.php');
        exit();
    }
}

/**
 * Ensures the user is logged in as either an Employee or Admin.
 */
function require_employee_or_admin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['employee', 'admin'])) {
        if (isset($_SESSION['student_national_id'])) {
            header('Location: ' . BASE_URL . 'students/index.php');
            exit();
        }
        $_SESSION['error'] = 'عذراً، يجب تسجيل الدخول للوصول إلى هذه الصفحة.';
        header('Location: ' . BASE_URL . 'admin/auth/login.php');
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
        if (isset($_SESSION['user_id'])) {
            $role = $_SESSION['user_role'] ?? '';
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . 'admin/index.php');
                exit();
            } elseif ($role === 'employee') {
                header('Location: ' . BASE_URL . 'support/index.php');
                exit();
            }
        }
        $_SESSION['error'] = 'يرجى إدخال الرقم القومي للتحقق والوصول إلى بوابة الطلاب.';
        header('Location: ' . BASE_URL . 'students/auth/login.php');
        exit();
    }
}
