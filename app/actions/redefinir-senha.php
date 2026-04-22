<?php
/**
 * Albion P2P Trade — Processa a nova senha via token
 */

require_once __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}

$token   = $_POST['token']            ?? '';
$senha   = $_POST['password']         ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$urlReset = BASE_URL . '/pages/auth/redefinir-senha.php?token=' . urlencode($token);

// Validação básica
if ($token === '') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token inválido.'];
    header('Location: ' . BASE_URL . '/pages/auth/recuperar-senha.php');
    exit;
}

if (strlen($senha) < 8) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'A senha deve ter ao menos 8 caracteres.'];
    header('Location: ' . $urlReset);
    exit;
}

if ($senha !== $confirm) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'As senhas não coincidem.'];
    header('Location: ' . $urlReset);
    exit;
}

try {
    $pdo       = db();
    $tokenHash = hash('sha256', $token);

    // Busca token válido
    $stmt = $pdo->prepare('
        SELECT id, email FROM senha_resets
        WHERE  token_hash = :hash
          AND  expira_em  > NOW()
          AND  usado      = 0
        LIMIT 1
    ');
    $stmt->execute([':hash' => $tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Link expirado ou já utilizado. Solicite um novo.'];
        header('Location: ' . BASE_URL . '/pages/auth/recuperar-senha.php');
        exit;
    }

    // Atualiza senha do usuário
    $novoHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE email = ?')
        ->execute([$novoHash, $reset['email']]);

    // Marca token como usado
    $pdo->prepare('UPDATE senha_resets SET usado = 1 WHERE id = ?')
        ->execute([$reset['id']]);

    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => 'Senha redefinida com sucesso! Faça login com a nova senha.',
    ];
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;

} catch (PDOException $e) {
    error_log('[albiontrade] redefinir-senha: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erro interno. Tente novamente.'];
    header('Location: ' . $urlReset);
    exit;
}
