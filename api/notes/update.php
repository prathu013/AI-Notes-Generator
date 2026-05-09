<?php
// ============================================================
// api/notes/update.php — Update note fields
// PUT { id, title?, is_pinned?, is_archived?, category_id? }
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Auth::require();
$userId = Auth::id();
$data   = Validator::json();

$v = Validator::make($data, ['id' => 'required|integer']);
if ($v->fails()) Response::error('Note ID is required.', 422, $v->errors());

$id  = (int) $v->get('id');
$pdo = getDBConnection();

// Verify ownership
$stmt = $pdo->prepare('SELECT id FROM notes WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
if (!$stmt->fetch()) Response::notFound('Note not found.');

// ── Build dynamic SET clause ──────────────────────────────────
$allowed = ['title', 'is_pinned', 'is_archived', 'category_id'];
$sets    = [];
$params  = [];

foreach ($allowed as $field) {
    if (array_key_exists($field, $data)) {
        $sets[]   = "{$field} = ?";
        $params[] = $data[$field];
    }
}

if (empty($sets)) {
    Response::error('No fields to update.', 422);
}

$params[] = $id;
$params[] = $userId;
$sql      = 'UPDATE notes SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?';
$pdo->prepare($sql)->execute($params);

// Return updated note
$stmt = $pdo->prepare('SELECT * FROM notes WHERE id = ?');
$stmt->execute([$id]);
$note = $stmt->fetch();
$note['ai_key_points'] = json_decode($note['ai_key_points'] ?? '[]', true);
$note['ai_tags']       = json_decode($note['ai_tags']       ?? '[]', true);

Response::success($note, 'Note updated.');
