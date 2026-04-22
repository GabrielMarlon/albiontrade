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
    <title>Entrar — Albion P2P Trade</title>
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
                    <circle cx="45" cy="45" r="14" fill="rgba(201,168,76,.12)" stroke="#c9a84c" stroke-width="1"/>
                    <path d="M45 34 L50 42 L45 56 L40 42 Z" fill="#c9a84c" opacity=".7"/>
                    <circle cx="45" cy="45" r="4" fill="#c9a84c"/>
                </svg>
            </div>

            <h2 class="aside-heading">Bem-vindo de volta,<br><span>aventureiro</span></h2>
            <p class="aside-desc">Acesse sua conta para gerenciar suas ofertas, negociações e reputação na plataforma.</p>

            <ul class="aside-features">
                <li>
                    <span class="feat-icon">🛡</span>
                    <span>Trades protegidos por escrow — seus itens nunca ficam expostos</span>
                </li>
                <li>
                    <span class="feat-icon">⭐</span>
                    <span>Reputação pública acumulada a cada negociação concluída</span>
                </li>
                <li>
                    <span class="feat-icon">🔔</span>
                    <span>Alertas de preço em tempo real para os itens que você monitora</span>
                </li>
                <li>
                    <span class="feat-icon">💬</span>
                    <span>Chat interno com histórico completo das suas negociações</span>
                </li>
            </ul>
        </div>

        <div class="aside-footer">
            Ainda não tem conta? <a href="cadastro.php">Criar gratuitamente →</a>
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
                <h1>Entrar na plataforma</h1>
                <p>Use seu e-mail ou nick do Albion Online</p>
            </div>

            <?php if ($flash): ?>
            <div class="flash-alert flash-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠' : '✓' ?></span>
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
            <?php endif; ?>

            <div id="alert-error"></div>

            <form id="login-form" action="<?= BASE_URL ?>/app/actions/login.php" method="POST" novalidate>

                <div class="field">
                    <label for="username">Nick ou e-mail</label>
                    <div class="input-wrap">
                        <span class="input-icon">⚔</span>
                        <input type="text" id="username" name="username"
                               placeholder="Seu nick ou endereço de e-mail"
                               autocomplete="username">
                    </div>
                    <span class="field-msg" id="err-user">Campo obrigatório.</span>
                </div>

                <div class="field">
                    <label for="password">
                        Senha
                        <a href="recuperar-senha.php">Esqueceu a senha?</a>
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               autocomplete="current-password">
                        <button type="button" class="btn-eye" onclick="togglePw('password', this)"
                                aria-label="Mostrar senha">👁</button>
                    </div>
                    <span class="field-msg" id="err-pass">Campo obrigatório.</span>
                </div>

                <div class="field" style="margin-bottom:24px">
                    <label class="check-wrap" style="text-transform:none;letter-spacing:0;font-size:.85rem;color:var(--text-secondary);font-weight:400">
                        <input type="checkbox" name="remember" value="1">
                        Manter conectado por 30 dias
                    </label>
                </div>

                <button type="submit" class="btn-primary" id="btn-submit">
                    Entrar na plataforma
                </button>

            </form>

            <div class="auth-divider">ou</div>

            <button class="btn-discord" onclick="alert('Em breve: login com Discord')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/>
                </svg>
                Entrar com Discord
            </button>

            <div class="auth-switch">
                Ainda não tem conta? <a href="cadastro.php">Criar gratuitamente</a>
            </div>

        </div>
    </main>

</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}

document.getElementById('login-form').addEventListener('submit', function(e) {
    let ok = true;
    const u  = document.getElementById('username');
    const p  = document.getElementById('password');
    const eu = document.getElementById('err-user');
    const ep = document.getElementById('err-pass');

    eu.className = 'field-msg'; ep.className = 'field-msg';
    u.classList.remove('is-error'); p.classList.remove('is-error');

    if (!u.value.trim()) {
        eu.className = 'field-msg err';
        u.classList.add('is-error');
        ok = false;
    }
    if (!p.value) {
        ep.className = 'field-msg err';
        p.classList.add('is-error');
        if (ok) p.focus();
        ok = false;
    }
    if (!ok) { e.preventDefault(); return; }

    const btn = document.getElementById('btn-submit');
    btn.textContent = 'Entrando…';
    btn.disabled = true;
});
</script>
</body>
</html>
