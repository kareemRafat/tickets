<?php

function get_pagination_params($perPage = 10) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;
    return ['page' => $page, 'offset' => $offset, 'perPage' => $perPage];
}

function get_pagination_query_string($page) {
    $params = $_GET;
    if ($page <= 1) {
        unset($params['page']);
    } else {
        $params['page'] = $page;
    }
    return http_build_query($params);
}

function render_pagination($totalRecords, $perPage = 10) {
    $totalPages = max(1, (int)ceil($totalRecords / $perPage));
    if ($totalPages <= 1) {
        return;
    }

    $current = max(1, (int)($_GET['page'] ?? 1));

    $range = 2;
    $start = max(1, $current - $range);
    $end = min($totalPages, $current + $range);
    ?>
    <nav class="flex flex-col md:flex-row items-center justify-between gap-4 px-4 py-3 border-t border-gray-100 dark:border-gray-700" aria-label="pagination">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
            صفحة <?php echo $current; ?> من <?php echo $totalPages; ?> (<?php echo $totalRecords; ?> سجل)
        </span>
        <ul class="inline-flex items-center gap-1 text-sm">
            <?php if ($current > 1): ?>
                <li>
                    <a href="?<?php echo get_pagination_query_string($current - 1); ?>" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        السابق
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($start > 1): ?>
                <li>
                    <a href="?<?php echo get_pagination_query_string(1); ?>" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">1</a>
                </li>
                <?php if ($start > 2): ?>
                    <li><span class="px-2 py-2 text-gray-400 dark:text-gray-500">...</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <li>
                    <?php if ($i === $current): ?>
                        <span class="flex items-center justify-center px-3 py-2 font-bold text-white bg-blue-600 border border-blue-600 rounded-lg cursor-default"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo get_pagination_query_string($i); ?>" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all"><?php echo $i; ?></a>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                    <li><span class="px-2 py-2 text-gray-400 dark:text-gray-500">...</span></li>
                <?php endif; ?>
                <li>
                    <a href="?<?php echo get_pagination_query_string($totalPages); ?>" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all"><?php echo $totalPages; ?></a>
                </li>
            <?php endif; ?>

            <?php if ($current < $totalPages): ?>
                <li>
                    <a href="?<?php echo get_pagination_query_string($current + 1); ?>" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">
                        التالي
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
}
