<?php
/**
 * Albion P2P Trade — Autenticação central
 * Inclua este arquivo no topo de cada página.
 * Chama session_start() e tenta auto-login via cookie "remember me".
 */

require_once __DIR__ . '/db.php';

function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Já logado via sessão PHP — nada a fazer
    if (!empty($_SESSION['user_id'])) {
        return;
    }

    // Tenta auto-login pelo cookie de "lembrar"
    $token = $_COOKIE['remember_token'] ?? '';
    if ($token === '') {
        return;
    }

    try {
        $pdo       = db();
        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare('
            SELECT s.usuario_id, u.nick, u.email, u.ativo
            FROM   sessoes  s
            JOIN   usuarios u ON u.id = s.usuario_id
            WHERE  s.token_hash = :hash
              AND  s.expira_em  > NOW()
            LIMIT  1
        ');
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();

        if ($row && (bool) $row['ativo']) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int) $row['usuario_id'];
            $_SESSION['user_nick']  = $row['nick'];
            $_SESSION['user_email'] = $row['email'];
        } else {
            // Token inválido ou expirado — remove o cookie
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    } catch (PDOException $e) {
        error_log('[albiontrade] auto-login: ' . $e->getMessage());
    }
}

/**
 * Garante que o usuário está autenticado.
 * Se não estiver, redireciona para o login com flash de aviso.
 * Chame logo após iniciar_sessao() em qualquer página protegida.
 */
function exigir_login(): void
{
    if (!empty($_SESSION['user_id'])) {
        return;
    }

    $_SESSION['flash'] = [
        'type' => 'error',
        'msg'  => 'Você precisa estar logado para acessar esta página.',
    ];

    $loginUrl = BASE_URL . '/pages/auth/login.php';
    header('Location: ' . $loginUrl);
    exit;
}
