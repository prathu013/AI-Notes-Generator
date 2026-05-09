<?php
// ============================================================
// config/cors.php — CORS & JSON headers
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Allow same-origin requests (tighten in production)
$allowed = env('APP_URL', 'http://localhost');
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin === $allowed || APP_ENV === 'development') {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
