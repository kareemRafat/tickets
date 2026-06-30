<?php
require_once __DIR__ . '/../../bootstrap.php';

set_security_headers();
require_employee_or_admin();

header('Content-Type: application/json; charset=utf-8');

$db = getDBConnection();
$user_id = (int)$_SESSION['user_id'];

try {
    // Count pending
    $countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM employee_todos WHERE assigned_to = :uid AND status = 'pending'");
    $countStmt->execute(['uid' => $user_id]);
    $pendingCount = (int)$countStmt->fetch()['cnt'];

    // Last 5 todos
    $listStmt = $db->prepare("
        SELECT t.id, t.title, t.status, t.due_date, t.created_at, a.name as assigned_by_name
        FROM employee_todos t
        JOIN employees a ON t.assigned_by = a.id
        WHERE t.assigned_to = :uid
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $listStmt->execute(['uid' => $user_id]);
    $todos = $listStmt->fetchAll();

    $items = array_map(function ($r) {
        return [
            'id'               => (int)$r['id'],
            'title'            => htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'),
            'status'           => $r['status'],
            'due_date'         => $r['due_date'],
            'assigned_by_name' => htmlspecialchars($r['assigned_by_name'], ENT_QUOTES, 'UTF-8'),
        ];
    }, $todos);

    echo json_encode([
        'success'       => true,
        'pending_count' => $pendingCount,
        'todos'         => $items,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error'], JSON_UNESCAPED_UNICODE);
    error_log('dashboard-todos error: ' . $e->getMessage());
}
