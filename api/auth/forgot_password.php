<?php
// ============================================================
// api/auth/forgot_password.php — Request a password reset link
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
    'email' => 'required|email',
]);

if ($v->fails()) {
    Response::error('Validation failed.', 422, $v->errors());
}

$email = strtolower(trim($v->get('email')));
$pdo = getDBConnection();

// Create table if it doesn't exist (safety measure for local dev)
$pdo->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(150)      NOT NULL,
    `token`      VARCHAR(64)       NOT NULL,
    `expires_at` DATETIME          NOT NULL,
    `created_at` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    INDEX idx_password_resets_email (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Check if user exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // For security, do not reveal whether the email exists. 
    // In a real app, just return success without sending an email.
    // However, since we are returning the link directly in the UI for the demo,
    // we'll explicitly error out so the developer knows.
    Response::error('No account found with that email address.', 404);
}

// Generate token
$token = bin2hex(random_bytes(32));

// Store token using MySQL's time to avoid PHP/MySQL timezone mismatches
$stmt = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
$stmt->execute([$email, $token]);

// Generate demo reset link (pointing back to index.html with the token)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname(dirname(dirname($_SERVER['REQUEST_URI'])));
$resetLink = rtrim("$protocol://$host$basePath", '/') . "/index.html?reset_token=" . $token;

// Return the link in the response since we have no SMTP server
Response::success([
    'reset_link' => $resetLink
], 'Reset link generated successfully. (Demo mode: link provided below)');
