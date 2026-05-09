<?php
// ============================================================
// api/auth/update.php — Update user profile
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Validator.php';
require_once __DIR__ . '/../../includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed.', 405);
}

Auth::require();
$userId = Auth::id();
$data = Validator::json();

$v = Validator::make($data, [
    'username' => 'required|min:3',
    'email'    => 'required|email',
]);

if ($v->fails()) {
    Response::error('Validation failed.', 422, $v->errors());
}

$username = trim($v->get('username'));
$email    = strtolower(trim($v->get('email')));
$pdo      = getDBConnection();

// Check if email or username is taken by someone else
$stmt = $pdo->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?');
$stmt->execute([$email, $username, $userId]);
if ($stmt->fetch()) {
    Response::error('Username or email is already taken.', 409);
}

// Update the user
$updateStmt = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
if ($updateStmt->execute([$username, $email, $userId])) {
    // Fetch updated user to update session
    $fetchStmt = $pdo->prepare('SELECT id, username, email, is_admin FROM users WHERE id = ?');
    $fetchStmt->execute([$userId]);
    $updatedUser = $fetchStmt->fetch();
    
    Auth::login($updatedUser); // Refresh session

    Response::success($updatedUser, 'Profile updated successfully.');
} else {
    Response::error('Failed to update profile.', 500);
}
