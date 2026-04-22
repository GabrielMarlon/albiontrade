<?php
/**
 * Albion P2P Trade — Processamento de cadastro
 */

require_once __DIR__ . '/../config/db.php';

session_start();

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/auth/cadastro.php');
    exit;
}

// ── Coleta e sanitiza ──────────────────────────────────────────────────────
$email      = trim(filter_input(INPUT_POST, 'email',            FILTER_SANITIZE_EMAIL)    ?? '');
$senha      =      $_POST['password']         ?? '';
$confirm    =      $_POST['confirm_password'] ?? '';
$nick       = trim(filter_input(INPUT_POST, 'nick',             FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$nome       = trim(filter_input(INPUT_POST, 'nome',             FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$sobrenome  = trim(filter_input(INPUT_POST, 'sobrenome',        FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$servidor   =      $_POST['servidor']         ?? '';
$newsletter = isset($_POST['newsletter']) ? 1 : 0;

// ── Validação ──────────────────────────────────────────────────────────────
$erros = [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'Informe um e-mail válido.';
}
if (strlen($senha) < 8) {
    $erros[] = 'A senha deve ter ao menos 8 caracteres.';
}
if ($senha !== $confirm) {
    $erros[] = 'As senhas não coincidem.';
}
if (mb_strlen($nick) < 2 || mb_strlen($nick) > 60) {
    $erros[] = 'Nick deve ter entre 2 e 60 caracteres.';
}
if (!in_array($servidor, ['americas', 'europe', 'asia'], true)) {
    $erros[] = 'Selecione um servidor válido.';
}
if (!isset($_POST['termo_uso'])) {
    $erros[] = 'Você precisa aceitar os Termos de Uso.';
}

if ($erros) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => implode(' ', $erros)];
    header('Location: ' . BASE_URL . '/pages/auth/cadastro.php');
    exit;
}

// ── Persiste no banco ──────────────────────────────────────────────────────
try {
    $pdo = db();

    // Verifica duplicidade de e-mail e nick
    $stmt = $pdo->prepare(
        'SELECT id, email, nick FROM usuarios WHERE email = :email OR nick = :nick LIMIT 1'
    );
    $stmt->execute([':email' => $email, ':nick' => $nick]);
    $duplicado = $stmt->fetch();

    if ($duplicado) {
        $campo = ($duplicado['email'] === $email) ? 'E-mail' : 'Nick';
        $_SESSION['flash'] = ['type' => 'error', 'msg' => "$campo já está em uso. Escolha outro."];
        header('Location: ' . BASE_URL . '/pages/auth/cadastro.php');
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare('
        INSERT INTO usuarios
            (email, senha_hash, nick, nome, sobrenome, servidor, newsletter)
        VALUES
            (:email, :senha_hash, :nick, :nome, :sobrenome, :servidor, :newsletter)
    ');
    $stmt->execute([
        ':email'      => $email,
        ':senha_hash' => $senhaHash,
        ':nick'       => $nick,
        ':nome'       => $nome       ?: null,
        ':sobrenome'  => $sobrenome  ?: null,
        ':servidor'   => $servidor,
        ':newsletter' => $newsletter,
    ]);

    $userId = (int) $pdo->lastInsertId();

    // Login automático após cadastro
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_nick']  = $nick;
    $_SESSION['user_email'] = $email;

    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => 'Conta criada com sucesso! Bem-vindo à guilda, ' . htmlspecialchars($nick) . '.',
    ];

    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;

} catch (PDOException $e) {
    error_log('[albiontrade] register_process: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Erro interno. Tente novamente em instantes.'];
    header('Location: ' . BASE_URL . '/pages/auth/cadastro.php');
    exit;
}
