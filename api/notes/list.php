<?php
// ============================================================
// api/notes/list.php — Paginated notes list for My Notes view
// GET ?search=&page=1&limit=20&category_id=&pinned=1
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

$search   = trim($_GET['search']      ?? '');
$page     = max(1, (int) ($_GET['page']  ?? 1));
$limit    = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
$catId    = isset($_GET['category_id']) && is_numeric($_GET['category_id'])
            ? (int) $_GET['category_id'] : null;
$pinned   = isset($_GET['pinned']) ? 1 : null;
$archived = (int) ($_GET['archived'] ?? 0);
$offset   = ($page - 1) * $limit;

$params = [$userId, $archived];
$where  = 'n.user_id = ? AND n.is_archived = ?';

if ($search !== '') {
    // Use LIKE fallback (FULLTEXT needs index; safe fallback always works)
    $where    .= ' AND (n.title LIKE ? OR n.ai_summary LIKE ?)';
    $params[]  = "%{$search}%";
    $params[]  = "%{$search}%";
}
if ($catId !== null) {
    $where   .= ' AND n.category_id = ?';
    $params[] = $catId;
}
if ($pinned !== null) {
    $where   .= ' AND n.is_pinned = 1';
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notes n WHERE {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Fetch page
$sql = "SELECT n.id, n.title, n.ai_summary, n.ai_tags, n.ai_key_points,
               n.word_count, n.is_pinned, n.created_at, n.updated_at,
               c.name AS category_name, c.color AS category_color
        FROM notes n
        LEFT JOIN categories c ON c.id = n.category_id
        WHERE {$where}
        ORDER BY n.is_pinned DESC, n.updated_at DESC
        LIMIT {$limit} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

foreach ($notes as &$note) {
    $note['ai_tags']       = json_decode($note['ai_tags']       ?? '[]', true);
    $note['ai_key_points'] = json_decode($note['ai_key_points'] ?? '[]', true);
}
unset($note);

Response::success([
    'notes'       => $notes,
    'total'       => $total,
    'page'        => $page,
    'limit'       => $limit,
    'total_pages' => (int) ceil($total / $limit),
]);
