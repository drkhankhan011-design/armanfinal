<?php
/**
 * Dr. Arman Kabir Care - Application Configuration
 *
 * Security: Never commit this file to version control.
 * On cPanel, place this outside public_html or protect with .htaccess.
 *
 * NOTE: putenv() is disabled on this server. We read .env directly and
 * define constants. For maximum security, set real env vars via cPanel.
 */

// ─── Environment Detection ─────────────────────────────────────────────────
// First check OS-level environment variables (set via cPanel or .env file)
// Then fall back to .env file outside public_html
// Finally fail closed rather than shipping production defaults.

$envConfig = [];

// 1. Check OS environment variables first (set via cPanel or actual env)
$envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET', 'APP_URL'];
foreach ($envVars as $var) {
    $val = getenv($var);
    if ($val !== false && $val !== '') {
        $envConfig[$var] = $val;
    }
}

// 2. Load from .env file if OS env vars are not set
$dotenvFile = __DIR__ . '/../.env';
if (file_exists($dotenvFile)) {
    $lines = file($dotenvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            // Only set if not already defined from OS env vars
            if (!isset($envConfig[$key])) {
                $envConfig[$key] = $value;
            }
        }
    }
}

/**
 * Get configuration value: OS env > .env file > default
 */
function cfg(string $key, mixed $default = null): mixed {
    global $envConfig;
    return $envConfig[$key] ?? $default;
}

// Store globally so other files can access env config directly
$GLOBALS['env_config'] = $envConfig;

// ─── Error Reporting (set early to prevent warnings from leaking to output) ─
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

// ─── Database Configuration ────────────────────────────────────────────────
if (!defined('DB_HOST')) define('DB_HOST', cfg('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', cfg('DB_NAME', ''));
if (!defined('DB_USER')) define('DB_USER', cfg('DB_USER', ''));
if (!defined('DB_PASS')) define('DB_PASS', cfg('DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

if (empty(DB_NAME) || empty(DB_USER) || empty(DB_PASS)) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log('Missing database configuration. Set DB_HOST/DB_NAME/DB_USER/DB_PASS via cPanel environment or an external .env file.');
    die('Database configuration is missing. Please contact the administrator.');
}

// ─── Application Configuration ─────────────────────────────────────────────
if (!defined('APP_NAME')) define('APP_NAME', 'Dr. Arman Kabir Care');
if (!defined('APP_VERSION')) define('APP_VERSION', '2.0.0');
if (!defined('APP_URL')) define('APP_URL', cfg('APP_URL', 'https://drarmankabir.com'));
if (!defined('API_URL')) define('API_URL', APP_URL . '/api');

// ─── Security Configuration ────────────────────────────────────────────────
if (!defined('JWT_SECRET')) define('JWT_SECRET', cfg('JWT_SECRET', ''));
if (!defined('JWT_EXPIRY')) define('JWT_EXPIRY', 86400); // 24 hours in seconds
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 86400 * 7); // 7 days
if (!defined('CSRF_TOKEN_LIFETIME')) define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

if (empty(JWT_SECRET)) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log('Missing JWT_SECRET. Set JWT_SECRET in the server environment or external .env file.');
    die('JWT configuration is missing. Please contact the administrator.');
}

// ─── Upload Configuration ──────────────────────────────────────────────────
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/uploads');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', '/uploads');
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
if (!defined('ALLOWED_EXTENSIONS')) define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx']);

// ─── Rate Limiting ────────────────────────────────────────────────────────
if (!defined('RATE_LIMIT_MAX')) define('RATE_LIMIT_MAX', 100);
if (!defined('RATE_LIMIT_WINDOW')) define('RATE_LIMIT_WINDOW', 60); // seconds

// ─── Timezone ─────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Dhaka');
