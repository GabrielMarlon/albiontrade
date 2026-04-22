<?php
/**
 * Albion P2P Trade — Em Breve
 * Inclua este arquivo nos stubs de páginas ainda não implementadas.
 * Variáveis esperadas (definidas antes do include):
 *   $feature_titulo  — Nome da funcionalidade  (ex: "Criar Anúncio")
 *   $feature_icone   — Emoji/ícone             (ex: "📝")
 *   $feature_desc    — Descrição breve         (ex: "Publique seus itens...")
 */

require_once __DIR__ . '/../app/config/auth.php';
iniciar_sessao();
exigir_login();

$logado   = isset($_SESSION['user_id']);
$userNick = htmlspecialchars($_SESSION['user_nick'] ?? '');

$feature_titulo = $feature_titulo ?? 'Em Breve';
$feature_icone  = $feature_icone  ?? '⚒';
$feature_desc   = $feature_desc   ?? 'Esta funcionalidade está sendo forjada e estará disponível em breve.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($feature_titulo) ?> — Albion P2P Trade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/em-breve.css">
</head>
<body>

<!-- ══ NAVBAR ════════════════════════════════════════════════════ -->
<nav id="navbar">
    <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
        <svg width="28" height="28" viewBox="0 0 30 30" fill="none">
            <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
            <polygon points="15,7 23,11.5 23,18.5 15,23 7,18.5 7,11.5" fill="rgba(201,168,76,.1)" stroke="#c9a84c" stroke-width="1"/>
            <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
            <circle cx="15" cy="15" r="1.5" fill="#0e0e10"/>
        </svg>
        Albion P2P Trade
    </a>

    <ul class="nav-links">
        <li><a href="<?= BASE_URL ?>/pages/dashboard.php">Marketplace</a></li>
    </ul>

    <div class="nav-auth">
        <?php if ($logado): ?>
            <span class="nav-user">⚔ <?= $userNick ?></span>
            <a href="<?= BASE_URL ?>/app/actions/logout.php" class="nav-btn-ghost">Sair</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/auth/login.php" class="nav-btn-ghost">Entrar</a>
        <?php endif; ?>
    </div>

    <button id="nav-hamburger" onclick="toggleMobileMenu()" aria-label="Abrir menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- ══ OVERLAY + DRAWER MOBILE ═══════════════════════════════════ -->
<div id="nav-overlay" onclick="closeMobileMenu()"></div>
<div id="nav-drawer" aria-hidden="true">
    <div class="drawer-inner">
        <div class="drawer-header">
            <a href="<?= BASE_URL ?>/index.php" class="drawer-logo">
                <svg width="22" height="22" viewBox="0 0 30 30" fill="none">
                    <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
                    <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
                </svg>
                Albion P2P Trade
            </a>
            <button class="drawer-close" onclick="closeMobileMenu()" aria-label="Fechar menu">✕</button>
        </div>

        <ul class="drawer-nav">
            <li><a href="<?= BASE_URL ?>/pages/dashboard.php">Marketplace</a></li>
        </ul>

        <div class="drawer-auth">
            <?php if ($logado): ?>
                <div class="drawer-user">⚔ <?= $userNick ?></div>
                <a href="<?= BASE_URL ?>/app/actions/logout.php" class="drawer-btn drawer-btn-ghost">Sair</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/auth/login.php" class="drawer-btn drawer-btn-primary">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ CONTEÚDO PRINCIPAL ════════════════════════════════════════ -->
<main class="soon-page">

    <!-- Decoração de fundo -->
    <div class="soon-bg-orb soon-bg-orb-1"></div>
    <div class="soon-bg-orb soon-bg-orb-2"></div>
    <div class="soon-bg-grid"></div>

    <div class="soon-card">

        <!-- Badge de status -->
        <div class="soon-badge">
            <span class="soon-badge-dot"></span>
            Em desenvolvimento
        </div>

        <!-- Ícone da funcionalidade em hexágono -->
        <div class="soon-icon-wrap">
            <svg class="soon-hex-ring" viewBox="0 0 128 128" fill="none"
                 xmlns="http://www.w3.org/2000/svg">
                <circle cx="64" cy="64" r="60"
                        stroke="rgba(201,168,76,.18)"
                        stroke-width="1"
                        stroke-dasharray="5 9"
                        stroke-linecap="round"/>
            </svg>
            <svg style="position:absolute;inset:0;width:100%;height:100%" viewBox="0 0 128 128" fill="none">
                <polygon points="64,10 112,36 112,92 64,118 16,92 16,36"
                         fill="rgba(201,168,76,.05)"
                         stroke="rgba(201,168,76,.25)"
                         stroke-width="1.2"/>
                <polygon points="64,24 98,42 98,86 64,104 30,86 30,42"
                         fill="rgba(201,168,76,.04)"
                         stroke="rgba(201,168,76,.12)"
                         stroke-width=".8"/>
            </svg>
            <div class="soon-icon"><?= $feature_icone ?></div>
        </div>

        <!-- Nome da funcionalidade -->
        <div class="soon-feature-name"><?= htmlspecialchars($feature_titulo) ?></div>

        <!-- Título principal -->
        <h1 class="soon-title">Em Breve</h1>

        <!-- Descrição -->
        <p class="soon-desc"><?= htmlspecialchars($feature_desc) ?></p>

        <!-- Barra de progresso animada -->
        <div class="soon-progress">
            <div class="soon-progress-fill"></div>
        </div>

        <!-- Divisor -->
        <div class="soon-divider">aguardando forjamento</div>

        <!-- Botões de ação -->
        <div class="soon-actions">
            <a href="javascript:history.back()" class="btn-soon-ghost">
                ← Voltar
            </a>
            <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-soon-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
                </svg>
                Ir ao Marketplace
            </a>
        </div>

        <!-- Brand -->
        <div class="soon-brand">Albion P2P Trade</div>

    </div>
</main>

<script>
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

function toggleMobileMenu() {
    const open = document.getElementById('nav-drawer').classList.toggle('open');
    document.getElementById('nav-overlay').classList.toggle('open', open);
    const btn = document.getElementById('nav-hamburger');
    btn.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open);
    document.getElementById('nav-drawer').setAttribute('aria-hidden', !open);
    document.body.style.overflow = open ? 'hidden' : '';
}
function closeMobileMenu() {
    document.getElementById('nav-drawer').classList.remove('open');
    document.getElementById('nav-overlay').classList.remove('open');
    document.getElementById('nav-hamburger').classList.remove('open');
    document.getElementById('nav-hamburger').setAttribute('aria-expanded', 'false');
    document.getElementById('nav-drawer').setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMobileMenu(); });
</script>
</body>
</html>
