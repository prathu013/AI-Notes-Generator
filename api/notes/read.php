<?php
// ============================================================
// api/notes/read.php — Get notes for authenticated user
// GET ?id=X          → single note
// GET ?search=term   → search notes
// GET ?archived=1    → archived notes
// GET (no params)    → all active notes
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
$pdo    = getDBConnection();

// ── Single note ───────────────────────────────────────────────
if (!empty($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $pdo->prepare(
        'SELECT n.*, c.name AS category_name, c.color AS category_color
         FROM notes n
         LEFT JOIN categories c ON c.id = n.category_id
         WHERE n.id = ? AND n.user_id = ?'
    );
    $stmt->execute([$id, $userId]);
    $note = $stmt->fetch();
    if (!$note) Response::notFound('Note not found.');

    $note['ai_key_points'] = json_decode($note['ai_key_points'] ?? '[]', true);
    $note['ai_tags']       = json_decode($note['ai_tags']       ?? '[]', true);
    Response::success($note);
}

// ── Build query ───────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$archived = (int) ($_GET['archived'] ?? 0);
$catId    = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

$params = [$userId, $archived];
$where  = 'n.user_id = ? AND n.is_archived = ?';

if ($search !== '') {
    $where   .= ' AND MATCH(n.title, n.raw_input, n.ai_summary) AGAINST(? IN BOOLEAN MODE)';
    $params[] = $search . '*';
}

if ($catId) {
    $where   .= ' AND n.category_id = ?';
    $params[] = $catId;
}

$sql = "SELECT n.id, n.title, n.ai_summary, n.ai_tags, n.word_count,
               n.is_pinned, n.is_archived, n.created_at, n.updated_at,
               c.name AS category_name, c.color AS category_color
        FROM notes n
        LEFT JOIN categories c ON c.id = n.category_id
        WHERE {$where}
        ORDER BY n.is_pinned DESC, n.updated_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

foreach ($notes as &$note) {
    $note['ai_tags'] = json_decode($note['ai_tags'] ?? '[]', true);
}
unset($note);

Response::success(['notes' => $notes, 'total' => count($notes)]);
