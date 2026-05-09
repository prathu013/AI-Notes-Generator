<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Include the database factory
require_once 'config/database.php';

try {
    // 2. Actually CALL the function to get the connection
    $connection = getDBConnection();

    if ($connection instanceof PDO) {
        echo "<div style='font-family: sans-serif; padding: 20px; border: 2px solid green; border-radius: 10px; background: #eaffea;'>";
        echo "<h2 style='color: green;'>✅ Success!</h2>";
        echo "Successfully connected to the database: <b>" . DB_NAME . "</b>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; border: 2px solid red; border-radius: 10px; background: #ffeaea;'>";
    echo "<h2 style='color: red;'>❌ Connection Failed!</h2>";
    echo "Error message: " . $e->getMessage();
    echo "</div>";
}