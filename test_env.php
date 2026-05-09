<?php
// Temporary diagnostic — delete this file after fixing!
define('BASE_PATH', dirname(__FILE__));

$envPath = BASE_PATH . '/.env';

echo "<h2>Diagnostic: Environment Loader</h2>";
echo "<b>BASE_PATH:</b> " . BASE_PATH . "<br>";
echo "<b>.env path:</b> " . $envPath . "<br>";
echo "<b>.env exists?</b> " . (file_exists($envPath) ? '✅ YES' : '❌ NO — FILE NOT FOUND') . "<br><br>";

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "<b>Lines in .env:</b> " . count($lines) . "<br><br>";
    echo "<pre>";
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === 'GEMINI_API_KEY') {
            echo htmlspecialchars("$key=" . substr($value, 0, 20) . "... (length=" . strlen($value) . ")") . "\n";
        } else if ($key === 'DB_PASS') {
            echo htmlspecialchars("$key=***hidden***") . "\n";
        } else {
            echo htmlspecialchars("$key=$value") . "\n";
        }
    }
    echo "</pre>";
}

require_once __DIR__ . '/config/config.php';

echo "<br><b>GEMINI_API_KEY constant value (first 20 chars):</b> ";
$key = GEMINI_API_KEY;
if (empty($key)) {
    echo "❌ EMPTY — constant is not being set!";
} else {
    echo "✅ " . htmlspecialchars(substr($key, 0, 20)) . "... (length=" . strlen($key) . ")";
}
?>
