<?php
// ============================================================
// api/admin/dashboard.php — Admin Dashboard Stats
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Require Admin Privileges
Auth::requireAdmin();

try {
    $pdo = getDBConnection();
    
    // Total Users
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmtUsers->fetchColumn();
    
    // Total Notes
    $stmtNotes = $pdo->query("SELECT COUNT(*) FROM notes");
    $totalNotes = $stmtNotes->fetchColumn();
    
    // Total AI Requests
    $stmtReqs = $pdo->query("SELECT COUNT(*) FROM ai_requests");
    $totalRequests = $stmtReqs->fetchColumn();
    
    // Total AI Tokens
    $stmtTokens = $pdo->query("SELECT SUM(total_tokens) FROM ai_requests");
    $totalTokens = $stmtTokens->fetchColumn() ?: 0;
    
    // Recent Users
    $stmtRecentUsers = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmtRecentUsers->fetchAll();
    
    // Recent Notes
    $stmtRecentNotes = $pdo->query("
        SELECT n.id, n.title, n.created_at, u.username 
        FROM notes n 
        LEFT JOIN users u ON n.user_id = u.id 
        ORDER BY n.created_at DESC LIMIT 5
    ");
    $recentNotes = $stmtRecentNotes->fetchAll();

    Response::success([
        'stats' => [
            'totalUsers' => (int)$totalUsers,
            'totalNotes' => (int)$totalNotes,
            'totalRequests' => (int)$totalRequests,
            'totalTokens' => (int)$totalTokens,
        ],
        'recentUsers' => $recentUsers,
        'recentNotes' => $recentNotes
    ], 'Dashboard stats retrieved successfully.');
    
} catch (PDOException $e) {
    Response::error("Database error: " . $e->getMessage(), 500);
}
