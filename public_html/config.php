<?php
/**
 * Dr. Arman Kabir Care - Application Configuration
 *
 * cPanel-safe configuration loader.
 *
 * IMPORTANT:
 * - Do not call putenv() on cPanel; it is often disabled.
 * - Use OS environment variables exposed by cPanel or a local .env file inside
 *   the application root.
 * - Do not rely on /tmp or other arbitrary paths; cPanel disables access via
 *   open_basedir and PHP execution sandboxing.
 */

// ─── Environment Detection ─────────────────────────────────────────────────
// Prefer values already exposed by the host (cPanel / Apache / PHP-FPM).
// Fall back to a .env file located at the application root.
// Final fallback is safe defaults.

$envConfig = [];

function parseEnvFile(string $path): array
{
    $values = [];
    if (!is_file($path) || !is_readable($path)) {
        return $values;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '') {
            $values[$key] = $value;
        }
    }

    return $values;
}

function cfg(string $key, mixed $default = null): mixed
{
    global $envConfig;

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    if (is_string($value) && $value !== '') {
        return $value;
    }

    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    if (array_key_exists($key, $envConfig)) {
        return $envConfig[$key];
    }

    return $default;
}

$appRoot = dirname(__DIR__);
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$baseCandidates = [];

if ($docRoot !== '') {
    $baseCandidates[] = rtrim($docRoot, '/');
    $baseCandidates[] = dirname(rtrim($docRoot, '/'));
}

$baseCandidates[] = $appRoot;
$baseCandidates[] = dirname($appRoot);

$seen = [];
foreach ($baseCandidates as $basePath) {
    if ($basePath === '' || in_array($basePath, $seen, true)) {
        continue;
    }
    $seen[] = $basePath;

    $candidate = rtrim($basePath, '/') . '/.env';
    $realPath = @realpath($candidate);
    if ($realPath !== false) {
        $envConfig = array_merge(parseEnvFile($realPath), $envConfig);
    }
}

// Keep the env config available elsewhere in the app.
$GLOBALS['env_config'] = $envConfig;

// ─── Error Reporting (set early to prevent warnings from leaking to output) ─
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$logDir = $appRoot . '/logs';
if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
    $logDir = sys_get_temp_dir();
}
ini_set('error_log', $logDir . '/php-error.log');

// ─── Database Configuration ────────────────────────────────────────────────
if (!defined('DB_HOST')) define('DB_HOST', cfg('DB_HOST', '127.0.0.1'));
if (!defined('DB_NAME')) define('DB_NAME', cfg('DB_NAME', 'drarmank_drarmank_care'));
if (!defined('DB_USER')) define('DB_USER', cfg('DB_USER', 'drarmank_drarmank_care_user'));
if (!defined('DB_PASS')) define('DB_PASS', cfg('DB_PASS', 'change-this-password'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ─── Application Configuration ─────────────────────────────────────────────
if (!defined('APP_NAME')) define('APP_NAME', 'Dr. Arman Kabir Care');
if (!defined('APP_VERSION')) define('APP_VERSION', '2.0.0');
if (!defined('APP_URL')) define('APP_URL', cfg('APP_URL', 'https://drarmankabir.com'));
if (!defined('API_URL')) define('API_URL', APP_URL . '/api');

// ─── Security Configuration ────────────────────────────────────────────────
if (!defined('JWT_SECRET')) define('JWT_SECRET', cfg('JWT_SECRET', 'change-this-to-a-random-secret-in-production'));
if (!defined('JWT_EXPIRY')) define('JWT_EXPIRY', 86400);
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 86400 * 7);
if (!defined('CSRF_TOKEN_LIFETIME')) define('CSRF_TOKEN_LIFETIME', 3600);

// ─── Upload Configuration ──────────────────────────────────────────────────
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', '/uploads');
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);
if (!defined('ALLOWED_EXTENSIONS')) define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx']);

// ─── Rate Limiting ────────────────────────────────────────────────────────
if (!defined('RATE_LIMIT_MAX')) define('RATE_LIMIT_MAX', 100);
if (!defined('RATE_LIMIT_WINDOW')) define('RATE_LIMIT_WINDOW', 60);

// ─── Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Dhaka');
