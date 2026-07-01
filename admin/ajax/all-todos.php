<?php
require_once __DIR__ . '/../../bootstrap.php';

set_security_headers();
require_admin();

header('Content-Type: application/json; charset=utf-8');

$db = getDBConnection();

$date_filter = $_GET['date'] ?? '';
$date_filter = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_filter) ? $date_filter : '';
$assigned_to = isset($_GET['assigned_to']) ? (int)$_GET['assigned_to'] : 0;
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $per_page;

$where_sql = "";
$params = [];

$conditions = [];
if ($date_filter !== '') {
    $conditions[] = "t.due_date = :date";
    $params['date'] = $date_filter;
}
if ($assigned_to > 0) {
    $conditions[] = "t.assigned_to = :assigned_to";
    $params['assigned_to'] = $assigned_to;
}
if ($status_filter === 'pending' || $status_filter === 'done') {
    $conditions[] = "t.status = :status";
    $params['status'] = $status_filter;
}
if ($search !== '') {
    $conditions[] = "t.title LIKE :search";
    $params['search'] = '%' . $search . '%';
}
if (!empty($conditions)) {
    $where_sql = " AND " . implode(" AND ", $conditions);
}

// Get total count
$count_sql = "
    SELECT COUNT(*)
    FROM employee_todos t
    WHERE 1=1 $where_sql
";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$total_pages = max(1, (int)ceil($total / $per_page));

// Fetch page
$data_sql = "
    SELECT t.*,
           a.name as assigned_by_name,
           e.name as assigned_to_name
    FROM employee_todos t
    JOIN employees a ON t.assigned_by = a.id
    JOIN employees e ON t.assigned_to = e.id
    WHERE 1=1 $where_sql
    ORDER BY t.status ASC, t.due_date ASC, t.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($data_sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$todos = [];
foreach ($rows as $r) {
    $todos[] = [
        'id'               => (int)$r['id'],
        'title'            => xss_clean($r['title']),
        'due_date'         => $r['due_date'],
        'status'           => $r['status'],
        'assigned_to_id'   => (int)$r['assigned_to'],
        'assigned_to_name' => xss_clean($r['assigned_to_name']),
        'assigned_by_id'   => (int)$r['assigned_by'],
        'assigned_by_name' => xss_clean($r['assigned_by_name']),
        'completed_at'     => $r['completed_at'],
        'created_at'       => $r['created_at'],
    ];
}

echo json_encode([
    'success'      => true,
    'data'         => $todos,
    'total'        => $total,
    'total_pages'  => $total_pages,
    'current_page' => $page,
    'per_page'     => $per_page,
], JSON_UNESCAPED_UNICODE);
