<?php
/**
 * Albion P2P Trade — Logout
 * Destrói a sessão, remove o token "remember me" do banco e redireciona.
 */

require_once __DIR__ . '/../config/db.php';

session_start();

// ── Remove token "remember me" do banco ──────────────────────────────────
if (!empty($_COOKIE['remember_token'])) {
    try {
        $tokenHash = hash('sha256', $_COOKIE['remember_token']);
        db()->prepare('DELETE FROM sessoes WHERE token_hash = ?')
             ->execute([$tokenHash]);
    } catch (PDOException $e) {
        error_log('[albiontrade] logout: ' . $e->getMessage());
    }
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// ── Destrói a sessão PHP ─────────────────────────────────────────────────
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ' . BASE_URL . '/pages/auth/login.php');
exit;
