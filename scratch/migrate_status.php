<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'disabled') NOT NULL DEFAULT 'active' AFTER is_admin");
        echo "Successfully added 'status' column to 'users' table.\n";
    } else {
        echo "Column 'status' already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
