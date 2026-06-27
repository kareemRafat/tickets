<?php
/**
 * Bootstrap — loads all shared dependencies for every page.
 * Individual pages only need: require_once __DIR__ . '/bootstrap.php';
 * Add audit.php separately on CRUD pages: require_once __DIR__ . '/helpers/audit.php';
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/security.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/rate_limiter.php';
