<?php
/**
 * Albion P2P Trade — Gera token de redefinição de senha
 */

require_once __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/auth/recuperar-senha.php');
    exit;
}

$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Informe um e-mail válido.'];
    header('Location: ' . BASE_URL . '/pages/auth/recuperar-senha.php');
    exit;
}

// Mensagem genérica — não revela se o e-mail existe (segurança)
$msgGenerica = 'Se esse e-mail estiver cadastrado, você receberá o link em instantes. Verifique também sua caixa de spam.';

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Invalida tokens anteriores para este e-mail
        $pdo->prepare('UPDATE senha_resets SET usado = 1 WHERE email = :email AND usado = 0')
            ->execute([':email' => $email]);

        // Gera novo token
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expira    = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        $pdo->prepare('INSERT INTO senha_resets (email, token_hash, expira_em) VALUES (?, ?, ?)')
            ->execute([$email, $tokenHash, $expira]);

        $linkReset = BASE_URL . '/pages/auth/redefinir-senha.php?token=' . $token;

        // ── Em produção: envie o $linkReset por e-mail ──────────────────────
        // mail($email, 'Redefinir senha — Albion P2P Trade', "Acesse: $linkReset");
        //
        // Em desenvolvimento: exibe o link diretamente no flash
        $msgGenerica = 'Link gerado! Acesse: <a href="' . htmlspecialchars($linkReset) . '" style="color:inherit;text-decoration:underline">redefinir senha</a>';
    }

} catch (PDOException $e) {
    error_log('[albiontrade] recuperar-senha: ' . $e->getMessage());
}

$_SESSION['flash'] = ['type' => 'success', 'msg' => $msgGenerica];
header('Location: ' . BASE_URL . '/pages/auth/recuperar-senha.php');
exit;
