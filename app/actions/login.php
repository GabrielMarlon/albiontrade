<?php
/**
 * Albion P2P Trade — Processamento de login
 */

require_once __DIR__ . '/../config/db.php';

// "Remember me" precisa ser configurado antes de session_start()
if (!empty($_POST['remember'])) {
    $lifetime = 60 * 60 * 24 * 30; // 30 dias
    ini_set('session.gc_maxlifetime', $lifetime);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => false,   // true em HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

session_start();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}

// ── Coleta ────────────────────────────────────────────────────────────────
$credencial = trim($_POST['username'] ?? '');
$senha      =      $_POST['password'] ?? '';

if ($credencial === '' || $senha === '') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Preencha e-mail/nick e senha.'];
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}

// ── Consulta ──────────────────────────────────────────────────────────────
try {
    $pdo = db();

    // Aceita tanto e-mail quanto nick
    $stmt = $pdo->prepare('
        SELECT id, email, nick, senha_hash, ativo
        FROM   usuarios
        WHERE  email = :cred1 OR nick = :cred2
        LIMIT  1
    ');
    $stmt->execute([':cred1' => $credencial, ':cred2' => $credencial]);
    $usuario = $stmt->fetch();

    // Verifica existência e senha (usa timing-safe comparison do PHP)
    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        // Pequeno delay para dificultar brute-force timing attacks
        usleep(random_int(100_000, 300_000));
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Usuário ou senha incorretos.'];
        header('Location: ' . BASE_URL . '/pages/auth/login.php');
        exit;
    }

    if (!(bool) $usuario['ativo']) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Conta inativa. Verifique seu e-mail.'];
        header('Location: ' . BASE_URL . '/pages/auth/login.php');
        exit;
    }

    // Atualiza hash se o custo de bcrypt mudou
    if (password_needs_rehash($usuario['senha_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $novoHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([$novoHash, $usuario['id']]);
    }

    // ── Cria sessão ──────────────────────────────────────────────────────
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $usuario['id'];
    $_SESSION['user_nick']  = $usuario['nick'];
    $_SESSION['user_email'] = $usuario['email'];

    // ── "Lembrar por 30 dias" — persiste token no banco ──────────────────
    if (!empty($_POST['remember'])) {
        $token     = bin2hex(random_bytes(32));          // 64 chars aleatórios
        $tokenHash = hash('sha256', $token);
        $expira    = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

        $pdo->prepare('
            INSERT INTO sessoes (usuario_id, token_hash, ip, user_agent, expira_em)
            VALUES (?, ?, ?, ?, ?)
        ')->execute([
            (int) $usuario['id'],
            $tokenHash,
            $_SERVER['REMOTE_ADDR']                          ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expira,
        ]);

        setcookie('remember_token', $token, [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => false,   // true em HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => 'Bem-vindo de volta, ' . htmlspecialchars($usuario['nick']) . '!',
    ];

    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log('[albiontrade] login_process: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erro interno. Tente novamente em instantes.'];
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}
