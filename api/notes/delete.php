<?php
// ============================================================
// api/notes/delete.php — Delete a note
// DELETE { id }  or  POST { id, _method: "DELETE" }
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Validator.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'DELETE' && $method !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Auth::require();
$userId = Auth::id();
$data   = Validator::json();

// Also accept ?id=X in query string
$id = isset($data['id']) ? (int) $data['id'] : (int) ($_GET['id'] ?? 0);

if ($id <= 0) Response::error('Note ID is required.', 422);

$pdo  = getDBConnection();
$stmt = $pdo->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);

if ($stmt->rowCount() === 0) {
    Response::notFound('Note not found or already deleted.');
}

Response::success(null, 'Note deleted successfully.');
