<?php
require_once __DIR__ . '/../../bootstrap.php';

set_security_headers();
require_student();

header('Content-Type: application/json; charset=utf-8');

$db = getDBConnection();
$national_id = $_SESSION['student_national_id'];
$ticket_id = (int)($_GET['id'] ?? 0);

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'رابط غير صالح.']);
    exit();
}

$stmt = $db->prepare("
    SELECT st.*, c.name as category_name
    FROM student_tickets st
    JOIN categories c ON st.category_id = c.id
    WHERE st.id = :id
");
$stmt->execute(['id' => $ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket || $ticket['national_id'] !== $national_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'لا يمكنك الوصول إلى هذه التذكرة.']);
    exit();
}

$replies = [];
try {
    $stmt = $db->prepare("
        SELECT r.*, e.name as employee_name
        FROM student_ticket_replies r
        JOIN employees e ON r.employee_id = e.id
        WHERE r.ticket_id = :ticket_id
        ORDER BY r.created_at ASC
    ");
    $stmt->execute(['ticket_id' => $ticket_id]);
    $replies = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch student replies: " . $e->getMessage());
}

$status_labels = ['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'closed' => 'مغلقة'];
$priority_labels = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'];

$response = [
    'success' => true,
    'ticket' => [
        'id'                => (int)$ticket['id'],
        'ticket_number'     => xss_clean($ticket['ticket_number']),
        'subject'           => xss_clean($ticket['subject']),
        'description'       => xss_clean($ticket['description']),
        'status'            => $ticket['status'],
        'status_label'      => $status_labels[$ticket['status']] ?? $ticket['status'],
        'priority'          => $ticket['priority'],
        'priority_label'    => $priority_labels[$ticket['priority']] ?? $ticket['priority'],
        'category_name'     => xss_clean($ticket['category_name']),
        'student_name'      => xss_clean($ticket['student_name']),
        'contact_phone'     => xss_clean($ticket['contact_phone']),
        'created_at'        => $ticket['created_at'],
        'closed_at'         => $ticket['closed_at'],
    ],
    'replies' => [],
    'status_labels' => $status_labels,
    'priority_labels' => $priority_labels,
];

foreach ($replies as $reply) {
    $response['replies'][] = [
        'employee_name' => xss_clean($reply['employee_name']),
        'reply'         => xss_clean($reply['reply']),
        'old_status'    => $reply['old_status'],
        'new_status'    => $reply['new_status'],
        'created_at'    => $reply['created_at'],
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
