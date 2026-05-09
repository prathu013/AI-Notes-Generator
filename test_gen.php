<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Gemini.php';

try {
    $res = Gemini::generateNotes("Machine learning is a subset of AI.");
    print_r($res);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
