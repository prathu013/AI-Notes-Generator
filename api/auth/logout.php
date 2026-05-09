<?php
// ============================================================
// api/auth/logout.php — Destroy session
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::logout();
Response::success(null, 'Logged out successfully.');
