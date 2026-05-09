<?php
// ============================================================
// api/auth/register.php — User registration
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
    'username' => 'required|string|min:3|max:50',
    'email'    => 'required|email|max:150',
    'password' => 'required|min:8|max:128',
]);

if ($v->fails()) {
    Response::error('Validation failed.', 422, $v->errors());
}

$username = trim($v->get('username'));
$email    = strtolower(trim($v->get('email')));
$password = $v->get('password');

$pdo = getDBConnection();

// Check duplicates
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    Response::error('An account with this email or username already exists.', 409);
}

// Create user
$hash = Auth::hashPassword($password);
$stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
$stmt->execute([$username, $email, $hash]);
$userId = (int) $pdo->lastInsertId();

// Create default category
$pdo->prepare('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)')
    ->execute([$userId, 'General', '#6366f1']);

// Auto-login
Auth::login(['id' => $userId, 'username' => $username, 'email' => $email]);

Response::success(
    ['id' => $userId, 'username' => $username, 'email' => $email],
    'Account created successfully.',
    201
);
