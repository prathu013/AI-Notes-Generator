<?php
// ============================================================
// api/auth/login.php — User login
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validator.php';
require_once __DIR__ . '/../../includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$data = Validator::json();

$v = Validator::make($data, [
    'email'    => 'required|email',
    'password' => 'required',
]);

if ($v->fails()) {
    Response::error('Validation failed.', 422, $v->errors());
}

$email    = strtolower(trim($v->get('email')));
$password = $v->get('password');

$pdo  = getDBConnection();
$stmt = $pdo->prepare('SELECT id, username, email, password, is_admin, status FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !Auth::verifyPassword($password, $user['password'])) {
    // Generic message to prevent user enumeration
    Response::error('Invalid email or password.', 401);
}

if ($user['status'] === 'disabled') {
    Response::error('Your account has been disabled. Please contact support.', 403);
}

Auth::login($user);

Response::success(
    ['id' => $user['id'], 'username' => $user['username'], 'email' => $user['email'], 'is_admin' => (bool)$user['is_admin']],
    'Login successful.'
);
