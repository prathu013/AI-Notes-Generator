<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    $samples = [
        ['username' => 'Rahul', 'email' => 'rahul.s@example.com', 'is_admin' => 0],
        ['username' => 'Anjali', 'email' => 'anjali.v@example.com', 'is_admin' => 0],
        ['username' => 'Suresh', 'email' => 'suresh.k@example.com', 'is_admin' => 0],
        ['username' => 'Meera', 'email' => 'meera.r@example.com', 'is_admin' => 0],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, is_admin, created_at) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($samples as $u) {
        $hoursAgo = rand(1, 48);
        $createdAt = date('Y-m-d H:i:s', strtotime("-$hoursAgo hours"));
        $stmt->execute([
            $u['username'], 
            $u['email'], 
            password_hash('password123', PASSWORD_DEFAULT), 
            $u['is_admin'],
            $createdAt
        ]);
    }
    
    echo "Seed successful! Added " . count($samples) . " sample users.";
} catch (Exception $e) {
    echo "Seed failed: " . $e->getMessage();
}
