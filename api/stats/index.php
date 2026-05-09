<?php
// ============================================================
// api/stats/index.php — User statistics
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::require();
$userId = Auth::id();
$pdo    = getDBConnection();

$stmt = $pdo->prepare('SELECT * FROM note_stats WHERE user_id = ?');
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Recent AI request history (last 5)
$history = $pdo->prepare(
    'SELECT model, total_tokens, status, created_at
     FROM ai_requests WHERE user_id = ?
     ORDER BY created_at DESC LIMIT 5'
);
$history->execute([$userId]);

Response::success([
    'stats'          => $stats ?: ['total_notes' => 0, 'total_words' => 0, 'total_tokens_used' => 0],
    'recent_requests'=> $history->fetchAll(),
]);
