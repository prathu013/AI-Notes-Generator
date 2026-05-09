<?php
// ============================================================
// api/admin/users.php — Admin Users Management
// ============================================================

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Response.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Require Admin Privileges
Auth::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDBConnection();

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.username, u.email, u.is_admin, u.status, u.created_at, 
                   COUNT(n.id) as note_count
            FROM users u
            LEFT JOIN notes n ON u.id = n.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();
        
        // Cast values
        foreach ($users as &$u) {
            $u['is_admin'] = (bool)$u['is_admin'];
            $u['note_count'] = (int)$u['note_count'];
        }
        
        Response::success(['users' => $users], 'Users retrieved successfully.');
    } catch (PDOException $e) {
        Response::error("Database error: " . $e->getMessage(), 500);
    }
} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        Response::error("User ID is required.", 400);
    }
    
    // Prevent self-deletion
    if ($id === Auth::id()) {
        Response::error("You cannot delete your own account from the admin dashboard.", 403);
    }
    
    try {
        // Get current status
        $check = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $check->execute([$id]);
        $user = $check->fetch();
        
        if (!$user) {
            Response::error("User not found.", 404);
        }

        $newStatus = ($user['status'] === 'active') ? 'disabled' : 'active';
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        
        $msg = ($newStatus === 'disabled') 
            ? "User account has been disabled. Data remains intact."
            : "User account has been re-enabled.";
            
        Response::success(['status' => $newStatus], $msg);
    } catch (PDOException $e) {
        Response::error("Database error: " . $e->getMessage(), 500);
    }
} elseif ($method === 'PUT') {
    // Toggle Admin status
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0;
    
    if (!$id) {
        Response::error("User ID is required.", 400);
    }
    
    if ($id === Auth::id()) {
        Response::error("You cannot change your own admin status.", 403);
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$is_admin, $id]);
        
        Response::success(null, "User admin status updated.");
    } catch (PDOException $e) {
        Response::error("Database error: " . $e->getMessage(), 500);
    }
} else {
    Response::error("Method not allowed.", 405);
}
