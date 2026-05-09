<?php
// ============================================================
// make_admin.php
// Utility script to give a user admin privileges in the database.
// WARNING: Remove this file from production servers after use!
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Only allow execution from CLI or localhost for safety
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
$isCli = (php_sapi_name() === 'cli');

if (!$isCli && !$isLocalhost) {
    die("This script can only be run locally or via command line.");
}

$message = "";
$messageType = "info"; // "success", "error", "info"

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($argv[1]))) {
    $email = $_POST['email'] ?? $argv[1] ?? '';
    
    if (empty($email)) {    
        $message = "Please provide an email address.";
        $messageType = "error";
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, username, is_admin FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // User doesn't exist. Let's create them as an admin!
                $defaultPassword = 'password123';
                $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
                $username = explode('@', $email)[0] . rand(100, 999);
                
                $insertSt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
                if ($insertSt->execute([$username, $email, $hash])) {
                     $message = "User created successfully and granted Admin rights!\n\nEmail: $email\nPassword: $defaultPassword\n\nPlease sign in and change your password.";
                     $messageType = "success";
                } else {
                     $message = "Error: Failed to create the user in the database.";
                     $messageType = "error";
                }
            } else if ($user['is_admin'] == 1) {
                $message = "User '{$user['username']}' is already an admin.";
                $messageType = "info";
            } else {
                $updateSt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
                if ($updateSt->execute([$email])) {
                     $message = "Success: User '{$user['username']}' ($email) has been granted Admin rights!";
                     $messageType = "success";
                } else {
                     $message = "Error: Failed to update the database.";
                     $messageType = "error";
                }
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Admin Utility</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { padding-top: 50px; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; background: var(--bg-0); }
        .admin-card { background: var(--bg-card); padding: 2.5rem; border-radius: var(--r-xl); border: 1px solid var(--bg-card-border); max-width: 480px; width: 100%; box-shadow: var(--sh-lg); text-align: center; }
        .admin-title { font-size: 1.5rem; font-weight: 800; margin-bottom: .5rem; }
        .admin-sub { color: var(--tx-2); font-size: .85rem; margin-bottom: 2rem; }
        .admin-icon { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gold); }
        .warning-box { background: rgba(240, 165, 0, 0.1); border: 1px solid var(--gold-border); padding: 1rem; border-radius: var(--r-sm); margin-top: 2rem; font-size: .8rem; color: var(--gold-light); }
    </style>
</head>
<body>
    <div class="admin-card">
        <div class="admin-icon">🛡️</div>
        <h2 class="admin-title">Grant Admin Rights</h2>
        <p class="admin-sub">Enter an email address to grant them administrator access. If the account doesn't exist, it will be created for you.</p>
        
        <form method="POST" style="text-align: left;">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="admin@example.com" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Make Admin →</button>
        </form>
        
        <?php if ($message): ?>
            <div style="margin-top: 1.5rem; text-align: left; white-space: pre-line;" class="toast toast-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <strong>⚠️ Security Notice</strong><br>
            Delete this file (<code>make_admin.php</code>) from your server immediately after you are done setting up your admin account.
        </div>
    </div>
</body>
</html>
