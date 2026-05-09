<?php
// ============================================================
// config/config.php — App-wide constants & env loader
// ============================================================

// 1. Define the absolute path to the project root
// Since this file is in /config/config.php, the root is one level up.
define('BASE_PATH', realpath(__DIR__ . '/..'));

// ── Load .env file ───────────────────────────────────────────
function loadEnv(string $path): void {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Always populate $_ENV and $_SERVER
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// Execute the loader
loadEnv(BASE_PATH . '/.env');

// ── Helper ───────────────────────────────────────────────────
function env(string $key, mixed $default = null): mixed {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    return $default;
}

// ── App settings ─────────────────────────────────────────────
define('APP_NAME',    env('APP_NAME', 'AI Notes Generator'));
define('APP_ENV',     env('APP_ENV',  'development')); // Default to dev to see errors
define('APP_SECRET',  env('APP_SECRET', 'change-me'));

// ── Session ──────────────────────────────────────────────────
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 86400));

// ── Gemini ───────────────────────────────────────────────────
define('GEMINI_API_KEY',    env('GEMINI_API_KEY', 'AQ.Ab8RN6IwiumbDiy5KdigfYprPTckcKi-IWk_fltiNAEUozgvaQ'));
define('GEMINI_MODEL',      env('GEMINI_MODEL',   'gemini-1.5-flash'));
define('GEMINI_MAX_TOKENS', (int) env('GEMINI_MAX_TOKENS', 3000));
define('GEMINI_API_URL',    'https://generativelanguage.googleapis.com/v1beta/models/');

// ── Database ─────────────────────────────────────────────────
// FIX: Changed 'ai-notes-generator' to 'ai-notes-generator' to match your phpMyAdmin
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'ai-notes-generator')); 
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// ── Error reporting ──────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Session config ───────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  (string) SESSION_LIFETIME);
    session_start();
}