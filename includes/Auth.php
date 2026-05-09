<?php
// ============================================================
// includes/Auth.php — Session-based authentication helper
// ============================================================

require_once __DIR__ . '/../config/config.php';

class Auth {
    // ── Start / resume session ───────────────────────────────
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ── Login: persist user in session ──────────────────────
    public static function login(array $user): void {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
        $_SESSION['login_at'] = time();
    }

    // ── Logout ───────────────────────────────────────────────
    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Check if session is valid ────────────────────────────
    public static function check(): bool {
        self::startSession();
        if (empty($_SESSION['user_id'])) return false;
        $loginAt = $_SESSION['login_at'] ?? 0;
        if ((time() - $loginAt) > SESSION_LIFETIME) {
            self::logout();
            return false;
        }
        return true;
    }

    // ── Require authentication — terminate if not logged in ──
    public static function require(): void {
        if (!self::check()) {
            require_once __DIR__ . '/Response.php';
            Response::unauthorized('Please log in to continue.');
        }
    }

    // ── Require admin — terminate if not admin ───────────────
    public static function requireAdmin(): void {
        self::require();
        if (empty($_SESSION['is_admin'])) {
            require_once __DIR__ . '/Response.php';
            Response::error('Forbidden. Admins only.', 403);
        }
    }

    // ── Current user id ──────────────────────────────────────
    public static function id(): ?int {
        self::startSession();
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    // ── Current user info ────────────────────────────────────
    public static function user(): ?array {
        self::startSession();
        if (empty($_SESSION['user_id'])) return null;
        return [
            'id'       => (int)$_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email'    => $_SESSION['email'],
            'is_admin' => (bool)($_SESSION['is_admin'] ?? false),
        ];
    }

    // ── Hash password ────────────────────────────────────────
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // ── Verify password ──────────────────────────────────────
    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}
