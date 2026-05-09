<?php
// ============================================================
// api/stats/dashboard.php — All dashboard data in one call
// Returns: user info, stats, recent notes, activity log
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed.', 405);
}

Auth::require();
$userId = Auth::id();
$user   = Auth::user();
$pdo    = getDBConnection();

// ── Stats ────────────────────────────────────────────────────
$statsStmt = $pdo->prepare('SELECT * FROM note_stats WHERE user_id = ?');
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch() ?: [
    'total_notes'       => 0,
    'pinned_notes'      => 0,
    'archived_notes'    => 0,
    'total_words'       => 0,
    'total_ai_requests' => 0,
    'total_tokens_used' => 0,
];

// ── Monthly usage (current calendar month) ──────────────────
$usageStmt = $pdo->prepare(
    'SELECT COUNT(*) AS monthly_count
     FROM ai_requests
     WHERE user_id = ?
     AND YEAR(created_at)  = YEAR(NOW())
     AND MONTH(created_at) = MONTH(NOW())
     AND status = "success"'
);
$usageStmt->execute([$userId]);
$monthly = (int) ($usageStmt->fetchColumn() ?: 0);

// ── Download count (future: pdf_exports table; for now = ai_requests) ──
$dlStmt = $pdo->prepare('SELECT COUNT(*) FROM ai_requests WHERE user_id = ? AND status = "success"');
$dlStmt->execute([$userId]);
$downloadCount = (int) ($dlStmt->fetchColumn() ?: 0);

// ── Total Q&A questions (key_points count approximation) ────
$qaStmt = $pdo->prepare(
    'SELECT COALESCE(SUM(JSON_LENGTH(ai_key_points)), 0)
     FROM notes WHERE user_id = ? AND ai_key_points IS NOT NULL'
);
$qaStmt->execute([$userId]);
$qaCount = (int) ($qaStmt->fetchColumn() ?: 0);

// ── Recent notes (last 10) ──────────────────────────────────
$notesStmt = $pdo->prepare(
    'SELECT n.id, n.title, n.ai_summary, n.ai_tags, n.ai_key_points,
            n.word_count, n.is_pinned, n.created_at, n.updated_at,
            c.name AS category_name, c.color AS category_color
     FROM notes n
     LEFT JOIN categories c ON c.id = n.category_id
     WHERE n.user_id = ? AND n.is_archived = 0
     ORDER BY n.is_pinned DESC, n.updated_at DESC
     LIMIT 10'
);
$notesStmt->execute([$userId]);
$recentNotes = $notesStmt->fetchAll();

foreach ($recentNotes as &$note) {
    $note['ai_tags']       = json_decode($note['ai_tags']       ?? '[]', true);
    $note['ai_key_points'] = json_decode($note['ai_key_points'] ?? '[]', true);
}
unset($note);

// ── Activity feed (last 8 AI requests) ──────────────────────
$actStmt = $pdo->prepare(
    'SELECT r.created_at, r.status, r.model, r.total_tokens, n.title AS note_title
     FROM ai_requests r
     LEFT JOIN notes n ON n.id = r.note_id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC
     LIMIT 8'
);
$actStmt->execute([$userId]);
$activity = $actStmt->fetchAll();

// ── Categories ───────────────────────────────────────────────
$catStmt = $pdo->prepare(
    'SELECT c.id, c.name, c.color, COUNT(n.id) AS note_count
     FROM categories c
     LEFT JOIN notes n ON n.category_id = c.id AND n.is_archived = 0
     WHERE c.user_id = ?
     GROUP BY c.id, c.name, c.color
     ORDER BY note_count DESC'
);
$catStmt->execute([$userId]);
$categories = $catStmt->fetchAll();

Response::success([
    'user'        => $user,
    'stats'       => [
        'total_notes'    => (int) $stats['total_notes'],
        'total_words'    => (int) $stats['total_words'],
        'downloads'      => $downloadCount,
        'questions'      => $qaCount,
        'monthly_usage'  => $monthly,
        'monthly_limit'  => 30,
        'pinned'         => (int) $stats['pinned_notes'],
        'ai_requests'    => (int) $stats['total_ai_requests'],
    ],
    'notes'       => $recentNotes,
    'activity'    => $activity,
    'categories'  => $categories,
]);
