<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Gemini.php';

try {
    // Generate notes directly to see what Gemini returns
    $ai = Gemini::generateNotes("artificial intelligence and machine learning");
    print_r($ai);
} catch (Exception $e) {
    echo "ERROR CAUGHT:\n";
    echo $e->getMessage() . "\n";
    // If it's a runtime exception about invalid JSON, the Gemini class doesn't output the full string.
    // Let's modify our script to do a manual cURL just like Gemini.php to dump the full raw response.
}
