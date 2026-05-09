<?php
// ============================================================
// config/database.php — PDO connection factory
// ============================================================

require_once __DIR__ . '/config.php';

function getDSN(): string {
    // We use the constants defined in config.php as the fallback
    $host = env('DB_HOST', DB_HOST);
    $port = env('DB_PORT', DB_PORT);
    $name = env('DB_NAME', DB_NAME); 
    
    return "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
}

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO(
            getDSN(),
            env('DB_USER', DB_USER),
            env('DB_PASS', DB_PASS),
            $options
        );
    } catch (PDOException $e) {
        // Return detailed JSON error to find the root cause
        http_response_code(500);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false, 
            'message' => 'Detailed DB Error: ' . $e->getMessage(),
            'connection_details' => [
                'host' => env('DB_HOST', DB_HOST),
                'dbname' => env('DB_NAME', DB_NAME),
                'user' => env('DB_USER', DB_USER),
                'env_state' => APP_ENV
            ]
        ]);
        exit;
    }

    return $pdo;
}

// Automatically initialize the connection when this file is included
$pdo = getDBConnection();