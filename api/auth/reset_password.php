<?php
// ============================================================
// api/auth/reset_password.php — Reset password using a token
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$data = Validator::json();

$v = Validator::make($data, [
    'token'    => 'required|string',
    'password' => 'required|min:8',
]);

if ($v->fails()) {
    Response::error('Validation failed.', 422, $v->errors());
}

$token    = $v->get('token');
$password = $v->get('password');

$pdo = getDBConnection();

// Check if token exists and is not expired
$stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()');
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    Response::error('Invalid or expired password reset token.', 400);
}

$email = $reset['email'];

// Update the user's password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
$updateStmt->execute([$hashedPassword, $email]);

// Delete the token so it cannot be reused
$deleteStmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
$deleteStmt->execute([$email]);

Response::success(null, 'Password has been reset successfully. You can now sign in.');
