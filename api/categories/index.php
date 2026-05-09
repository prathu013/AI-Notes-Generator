<?php
// ============================================================
// api/categories/index.php — CRUD for categories
// GET    → list all categories for user
// POST   → create category { name, color? }
// DELETE → delete category { id }
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Validator.php';

Auth::require();
$userId = Auth::id();
$pdo    = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: list ─────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT c.*, COUNT(n.id) AS note_count
         FROM categories c
         LEFT JOIN notes n ON n.category_id = c.id AND n.is_archived = 0
         WHERE c.user_id = ?
         GROUP BY c.id
         ORDER BY c.name ASC'
    );
    $stmt->execute([$userId]);
    Response::success($stmt->fetchAll());
}

// ── POST: create ──────────────────────────────────────────────
if ($method === 'POST') {
    $data = Validator::json();
    $v    = Validator::make($data, ['name' => 'required|string|min:1|max:100']);
    if ($v->fails()) Response::error('Validation failed.', 422, $v->errors());

    $name  = trim($v->get('name'));
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'] ?? '') ? $data['color'] : '#6366f1';

    // Enforce limit of 20 categories per user
    $count = (int) $pdo->prepare('SELECT COUNT(*) FROM categories WHERE user_id = ?')
        ->execute([$userId]);

    $stmt = $pdo->prepare('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $name, $color]);
    $id = (int) $pdo->lastInsertId();

    Response::success(
        ['id' => $id, 'user_id' => $userId, 'name' => $name, 'color' => $color, 'note_count' => 0],
        'Category created.',
        201
    );
}

// ── DELETE ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $data = Validator::json();
    $id   = isset($data['id']) ? (int) $data['id'] : (int) ($_GET['id'] ?? 0);
    if ($id <= 0) Response::error('Category ID required.', 422);

    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() === 0) Response::notFound('Category not found.');
    Response::success(null, 'Category deleted.');
}

Response::error('Method not allowed.', 405);
