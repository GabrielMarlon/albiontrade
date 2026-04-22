<?php
require_once __DIR__ . '/app/config/auth.php';
require_once __DIR__ . '/app/config/db.php';
iniciar_sessao();
$flash    = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$logado   = isset($_SESSION['user_id']);
$userNick = htmlspecialchars($_SESSION['user_nick'] ?? '');

// ── Estatísticas da hero section ──────────────────────────────────────────
$stats = db()->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios  WHERE ativo = 1)                        AS total_usuarios,
        (SELECT COUNT(*) FROM anuncios  WHERE ativo = 1 AND expira_em > NOW())  AS anuncios_ativos,
        (SELECT COUNT(*) FROM anuncios)                                          AS total_anuncios
")->fetch();

function fmtStat(int $n): string {
    if ($n === 0) return '0';
    if ($n >= 1000) return number_format($n / 1000, 1, '.', '') . 'k+';
    return $n . '+';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albion P2P Trade — Comércio Seguro entre Jogadores</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
</head>
<body>

<?php if ($flash): ?>
<div class="flash-msg">
    <?= $flash['type'] === 'error' ? '⚠' : '✓' ?>
    &nbsp;<?= htmlspecialchars($flash['msg']) ?>
    <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<!-- ══ NAVBAR ══════════════════════════════════════════════════ -->
<nav id="navbar">
    <a href="index.php" class="nav-logo">
        <svg width="30" height="30" viewBox="0 0 30 30" fill="none">
            <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
            <polygon points="15,7 23,11.5 23,18.5 15,23 7,18.5 7,11.5" fill="rgba(201,168,76,.1)" stroke="#c9a84c" stroke-width="1"/>
            <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
            <circle cx="15" cy="15" r="1.5" fill="#0e0e10"/>
        </svg>
        Albion P2P Trade
    </a>

    <ul class="nav-links">
        <li><a href="#features">Funcionalidades</a></li>
        <li><a href="#como-funciona">Como funciona</a></li>
        <li><a href="#cta">Comunidade</a></li>
    </ul>

    <div class="nav-auth">
        <?php if ($logado): ?>
            <span class="nav-user">⚔ <?= $userNick ?></span>
            <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn-outline" style="padding:8px 16px;font-size:.875rem">Marketplace</a>
            <a href="<?= BASE_URL ?>/app/actions/logout.php" class="nav-btn-ghost">Sair</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/auth/login.php" class="nav-btn-ghost">Entrar</a>
            <a href="<?= BASE_URL ?>/pages/auth/cadastro.php" class="btn btn-primary" style="padding:9px 18px;font-size:.875rem">Criar conta</a>
        <?php endif; ?>
    </div>

    <button id="nav-hamburger" onclick="toggleMobileMenu()" aria-label="Abrir menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- ══ DRAWER MOBILE ══════════════════════════════════════════ -->
<div id="nav-overlay" onclick="closeMobileMenu()"></div>
<div id="nav-drawer" aria-hidden="true">
    <div class="drawer-inner">
        <div class="drawer-header">
            <a href="index.php" class="drawer-logo">
                <svg width="22" height="22" viewBox="0 0 30 30" fill="none">
                    <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
                    <polygon points="15,7 23,11.5 23,18.5 15,23 7,18.5 7,11.5" fill="rgba(201,168,76,.1)" stroke="#c9a84c" stroke-width="1"/>
                    <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
                </svg>
                Albion P2P Trade
            </a>
            <button class="drawer-close" onclick="closeMobileMenu()" aria-label="Fechar menu">✕</button>
        </div>

        <ul class="drawer-nav">
            <li><a href="#features" onclick="closeMobileMenu()">Funcionalidades</a></li>
            <li><a href="#como-funciona" onclick="closeMobileMenu()">Como funciona</a></li>
            <li><a href="#cta" onclick="closeMobileMenu()">Comunidade</a></li>
        </ul>

        <div class="drawer-auth">
            <?php if ($logado): ?>
                <div class="drawer-user">⚔ <?= $userNick ?></div>
                <a href="<?= BASE_URL ?>/pages/dashboard.php" class="drawer-btn drawer-btn-primary">Marketplace</a>
                <a href="<?= BASE_URL ?>/app/actions/logout.php" class="drawer-btn drawer-btn-ghost">Sair</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/auth/cadastro.php" class="drawer-btn drawer-btn-primary">Criar conta gratuita</a>
                <a href="<?= BASE_URL ?>/pages/auth/login.php" class="drawer-btn drawer-btn-ghost">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ HERO ═══════════════════════════════════════════════════ -->
<section class="hero">
    <canvas id="hero-canvas"></canvas>
    <div class="hero-bg-radial"></div>
    <div class="hero-grid"></div>

    <div class="hero-hex-deco">
        <svg width="700" height="700" viewBox="0 0 700 700" fill="none">
            <polygon points="350,20 660,185 660,515 350,680 40,515 40,185"
                     fill="none" stroke="#c9a84c" stroke-width="1"/>
            <polygon points="350,80 600,215 600,485 350,620 100,485 100,215"
                     fill="none" stroke="#c9a84c" stroke-width="0.5" stroke-dasharray="10 20"/>
            <polygon points="350,140 540,245 540,455 350,560 160,455 160,245"
                     fill="none" stroke="#c9a84c" stroke-width="0.5"/>
        </svg>
    </div>

    <div class="hero-content">
        <div class="hero-badge">
            <span class="hero-badge-dot"></span>
            ⚔&nbsp; Plataforma P2P · Albion Online
        </div>

        <h1 class="hero-title">Comércio seguro<br>entre aventureiros</h1>

        <p class="hero-subtitle">
            Compre, venda e negocie itens diretamente com outros jogadores.
            Sem intermediários, sem taxas abusivas — apenas trocas justas
            protegidas por escrow.
        </p>

        <div class="hero-cta">
            <a href="<?= BASE_URL ?>/pages/auth/cadastro.php" class="btn btn-primary btn-lg">
                ⚔&nbsp; Começar agora
            </a>
            <a href="#como-funciona" class="btn btn-outline btn-lg">
                Como funciona
            </a>
        </div>

        <div class="hero-divider"></div>

        <div class="hero-stats">
            <div class="hero-stat">
                <span class="stat-num" data-count="<?= (int)$stats['total_usuarios'] ?>"><?= fmtStat((int)$stats['total_usuarios']) ?></span>
                <span class="stat-label">Aventureiros</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat">
                <span class="stat-num" data-count="<?= (int)$stats['anuncios_ativos'] ?>"><?= fmtStat((int)$stats['anuncios_ativos']) ?></span>
                <span class="stat-label">Anúncios ativos</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat">
                <span class="stat-num" data-count="<?= (int)$stats['total_anuncios'] ?>"><?= fmtStat((int)$stats['total_anuncios']) ?></span>
                <span class="stat-label">Trades publicados</span>
            </div>
        </div>
    </div>

    <a href="#features"
       class="hero-scroll"
       onclick="document.getElementById('features').scrollIntoView({behavior:'smooth'});return false;">
        <div class="scroll-chevron">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
        Explorar
    </a>
</section>

<!-- ══ TRUST STRIP ════════════════════════════════════════════ -->
<div class="trust-strip">
    <div class="trust-inner">
        <div class="trust-item">
            <div class="trust-icon">🛡</div>
            <strong>Escrow integrado</strong>
        </div>
        <div class="trust-item">
            <div class="trust-icon">⚡</div>
            <strong>Negociação em tempo real</strong>
        </div>
        <div class="trust-item">
            <div class="trust-icon">⭐</div>
            <strong>Sistema de reputação</strong>
        </div>
        <div class="trust-item">
            <div class="trust-icon">🔔</div>
            <strong>Alertas de preço</strong>
        </div>
        <div class="trust-item">
            <div class="trust-icon">🌍</div>
            <strong>Multi-servidor</strong>
        </div>
    </div>
</div>

<!-- ══ FEATURES ══════════════════════════════════════════════ -->
<section id="features" class="section">
    <div class="section-inner">
        <div class="section-header" data-anim="fade">
            <div class="section-tag">⚔ Por que escolher</div>
            <h2 class="section-title">Tudo que você precisa para <span>negociar</span></h2>
            <p class="section-desc">Uma plataforma pensada por jogadores para jogadores, com ferramentas que tornam cada trade mais rápido e seguro.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card" data-anim>
                <div class="feature-card-num">01</div>
                <div class="feature-icon-wrap">🛡</div>
                <h3 class="feature-title">Trades com Escrow</h3>
                <p class="feature-desc">Nenhuma das partes recebe antes de confirmar o acordo. O sistema retém os itens até que ambos validem a transação.</p>
            </div>
            <div class="feature-card" data-anim style="transition-delay:.07s">
                <div class="feature-card-num">02</div>
                <div class="feature-icon-wrap">⚡</div>
                <h3 class="feature-title">Negociações Rápidas</h3>
                <p class="feature-desc">Filtros por tier, encantamento, servidor e faixa de preço. Encontre a oferta certa em segundos.</p>
            </div>
            <div class="feature-card" data-anim style="transition-delay:.14s">
                <div class="feature-card-num">03</div>
                <div class="feature-icon-wrap">💰</div>
                <h3 class="feature-title">Taxa Mínima</h3>
                <p class="feature-desc">Cobramos apenas o essencial para manter a plataforma. Mais lucro fica com você, onde deveria estar.</p>
            </div>
            <div class="feature-card" data-anim style="transition-delay:.21s">
                <div class="feature-card-num">04</div>
                <div class="feature-icon-wrap">⭐</div>
                <h3 class="feature-title">Reputação Pública</h3>
                <p class="feature-desc">Cada trade gera avaliações visíveis. Construa sua reputação e atraia parceiros de negócios confiáveis.</p>
            </div>
            <div class="feature-card" data-anim style="transition-delay:.28s">
                <div class="feature-card-num">05</div>
                <div class="feature-icon-wrap">🔔</div>
                <h3 class="feature-title">Alertas em Tempo Real</h3>
                <p class="feature-desc">Configure alertas de preço e seja notificado quando um item de interesse entrar no mercado.</p>
            </div>
            <div class="feature-card" data-anim style="transition-delay:.35s">
                <div class="feature-card-num">06</div>
                <div class="feature-icon-wrap">🌍</div>
                <h3 class="feature-title">Multi-Servidor</h3>
                <p class="feature-desc">Suporte a Americas, Europa, Ásia. Negocie com qualquer jogador, em qualquer região.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ ORNAMENT ═══════════════════════════════════════════════ -->
<div class="ornament-divider">
    <div class="ornament-line"></div>
    <div class="ornament-hex"></div>
    <div class="ornament-line"></div>
    <div class="ornament-hex"></div>
    <div class="ornament-line"></div>
</div>

<!-- ══ COMO FUNCIONA ══════════════════════════════════════════ -->
<section id="como-funciona" class="section">
    <div class="section-inner">
        <div class="section-header" data-anim="fade">
            <div class="section-tag">📜 Passo a passo</div>
            <h2 class="section-title">Como <span>funciona</span></h2>
            <p class="section-desc">Do cadastro ao trade concluído em poucos minutos.</p>
        </div>

        <div class="steps-container">
            <div class="step-item" data-anim="left">
                <div class="step-num-wrap">
                    <div class="step-num">1</div>
                </div>
                <div class="step-body">
                    <h3 class="step-title">Crie sua conta gratuita</h3>
                    <p class="step-desc">Cadastre-se com e-mail e seu nick do Albion Online. Confirme o e-mail e acesse o painel imediatamente.</p>
                </div>
            </div>
            <div class="step-item" data-anim="left" style="transition-delay:.1s">
                <div class="step-num-wrap">
                    <div class="step-num">2</div>
                </div>
                <div class="step-body">
                    <h3 class="step-title">Publique ou encontre ofertas</h3>
                    <p class="step-desc">Liste os itens que deseja vender com foto, preço e servidor. Ou use a busca avançada para encontrar o que procura.</p>
                </div>
            </div>
            <div class="step-item" data-anim="left" style="transition-delay:.2s">
                <div class="step-num-wrap">
                    <div class="step-num">3</div>
                </div>
                <div class="step-body">
                    <h3 class="step-title">Negocie pelo chat interno</h3>
                    <p class="step-desc">Converse diretamente com o outro jogador, acerte os detalhes e inicie o processo de escrow com um clique.</p>
                </div>
            </div>
            <div class="step-item" data-anim="left" style="transition-delay:.3s">
                <div class="step-num-wrap">
                    <div class="step-num">4</div>
                </div>
                <div class="step-body">
                    <h3 class="step-title">Confirme e avalie</h3>
                    <p class="step-desc">Após a troca in-game, ambos confirmam o recebimento. O escrow é liberado e você pode deixar sua avaliação.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ ORNAMENT ═══════════════════════════════════════════════ -->
<div class="ornament-divider">
    <div class="ornament-line"></div>
    <div class="ornament-hex"></div>
    <div class="ornament-line"></div>
    <div class="ornament-hex"></div>
    <div class="ornament-line"></div>
</div>

<!-- ══ CTA ════════════════════════════════════════════════════ -->
<section id="cta" class="section">
    <div class="cta-glow-left"></div>
    <div class="cta-glow-right"></div>

    <div class="cta-inner" data-anim="fade">
        <div class="cta-box">
            <div class="cta-corner cta-corner-tl"></div>
            <div class="cta-corner cta-corner-tr"></div>
            <div class="cta-corner cta-corner-bl"></div>
            <div class="cta-corner cta-corner-br"></div>

            <div class="cta-badge">⚔&nbsp; Junte-se à guilda</div>
            <h2 class="cta-title">Pronto para negociar<br>com confiança?</h2>
            <p class="cta-desc">Mais de 1.200 aventureiros já confiam no Albion P2P Trade. Sua conta é gratuita e fica pronta em minutos.</p>
            <div class="cta-actions">
                <a href="<?= BASE_URL ?>/pages/auth/cadastro.php" class="btn btn-primary btn-lg">Criar conta gratuita</a>
                <a href="<?= BASE_URL ?>/pages/auth/login.php" class="btn btn-outline btn-lg">Já tenho conta</a>
            </div>
        </div>
    </div>
</section>

<!-- ══ FOOTER ══════════════════════════════════════════════════ -->
<footer>
    <div class="footer-inner">
        <a href="index.php" class="footer-logo">
            <svg width="18" height="18" viewBox="0 0 30 30" fill="none">
                <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
                <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
            </svg>
            Albion P2P Trade
        </a>
        <div class="footer-disclaimer">Projeto comunitário — não afiliado à Sandbox Interactive GmbH</div>
        <div class="footer-copy">© <?= date('Y') ?> Albion P2P Trade</div>
    </div>
</footer>

<script>
/* ── Navbar scroll ──────────────────────────────────────────── */
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 40);
}, { passive: true });

