<?php
/**
 * API Health Check & Info
 *
 * GET /api/info.php
 *
 * Returns application status, environment info, and database status.
 * This endpoint is intentionally public so deployment monitoring and cPanel
 * health checks can confirm the app is alive even before a user session exists.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

handleCors();
requireMethod('GET');

$authUser = null;
if (function_exists('getAuthUser')) {
    $authUser = getAuthUser();
}

try {
    $db = Database::getInstance();
    $db->query('SELECT 1');
    $dbStatus = ['connected' => true, 'message' => 'OK'];
} catch (Throwable $e) {
    $dbStatus = ['connected' => false, 'message' => $e->getMessage()];
}

$tables = [];
try {
    $db = Database::getInstance();
    $result = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($result as $table) {
        $count = $db->query('SELECT COUNT(*) as c FROM `' . str_replace('`', '``', $table) . '`')->fetch();
        $tables[$table] = (int)($count['c'] ?? 0);
    }
} catch (Throwable $e) {
    // Tables may not exist yet; keep the endpoint usable for deployment checks.
}

$info = [
    'application' => defined('APP_NAME') ? APP_NAME : 'Dr. Arman Kabir Care',
    'version' => defined('APP_VERSION') ? APP_VERSION : '2.0.0',
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database' => $dbStatus,
    'tables' => $tables,
    'timezone' => date_default_timezone_get(),
    'server_time' => date('Y-m-d H:i:s'),
    'authenticated' => !empty($authUser),
    'user' => $authUser ? [
        'id' => $authUser['id'] ?? null,
        'role' => $authUser['role'] ?? null,
        'email' => $authUser['email'] ?? null,
    ] : null,
    'endpoints' => [
        'auth' => ['login', 'logout', 'verify'],
        'patients' => ['list', 'get', 'create', 'update'],
        'visits' => ['list', 'create'],
        'prescriptions' => ['list', 'create'],
        'appointments' => ['list', 'create', 'update'],
        'vitals' => ['create'],
        'payments' => ['list', 'create'],
        'clinical' => ['notes-list', 'notes-create'],
        'investigations' => ['list'],
        'staff' => ['list'],
        'settings' => ['get'],
        'audit' => ['list'],
        'upload' => ['index'],
    ],
];

successResponse($info, 'API is running');
