<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Gemini.php';

try {
    $ai = Gemini::generateNotes("artificial intelligence and machine learning");
    print_r($ai);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    // Now let's test a mock to see if preg_replace is the issue
}
