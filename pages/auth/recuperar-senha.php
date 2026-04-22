<?php
require_once __DIR__ . '/../../app/config/auth.php';
iniciar_sessao();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha — Albion P2P Trade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body>

<div class="auth-layout">

    <!-- ══ PAINEL ESQUERDO ════════════════════════════ -->
    <aside class="auth-aside">
        <div class="aside-nav">
            <a href="<?= BASE_URL ?>/index.php" class="aside-logo">
                <svg width="28" height="28" viewBox="0 0 30 30" fill="none">
                    <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
                    <polygon points="15,7 23,11.5 23,18.5 15,23 7,18.5 7,11.5" fill="rgba(201,168,76,.1)" stroke="#c9a84c" stroke-width="1"/>
                    <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
                    <circle cx="15" cy="15" r="1.5" fill="#0e0e10"/>
                </svg>
                Albion P2P Trade
            </a>
        </div>

        <div class="aside-body">
            <div class="aside-hex">
                <svg class="hex-spin" width="120" height="120" viewBox="0 0 120 120" fill="none">
                    <circle cx="60" cy="60" r="56" stroke="rgba(201,168,76,.15)" stroke-width="1"
                            stroke-dasharray="4 8" stroke-linecap="round"/>
                </svg>
                <svg width="90" height="90" viewBox="0 0 90 90" fill="none" style="position:relative;z-index:1;display:block;margin:auto;margin-top:15px">
                    <polygon points="45,6 82,26 82,64 45,84 8,64 8,26"
                             fill="rgba(201,168,76,.06)" stroke="#c9a84c" stroke-width="1.2"/>
                    <polygon points="45,18 70,32 70,58 45,72 20,58 20,32"
                             fill="rgba(201,168,76,.08)" stroke="rgba(201,168,76,.4)" stroke-width=".8"/>
                    <!-- Chave -->
                    <circle cx="40" cy="40" r="8" fill="none" stroke="#c9a84c" stroke-width="2"/>
                    <line x1="46" y1="46" x2="57" y2="57" stroke="#c9a84c" stroke-width="2" stroke-linecap="round"/>
                    <line x1="53" y1="54" x2="57" y2="50" stroke="#c9a84c" stroke-width="2" stroke-linecap="round"/>
                    <line x1="55" y1="57" x2="59" y2="53" stroke="#c9a84c" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>

            <h2 class="aside-heading">Recupere o acesso<br><span>à sua conta</span></h2>
            <p class="aside-desc">Informe o e-mail cadastrado e enviaremos um link para você criar uma nova senha em instantes.</p>

            <ul class="aside-features">
                <li>
                    <span class="feat-icon">📧</span>
                    <span>Link de redefinição válido por 1 hora</span>
                </li>
                <li>
                    <span class="feat-icon">🔒</span>
                    <span>Sua conta permanece protegida durante o processo</span>
                </li>
                <li>
                    <span class="feat-icon">⚡</span>
                    <span>Acesso restaurado em menos de 2 minutos</span>
                </li>
            </ul>
        </div>

        <div class="aside-footer">
            Lembrou a senha? <a href="<?= BASE_URL ?>/pages/auth/login.php">Entrar →</a>
        </div>
    </aside>

    <!-- ══ PAINEL DIREITO ═════════════════════════════ -->
    <main class="auth-main">
        <div class="auth-card">

            <a href="<?= BASE_URL ?>/index.php" class="auth-logo-mobile">
                <svg width="24" height="24" viewBox="0 0 30 30" fill="none">
                    <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
                    <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
                </svg>
                Albion P2P Trade
            </a>

            <div class="auth-heading">
                <h1>Recuperar senha</h1>
                <p>Digite o e-mail vinculado à sua conta</p>
            </div>

            <?php if ($flash): ?>
            <div class="flash-alert flash-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠' : '✓' ?></span>
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
            <?php endif; ?>

            <div id="alert-error"></div>

            <form id="recovery-form" action="<?= BASE_URL ?>/app/actions/recuperar-senha.php" method="POST" novalidate>

                <div class="field" style="margin-bottom:28px">
                    <label for="email">E-mail cadastrado</label>
                    <div class="input-wrap">
                        <span class="input-icon">✉</span>
                        <input type="email" id="email" name="email"
                               placeholder="seu@email.com"
                               autocomplete="email">
                    </div>
                    <span class="field-msg" id="err-email">Informe um e-mail válido.</span>
                </div>

                <button type="submit" class="btn-primary" id="btn-submit">
                    Enviar link de recuperação
                </button>

            </form>

            <div class="auth-switch" style="margin-top:20px">
                <a href="<?= BASE_URL ?>/pages/auth/login.php">← Voltar para o login</a>
            </div>

        </div>
    </main>

</div>

<script>
document.getElementById('recovery-form').addEventListener('submit', function(e) {
    const email = document.getElementById('email');
    const err   = document.getElementById('err-email');
    err.className = 'field-msg';
    email.classList.remove('is-error');

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        err.className = 'field-msg err';
        email.classList.add('is-error');
        e.preventDefault();
        return;
    }

    const btn = document.getElementById('btn-submit');
    btn.textContent = 'Enviando…';
    btn.disabled = true;
});
</script>
</body>
</html>