/* ── Scroll animations ──────────────────────────────────────── */
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('in'); observer.unobserve(e.target); }
    });
}, { threshold: 0.12 });
document.querySelectorAll('[data-anim]').forEach(el => observer.observe(el));

/* ── Mobile menu ────────────────────────────────────────────── */
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
    const btn = document.getElementById('nav-hamburger');
    btn.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
    document.getElementById('nav-drawer').setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMobileMenu(); });

/* ── Hero particle canvas ───────────────────────────────────── */
(function () {
    const canvas = document.getElementById('hero-canvas');
    const ctx    = canvas.getContext('2d');
    let W, H, particles = [], raf;

    function resize() {
        W = canvas.width  = canvas.offsetWidth;
        H = canvas.height = canvas.offsetHeight;
    }

    function spawn() {
        particles = [];
        const count = Math.min(120, Math.floor((W * H) / 9000));
        for (let i = 0; i < count; i++) {
            particles.push({
                x:   Math.random() * W,
                y:   Math.random() * H,
                r:   Math.random() * 1.4 + 0.3,
                vx:  (Math.random() - 0.5) * 0.14,
                vy:  (Math.random() - 0.5) * 0.1,
                o:   Math.random() * 0.45 + 0.08,
                phi: Math.random() * Math.PI * 2,
                spd: 0.006 + Math.random() * 0.009,
                gold: Math.random() < 0.28,
            });
        }
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        particles.forEach(p => {
            p.x  += p.vx;
            p.y  += p.vy;
            p.phi += p.spd;
            if (p.x < 0) p.x = W;
            else if (p.x > W) p.x = 0;
            if (p.y < 0) p.y = H;
            else if (p.y > H) p.y = 0;
            const a = p.o * (0.55 + 0.45 * Math.sin(p.phi));
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = p.gold
                ? `rgba(201,168,76,${a})`
                : `rgba(220,210,200,${a * 0.45})`;
            ctx.fill();
        });
        raf = requestAnimationFrame(draw);
    }

    function init() {
        resize(); spawn();
        cancelAnimationFrame(raf);
        draw();
    }

    window.addEventListener('resize', init, { passive: true });
    init();
})();

/* ── Stats counter animation ────────────────────────────────── */
(function () {
    const els = document.querySelectorAll('.stat-num[data-count]');
    if (!els.length) return;

    const fmt = n => n >= 1000 ? (n / 1000).toFixed(1) + 'k+' : (n > 0 ? n + '+' : '0');

    const io = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const el     = e.target;
            const target = parseInt(el.dataset.count, 10) || 0;
            const t0     = performance.now();
            const dur    = 1500;
            (function step(now) {
                const p = Math.min((now - t0) / dur, 1);
                el.textContent = fmt(Math.round(target * (1 - Math.pow(1 - p, 3))));
                if (p < 1) requestAnimationFrame(step);
            })(t0);
            io.unobserve(el);
        });
    }, { threshold: 0.5 });

    els.forEach(el => io.observe(el));
})();
</script>
</body>
</html>
