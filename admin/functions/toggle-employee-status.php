<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/audit.php';

set_security_headers();
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صالحة.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$id = (int)($input['id'] ?? 0);
$status = $input['status'] ?? '';
$csrf_token = $input['csrf_token'] ?? '';

if (!verify_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة.']);
    exit();
}

if ($id <= 0 || !in_array($status, ['active', 'inactive'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit();
}

if ($id === (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'لا يمكنك تغيير حالة حسابك الحالي.']);
    exit();
}

try {
    $db = getDBConnection();

    $stmt = $db->prepare("SELECT * FROM employees WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $employee = $stmt->fetch();

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الموظف غير موجود.']);
        exit();
    }

    $stmt = $db->prepare("UPDATE employees SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $status, 'id' => $id]);

    $status_ar = $status === 'active' ? 'تفعيل' : 'تعطيل';
    log_audit_action("{$status_ar} حساب الموظف: {$employee['name']} (اسم المستخدم: {$employee['username']})", 'employees', $id, ['status' => $employee['status']], ['status' => $status]);

    echo json_encode(['success' => true, 'message' => "تم {$status_ar} حساب الموظف بنجاح."]);
} catch (PDOException $e) {
    error_log("Toggle employee status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات.']);
}
