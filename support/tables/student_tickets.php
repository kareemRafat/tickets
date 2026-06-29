<?php
$pageParams = get_pagination_params(10);
$where = [];
$params = [];
if (!$is_admin) {
    $where[] = "st.branch_id = :branch_id";
    $params['branch_id'] = $branch_id;
}

if (!empty($status_filter)) {
    $where[] = "st.status = :status";
    $params['status'] = $status_filter;
}
if ($category_filter > 0) {
    $where[] = "st.category_id = :category";
    $params['category'] = $category_filter;
}
if (!empty($priority_filter)) {
    $where[] = "st.priority = :priority";
    $params['priority'] = $priority_filter;
}
if (!empty($search)) {
    $where[] = "(st.ticket_number LIKE :search OR st.subject LIKE :search_subject OR st.student_name LIKE :search_name OR st.national_id LIKE :search_nid OR st.contact_phone LIKE :search_phone)";
    $params['search'] = '%' . $search . '%';
    $params['search_subject'] = '%' . $search . '%';
    $params['search_name'] = '%' . $search . '%';
    $params['search_nid'] = '%' . $search . '%';
    $params['search_phone'] = '%' . $search . '%';
}
if (!empty($from_date)) {
    $where[] = "st.created_at >= :from_date";
    $params['from_date'] = $from_date . ' 00:00:00';
}
if (!empty($to_date)) {
    $where[] = "st.created_at <= :to_date";
    $params['to_date'] = $to_date . ' 23:59:59';
}

$where_clause = $where ? implode(' AND ', $where) : '1=1';
$tickets = [];
$totalStudentTickets = 0;
try {
    $countStmt = $db->prepare("
        SELECT COUNT(*)
        FROM student_tickets st
        JOIN categories c ON st.category_id = c.id
        WHERE {$where_clause}
    ");
    $countStmt->execute($params);
    $totalStudentTickets = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name, st.student_name, st.national_id, st.student_code, st.contact_phone,
            (SELECT name FROM employees WHERE id = st.last_reply_by) as last_reply_name
        FROM student_tickets st
        JOIN categories c ON st.category_id = c.id
        WHERE {$where_clause}
        ORDER BY FIELD(st.status, 'open', 'in_progress', 'closed'), COALESCE(st.last_reply_at, st.created_at) DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $pageParams['perPage'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pageParams['offset'], PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->execute();
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Student tickets query error: " . $e->getMessage());
}
?>

<div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-base text-right text-gray-500 dark:text-gray-400">
            <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th scope="col" class="px-3 py-4 w-10">#</th>
                    <th scope="col" class="px-4 py-4">رقم التذكرة</th>
                    <th scope="col" class="px-4 py-4">الموضوع / الطالب</th>
                    <th scope="col" class="px-4 py-4">التصنيف / الأولوية</th>
                    <th scope="col" class="px-4 py-4">الحالة</th>
                    <th scope="col" class="px-4 py-4">آخر رد</th>
                    <th scope="col" class="px-4 py-4">تاريخ الإنشاء</th>
                    <th scope="col" class="px-4 py-4 text-left">عرض</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            لا توجد شكاوى طلابية تطابق معايير البحث.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = $pageParams['offset'] + 1; ?>
                    <?php foreach ($tickets as $t): ?>
                        <?php
                        $row_bg = match($t['status']) {
                            'open' => 'bg-blue-200/50 hover:bg-blue-300/60 dark:bg-blue-900/25 dark:hover:bg-blue-900/40',
                            'in_progress' => 'bg-yellow-200/50 hover:bg-yellow-300/60 dark:bg-yellow-900/25 dark:hover:bg-yellow-900/40',
                            'closed' => 'bg-green-200/50 hover:bg-green-300/60 dark:bg-green-900/25 dark:hover:bg-green-900/40',
                            default => 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'
                        };
                        $badge_bg = match($t['status']) {
                            'open' => 'bg-blue-700 text-white dark:bg-blue-500 dark:text-white',
                            'in_progress' => 'bg-yellow-600 text-white dark:bg-yellow-500 dark:text-white',
                            'closed' => 'bg-green-700 text-white dark:bg-green-500 dark:text-white',
                            default => ''
                        };
                        ?>
                        <tr class="<?php echo $row_bg; ?> transition-colors">
                            <td class="px-3 py-3 text-sm text-gray-600 font-bold dark:text-gray-500 text-center"><?php echo $i++; ?></td>
                            <td class="px-4 py-3 font-mono text-base font-semibold"><a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $t['id']; ?>&type=student" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($t['ticket_number']); ?></a></td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-xs"><?php echo htmlspecialchars($t['subject']); ?></div>
                                <div class="text-xs dark:text-gray-400 mt-1.5 font-bold bg-teal-600 text-white rounded w-fit px-4 py-0.5"><?php echo htmlspecialchars($t['student_name']); ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($t['category_name']); ?></div>
                                <div class="mt-1">
                                    <?php if ($t['priority'] === 'high'): ?>
                                        <span class="px-2 text-xs font-bold rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">عالية</span>
                                    <?php elseif ($t['priority'] === 'medium'): ?>
                                        <span class="px-2 text-xs font-bold rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">متوسطة</span>
                                    <?php else: ?>
                                        <span class="px-2 text-xs font-bold rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">منخفضة</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-1.5 py-0.5 text-xs font-bold rounded inline-flex items-center gap-1 <?php echo $badge_bg; ?>">
                                    <?php if ($t['status'] === 'closed'): ?>
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5a3 3 0 016 0V7a1 1 0 102 0V5.5A4.5 4.5 0 0014.5 1z" clip-rule="evenodd"/></svg>
                                    <?php elseif ($t['status'] === 'in_progress'): ?>
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    <?php else: ?>
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.5 1A4.5 4.5 0 0010 5.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5a3 3 0 016 0V7a1 1 0 102 0V5.5A4.5 4.5 0 0014.5 1z" clip-rule="evenodd"/></svg>
                                    <?php endif; ?>
                                    <?php echo $status_labels[$t['status']]; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php if ($t['last_reply_at']): ?><span class="font-medium"><?php echo htmlspecialchars($t['last_reply_name']); ?></span><br><span class="text-gray-500"><?php echo date('Y-m-d', strtotime($t['last_reply_at'])); ?></span><?php else: ?><span class="text-gray-400">—</span><?php endif; ?></td>
                            <td class="px-4 py-3 text-sm font-bold"><?php echo date('Y-m-d', strtotime($t['created_at'])); ?></td>
                            <td class="px-4 py-3 text-left">
                                <a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $t['id']; ?>&type=student" class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-bold">عرض</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php render_pagination($totalStudentTickets, 10); ?>
</div>
