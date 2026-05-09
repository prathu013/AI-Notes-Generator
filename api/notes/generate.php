<?php
// ============================================================
// api/notes/generate.php — Generate AI notes via Gemini
// POST { text, category_id? }
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Validator.php';
require_once __DIR__ . '/../../includes/Gemini.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Auth::require();
$userId = Auth::id();

$data = Validator::json();

$v = Validator::make($data, [
    'text' => 'required|string|min:20|max:15000',
]);

if ($v->fails()) {
    Response::error('Validation failed.', 422, $v->errors());
}

$rawText    = trim($v->get('text'));
$categoryId = isset($data['category_id']) && is_numeric($data['category_id'])
    ? (int) $data['category_id'] : null;

$pdo = getDBConnection();

$noteId = null;
$status = 'success';
$errMsg = null;

try {
    // ── Call Gemini ──────────────────────────────────────────
    $ai = Gemini::generateNotes($rawText);

    $wordCount = str_word_count(strip_tags($rawText));

    // ── Persist note ─────────────────────────────────────────
    $stmt = $pdo->prepare(
        'INSERT INTO notes
            (user_id, category_id, title, raw_input, ai_summary, ai_key_points, ai_tags, word_count)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $categoryId,
        $ai['title'],
        $rawText,
        $ai['summary'],
        json_encode($ai['key_points'], JSON_UNESCAPED_UNICODE),
        json_encode($ai['tags'],       JSON_UNESCAPED_UNICODE),
        $wordCount,
    ]);
    $noteId = (int) $pdo->lastInsertId();

    // ── Log AI request ───────────────────────────────────────
    $usage = $ai['usage'] ?? [];
    $pdo->prepare(
        'INSERT INTO ai_requests
            (user_id, note_id, model, prompt_tokens, completion_tokens, total_tokens, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId,
        $noteId,
        $ai['model'],
        $usage['prompt_tokens']     ?? 0,
        $usage['completion_tokens'] ?? 0,
        $usage['total_tokens']      ?? 0,
        'success',
    ]);

} catch (RuntimeException $e) {
    $errMsg = $e->getMessage();
    $status = 'error';

    // Log failed request
    $pdo->prepare(
        'INSERT INTO ai_requests (user_id, note_id, model, status, error_message)
         VALUES (?, NULL, ?, ?, ?)'
    )->execute([$userId, GEMINI_MODEL, 'error', $errMsg]);

    Response::error($errMsg, 502);
}

// ── Fetch the full note to return ────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM notes WHERE id = ?');
$stmt->execute([$noteId]);
$note = $stmt->fetch();
$note['ai_key_points'] = json_decode($note['ai_key_points'] ?? '[]', true);
$note['ai_tags']       = json_decode($note['ai_tags']       ?? '[]', true);

Response::success($note, 'Notes generated successfully.', 201);
