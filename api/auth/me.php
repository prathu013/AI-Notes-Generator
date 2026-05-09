<?php
// ============================================================
// api/auth/me.php — Return current authenticated user
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::require();
Response::success(Auth::user());
