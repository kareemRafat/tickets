<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../helpers/audit.php';

set_security_headers();
require_employee_or_admin();

header('Content-Type: application/json; charset=utf-8');

$db = getDBConnection();
$user_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'list':
        $date_filter = $_GET['date'] ?? date('Y-m-d');
        $date_filter = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter) ? $date_filter : date('Y-m-d');

        $stmt = $db->prepare("
            SELECT t.*, a.name as assigned_by_name
            FROM employee_todos t
            JOIN employees a ON t.assigned_by = a.id
            WHERE t.assigned_to = :user_id
            AND t.due_date = :due_date
            ORDER BY t.status ASC, t.created_at DESC
        ");
        $stmt->execute(['user_id' => $user_id, 'due_date' => $date_filter]);
        $rows = $stmt->fetchAll();

        $pending = [];
        $done = [];
        foreach ($rows as $r) {
            $item = [
                'id'               => (int)$r['id'],
                'title'            => xss_clean($r['title']),
                'due_date'         => $r['due_date'],
                'status'           => $r['status'],
                'assigned_by_id'   => (int)$r['assigned_by'],
                'assigned_by_name' => xss_clean($r['assigned_by_name']),
                'completed_at'     => $r['completed_at'],
                'created_at'       => $r['created_at'],
            ];
            if ($r['status'] === 'done') {
                $done[] = $item;
            } else {
                $pending[] = $item;
            }
        }

        echo json_encode(['success' => true, 'data' => ['pending' => $pending, 'done' => $done]], JSON_UNESCAPED_UNICODE);
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $due_date = trim($_POST['due_date'] ?? '');

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'عنوان المهمة مطلوب.']);
            exit;
        }

        if ($assigned_to <= 0) {
            echo json_encode(['success' => false, 'message' => 'يرجى اختيار الموظف.']);
            exit;
        }

        $check = $db->prepare("SELECT id FROM employees WHERE id = :id AND status = 'active'");
        $check->execute(['id' => $assigned_to]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'الموظف المحدد غير موجود.']);
            exit;
        }

        $due_date_val = !empty($due_date) ? $due_date : null;

        $stmt = $db->prepare("
            INSERT INTO employee_todos (assigned_by, assigned_to, title, due_date)
            VALUES (:assigned_by, :assigned_to, :title, :due_date)
        ");
        $stmt->execute([
            'assigned_by' => $user_id,
            'assigned_to' => $assigned_to,
            'title'       => $title,
            'due_date'    => $due_date_val,
        ]);

        $todo_id = (int)$db->lastInsertId();
        log_audit_action('إنشاء مهمة جديدة: ' . $title, 'employee_todos', $todo_id, null, ['title' => $title, 'assigned_to' => $assigned_to]);

        echo json_encode(['success' => true, 'message' => 'تم إنشاء المهمة بنجاح.'], JSON_UNESCAPED_UNICODE);
        break;

    case 'toggle':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $todo_id = (int)($_POST['id'] ?? 0);
        if ($todo_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرّف المهمة غير صالح.']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM employee_todos WHERE id = :id");
        $stmt->execute(['id' => $todo_id]);
        $todo = $stmt->fetch();

        if (!$todo) {
            echo json_encode(['success' => false, 'message' => 'المهمة غير موجودة.']);
            exit;
        }

        if ((int)$todo['assigned_to'] !== $user_id) {
            echo json_encode(['success' => false, 'message' => 'يمكن فقط للمسند إليه المهمة تغيير حالتها.']);
            exit;
        }

        $new_status = $todo['status'] === 'pending' ? 'done' : 'pending';
        $completed_at = $new_status === 'done' ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("UPDATE employee_todos SET status = :status, completed_at = :completed_at WHERE id = :id");
        $stmt->execute(['status' => $new_status, 'completed_at' => $completed_at, 'id' => $todo_id]);

        log_audit_action(
            ($new_status === 'done' ? 'إتمام مهمة' : 'إعادة فتح مهمة') . ': ' . $todo['title'],
            'employee_todos', $todo_id,
            ['status' => $todo['status']],
            ['status' => $new_status]
        );

        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة المهمة.'], JSON_UNESCAPED_UNICODE);
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $todo_id = (int)($_POST['id'] ?? 0);
        if ($todo_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرّف المهمة غير صالح.']);
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM employee_todos WHERE id = :id");
        $stmt->execute(['id' => $todo_id]);
        $todo = $stmt->fetch();

        if (!$todo) {
            echo json_encode(['success' => false, 'message' => 'المهمة غير موجودة.']);
            exit;
        }

        $is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
        if ((int)$todo['assigned_by'] !== $user_id && !$is_admin) {
            echo json_encode(['success' => false, 'message' => 'يمكن فقط لمنشئ المهمة أو المدير حذفها.']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM employee_todos WHERE id = :id");
        $stmt->execute(['id' => $todo_id]);

        log_audit_action('حذف مهمة: ' . $todo['title'], 'employee_todos', $todo_id, ['title' => $todo['title']], null);

        echo json_encode(['success' => true, 'message' => 'تم حذف المهمة.'], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف.']);
        break;
}
