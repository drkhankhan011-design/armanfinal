<?php
/**
 * Login API
 *
 * POST /api/auth/login.php
 * Body: { "email": "...", "password": "..." }
 *
 * Validates credentials and creates a session.
 * Sets a secure HttpOnly cookie only when the request is actually HTTPS.
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/middleware.php';

handleCors();
requireMethod('POST');
checkRateLimit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 900); // 5 attempts per 15 minutes

$input = getJsonInput();

// Validate required fields
$missing = validateRequired($input, ['email', 'password']);
if ($missing) {
    errorResponse('Missing required fields', 400, [
        'missing_fields' => $missing,
    ]);
}

$email = sanitizeEmail($input['email']);
$password = $input['password'];

if (empty($email)) {
    errorResponse('Invalid email address', 400);
}

try {
    $db = Database::getInstance();

    // Find user by email (only fetch needed columns)
    $stmt = $db->prepare('SELECT id, email, password_hash, full_name, name_bn, role, specialization, phone, photo_url, signature_url, bmdc_registration, is_active, registration_status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        logAudit(null, null, 'failed_login', 'user', null, null, ['email' => $email, 'reason' => 'user_not_found']);
        errorResponse('Invalid email or password', 401);
    }

    // Linear validation pipeline
    if (!$user['is_active']) {
        logAudit($user['id'], null, 'failed_login', 'user', $user['id'], null, ['reason' => 'account_deactivated']);
        errorResponse('Account is deactivated. Contact administrator.', 403);
    }

    if ($user['registration_status'] === 'pending') {
        logAudit($user['id'], null, 'failed_login', 'user', $user['id'], null, ['reason' => 'pending_approval']);
        errorResponse('Your account is pending admin approval. Please wait.', 403);
    }

    if ($user['registration_status'] === 'rejected') {
        logAudit($user['id'], null, 'failed_login', 'user', $user['id'], null, ['reason' => 'account_rejected']);
        errorResponse('Your account has been rejected. Please contact the admin or re-register.', 403);
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        logAudit($user['id'], null, 'failed_login', 'user', $user['id'], null, ['reason' => 'invalid_password']);
        errorResponse('Invalid email or password', 401);
    }

    // Regenerate PHP session ID to prevent session fixation
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    // Create session in database
    $token = createSession($user['id']);

    // Set secure HttpOnly cookie only when running over HTTPS.
    $secureCookie = isSecureCookieRequest();
    $cookieParams = session_get_cookie_params();
    setcookie('session_token', $token, [
        'expires' => time() + SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Update last login
    $updateStmt = $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $updateStmt->execute([':id' => $user['id']]);

    // Log successful login
    logAudit($user['id'], null, 'login', 'user', $user['id']);

    // Return user info (excluding password hash)
    unset($user['password_hash']);

    successResponse([
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'name_bn' => $user['name_bn'],
            'role' => $user['role'],
            'specialization' => $user['specialization'],
            'phone' => $user['phone'],
            'photo_url' => $user['photo_url'],
            'signature_url' => $user['signature_url'],
            'bmdc_registration' => $user['bmdc_registration'],
        ],
    ], 'Login successful');

} catch (\Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    errorResponse('Login failed. Please try again.', 500);
}
