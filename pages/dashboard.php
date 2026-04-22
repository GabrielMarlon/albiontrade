<?php
/**
 * Albion P2P Trade — Dashboard / Marketplace
 */
require_once __DIR__ . '/../app/config/auth.php';
iniciar_sessao();
exigir_login();

$logado   = isset($_SESSION['user_id']);
$userNick = htmlspecialchars($_SESSION['user_nick'] ?? '');
$flash    = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ── Filtros via GET ───────────────────────────────────────────────────────
$tipo         = in_array($_GET['tipo']        ?? '', ['venda','compra']) ? $_GET['tipo'] : '';
$categoria    = $_GET['categoria']   ?? '';
$grau         = isset($_GET['grau'])         && ctype_digit($_GET['grau'])         ? (int)$_GET['grau']         : 0;
$encantamento = isset($_GET['encantamento']) && ctype_digit($_GET['encantamento']) && (int)$_GET['encantamento'] <= 4 ? (int)$_GET['encantamento'] : -1;
$qualidade    = $_GET['qualidade']   ?? '';
$servidor     = in_array($_GET['servidor'] ?? '', ['americas','europe','asia']) ? $_GET['servidor'] : '';
$cidade       = $_GET['cidade']      ?? '';
$busca        = trim($_GET['busca']  ?? '');
$ordem        = in_array($_GET['ordem'] ?? '', ['preco_asc','preco_desc','recente']) ? $_GET['ordem'] : 'recente';
$pagina       = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina   = 12;

require_once __DIR__ . '/../app/config/categorias.php';

$qualidades_validas = ['normal','bom','excepcional','excelente','obra-prima'];
$cidades_validas    = ['caerleon','bridgewatch','fort sterling','lymhurst','martlock','thetford','brecilien'];

$subcategoria = $_GET['subcategoria'] ?? '';
$item_nome    = $_GET['item_nome']    ?? '';
if (!array_key_exists($categoria, $categorias_hierarquia)) { $categoria = ''; $subcategoria = ''; $item_nome = ''; }
if ($categoria && !array_key_exists($subcategoria, $categorias_hierarquia[$categoria][2])) { $subcategoria = ''; $item_nome = ''; }
if ($subcategoria && $item_nome && !array_key_exists($item_nome, $categorias_hierarquia[$categoria][2][$subcategoria][1])) $item_nome = '';
if (!in_array($qualidade, $qualidades_validas)) $qualidade = '';
if (!in_array($cidade,    $cidades_validas))    $cidade    = '';

// ── Consulta ──────────────────────────────────────────────────────────────
try {
    $pdo = db();
    $pdo->exec("UPDATE anuncios SET ativo = 0 WHERE ativo = 1 AND expira_em <= NOW()");

    $where  = ['a.ativo = 1', 'a.expira_em > NOW()'];
    $params = [];

    if ($tipo)         { $where[] = 'a.tipo = :tipo';            $params[':tipo']      = $tipo; }
    if ($categoria)    { $where[] = 'a.categoria = :categoria';  $params[':categoria'] = $categoria; }
    if ($subcategoria) { $where[] = 'a.subcategoria = :sub';     $params[':sub']       = $subcategoria; }
    if ($item_nome)    { $where[] = 'a.item_nome = :item_nome';  $params[':item_nome'] = $item_nome; }
    if ($grau > 0)     { $where[] = 'a.grau = :grau';            $params[':grau']      = $grau; }
    if ($encantamento >= 0) { $where[] = 'a.encantamento = :enc'; $params[':enc']      = $encantamento; }
    if ($qualidade)    { $where[] = 'a.qualidade = :qualidade';  $params[':qualidade'] = $qualidade; }
    if ($servidor)     { $where[] = 'a.servidor = :servidor';    $params[':servidor']  = $servidor; }
    if ($cidade)       { $where[] = 'a.cidade = :cidade';        $params[':cidade']    = $cidade; }
    if ($busca !== '') { $where[] = 'a.titulo LIKE :busca';      $params[':busca']     = '%' . $busca . '%'; }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);
    $orderSQL = match($ordem) {
        'preco_asc'  => 'a.preco ASC',
        'preco_desc' => 'a.preco DESC',
        default      => 'a.criado_em DESC',
    };

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM anuncios a $whereSQL");
    $stmtCount->execute($params);
    $total   = (int) $stmtCount->fetchColumn();
    $paginas = max(1, (int) ceil($total / $por_pagina));
    $pagina  = min($pagina, $paginas);
    $offset  = ($pagina - 1) * $por_pagina;

    $stmt = $pdo->prepare("
        SELECT a.id, a.tipo, a.titulo, a.categoria, a.grau, a.encantamento,
               a.qualidade, a.preco, a.servidor, a.cidade, a.criado_em, a.expira_em,
               DATEDIFF(a.expira_em, NOW()) AS dias_restantes,
               u.nick AS vendedor_nick
        FROM   anuncios a
        JOIN   usuarios u ON u.id = a.usuario_id
        $whereSQL
        ORDER BY $orderSQL
        LIMIT  :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,     PDO::PARAM_INT);
    $stmt->execute();
    $anuncios = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('[albiontrade] dashboard: ' . $e->getMessage());
    $anuncios = []; $total = 0; $paginas = 1;
}

// ── Helpers ───────────────────────────────────────────────────────────────
function formatarPreco(int $v): string {
    if ($v >= 1_000_000) return number_format($v / 1_000_000, 1, ',', '.') . 'M';
    if ($v >= 1_000)     return number_format($v / 1_000,     1, ',', '.') . 'K';
    return number_format($v, 0, ',', '.');
}

$icones_categoria = array_map(fn($h) => $h[0], $categorias_hierarquia);

$label_qualidade = [
    'normal'      => ['Normal',    'qual-normal'],
    'bom'         => ['Bom',       'qual-bom'],
    'excepcional' => ['Excepcional','qual-excepcional'],
    'excelente'   => ['Excelente', 'qual-excelente'],
    'obra-prima'  => ['Obra-Prima','qual-obra-prima'],
];

function qstr(array $over = []): string {
    $base = [
        'tipo'         => $_GET['tipo']         ?? '',
        'categoria'    => $_GET['categoria']    ?? '',
        'subcategoria' => $_GET['subcategoria'] ?? '',
        'item_nome'    => $_GET['item_nome']    ?? '',
        'grau'         => $_GET['grau']         ?? '',
        'encantamento' => $_GET['encantamento'] ?? '',
        'qualidade'    => $_GET['qualidade']    ?? '',
        'servidor'     => $_GET['servidor']     ?? '',
        'cidade'       => $_GET['cidade']       ?? '',
        'busca'        => $_GET['busca']        ?? '',
        'ordem'        => $_GET['ordem']        ?? '',
    ];
    $merged  = array_merge($base, $over);
    $filtered = array_filter($merged, fn($v) => $v !== '' && $v !== null);
    return $filtered ? '?' . http_build_query($filtered) : '';
}

// ── Closure renderCards ───────────────────────────────────────────────────
$renderCards = function() use ($anuncios, $total, $paginas, $pagina, $logado, $label_qualidade, $icones_categoria) {
    ob_start(); ?>
    <?php if (empty($anuncios)): ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>Nenhum anúncio encontrado</h3>
        <p>Tente ajustar os filtros ou a busca para encontrar o que procura.</p>
        <?php if ($logado): ?>
        <a href="<?= BASE_URL ?>/pages/anuncio_novo.php" class="empty-cta">
            + Criar primeiro anúncio
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="ads-grid">
        <?php foreach ($anuncios as $ad):
            $tierLabel    = 'T' . $ad['grau'] . ($ad['encantamento'] > 0 ? '.' . $ad['encantamento'] : '');
            $qualData     = $label_qualidade[$ad['qualidade']] ?? ['?', ''];
            $iconeCateg   = $icones_categoria[$ad['categoria']] ?? '📦';
            $dataFormatada= date('d/m/y', strtotime($ad['criado_em']));
            $diasRest     = (int)$ad['dias_restantes'];
            $expiryClass  = $diasRest <= 5 ? 'expiry-urgent' : ($diasRest <= 15 ? 'expiry-warn' : 'expiry-ok');
            $expiryLabel  = $diasRest <= 0 ? 'expirando' : ($diasRest === 1 ? '1 dia' : $diasRest . ' dias');
            $tipoClass    = $ad['tipo'] === 'venda' ? 'badge-venda' : 'badge-compra';
            $tipoLabel    = $ad['tipo'] === 'venda' ? '↑ Venda' : '↓ Compra';
        ?>
        <article class="ad-card">
            <div class="card-head">
                <div class="card-icon-wrap"><?= $iconeCateg ?></div>
                <div class="card-badges">
                    <span class="badge-tipo <?= $tipoClass ?>"><?= $tipoLabel ?></span>
                    <span class="badge-tier"><?= $tierLabel ?></span>
                </div>
            </div>

            <div class="card-body">
                <h3 class="card-title"><?= htmlspecialchars($ad['titulo']) ?></h3>
                <div class="card-meta">
                    <span class="meta-chip">
                        <span class="meta-key">Cat.</span>
                        <span class="meta-val"><?= ucfirst($ad['categoria']) ?></span>
                    </span>
                    <span class="meta-chip meta-qual-<?= $qualData[1] ?>">
                        <span class="meta-key">Qual.</span>
                        <span class="meta-val"><?= $qualData[0] ?></span>
                    </span>
                    <span class="meta-chip">
                        <span class="meta-key">Srv.</span>
                        <span class="meta-val"><?= ucfirst($ad['servidor']) ?></span>
                    </span>
                    <?php if ($ad['cidade']): ?>
                    <span class="meta-chip">
                        <span class="meta-key">Cidade</span>
                        <span class="meta-val"><?= ucwords($ad['cidade']) ?></span>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-foot">
                <div class="card-price">
                    <span class="price-value"><?= formatarPreco((int)$ad['preco']) ?></span>
                    <span class="price-unit">prata</span>
                </div>
                <div class="card-info">
                    <span class="info-nick">⚔ <?= htmlspecialchars($ad['vendedor_nick']) ?></span>
                    <div class="info-row">
                        <span class="info-expiry <?= $expiryClass ?>"><?= $expiryLabel ?></span>
                        <span class="info-date"><?= $dataFormatada ?></span>
                    </div>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/pages/anuncio.php?id=<?= $ad['id'] ?>"
               class="card-link" aria-label="Ver anúncio: <?= htmlspecialchars($ad['titulo']) ?>"></a>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($paginas > 1): ?>
    <nav class="pagination" aria-label="Paginação">
        <?php if ($pagina > 1): ?>
        <a class="page-btn" href="dashboard.php<?= qstr(['pagina' => $pagina - 1]) ?>" aria-label="Anterior">‹</a>
        <?php endif; ?>
        <?php
        $range = range(max(1, $pagina - 2), min($paginas, $pagina + 2));
        if (!in_array(1, $range)) {
            echo '<a class="page-btn" href="dashboard.php' . qstr(['pagina' => 1]) . '">1</a>';
            if ($range[0] > 2) echo '<span class="page-ellipsis">…</span>';
        }
        foreach ($range as $p):
            $isCurrent = $p === $pagina;
        ?>
        <a class="page-btn<?= $isCurrent ? ' active' : '' ?>"
           href="dashboard.php<?= qstr(['pagina' => $p]) ?>"
           <?= $isCurrent ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php endforeach; ?>
        <?php
        if (!in_array($paginas, $range)) {
            if (end($range) < $paginas - 1) echo '<span class="page-ellipsis">…</span>';
            echo '<a class="page-btn" href="dashboard.php' . qstr(['pagina' => $paginas]) . '">' . $paginas . '</a>';
        } ?>
        <?php if ($pagina < $paginas): ?>
        <a class="page-btn" href="dashboard.php<?= qstr(['pagina' => $pagina + 1]) ?>" aria-label="Próxima">›</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif;
    return ob_get_clean();
};

// ── AJAX ──────────────────────────────────────────────────────────────────
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['count' => $total, 'html' => $renderCards()]);
    exit;
}

// ── Filtros ativos (para badge e botão limpar) ─────────────────────────────
$ativos = array_filter([$tipo, $categoria, $subcategoria, $item_nome,
    $grau ?: '', $encantamento >= 0 ? $encantamento : '', $qualidade, $servidor, $cidade]);
$nAtivos = count($ativos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace — Albion P2P Trade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>

<?php if ($flash):
    $isSuccess  = $flash['type'] === 'success';
    $toastIcon  = $isSuccess ? '✦' : '⚠';
    $toastClass = $isSuccess ? 'success' : 'error';
    $toastTitle = $isSuccess ? 'Sucesso' : 'Atenção';
?>
<div id="flash-toast">
    <span class="toast-icon <?= $toastClass ?>"><?= $toastIcon ?></span>
    <div class="toast-body">
        <div class="toast-title"><?= $toastTitle ?></div>
        <div class="toast-message"><?= htmlspecialchars($flash['msg']) ?></div>
    </div>
    <button class="toast-close" onclick="this.closest('#flash-toast').remove()" aria-label="Fechar">✕</button>
</div>
<script>setTimeout(() => document.getElementById('flash-toast')?.remove(), 5000);</script>
<?php endif; ?>

<!-- ══ NAVBAR ══════════════════════════════════════════════════════════════ -->
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
        <li><a href="dashboard.php">Marketplace</a></li>
        <?php if ($logado): ?>
        <li><a href="<?= BASE_URL ?>/pages/meus-anuncios.php">Meus Anúncios</a></li>
        <li><a href="<?= BASE_URL ?>/pages/negociacoes.php">Negociações</a></li>
        <?php endif; ?>
    </ul>

    <div class="nav-auth">
        <?php if ($logado): ?>
            <a href="<?= BASE_URL ?>/pages/anuncio_novo.php" class="nav-btn-primary">+ Anunciar</a>
            <span class="nav-user">⚔ <?= $userNick ?></span>
            <a href="<?= BASE_URL ?>/app/actions/logout.php" class="nav-btn-ghost">Sair</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/auth/login.php"    class="nav-btn-ghost">Entrar</a>
            <a href="<?= BASE_URL ?>/pages/auth/cadastro.php" class="nav-btn-primary">Criar conta</a>
        <?php endif; ?>
    </div>

    <button id="nav-hamburger" onclick="toggleMobileMenu()" aria-label="Abrir menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- ══ DRAWER MOBILE ════════════════════════════════════════════════════════ -->
<div id="nav-overlay" onclick="closeMobileMenu()"></div>
<div id="filter-overlay" onclick="closeSidebar()"></div>
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
            <li><a href="dashboard.php">Marketplace</a></li>
            <?php if ($logado): ?>
            <li><a href="<?= BASE_URL ?>/pages/meus-anuncios.php">Meus Anúncios</a></li>
            <li><a href="<?= BASE_URL ?>/pages/negociacoes.php">Negociações</a></li>
            <?php endif; ?>
        </ul>

        <div class="drawer-auth">
            <?php if ($logado): ?>
                <div class="drawer-user">⚔ <?= $userNick ?></div>
                <a href="<?= BASE_URL ?>/pages/anuncio_novo.php" class="drawer-btn drawer-btn-primary">+ Anunciar</a>
                <a href="<?= BASE_URL ?>/app/actions/logout.php" class="drawer-btn drawer-btn-ghost">Sair</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/auth/login.php"    class="drawer-btn drawer-btn-ghost">Entrar</a>
                <a href="<?= BASE_URL ?>/pages/auth/cadastro.php" class="drawer-btn drawer-btn-primary">Criar conta</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ DASHBOARD LAYOUT ════════════════════════════════════════════════════ -->
<div class="dash-layout">

    <!-- ── Sidebar filtros ─────────────────────────────────────────────── -->
    <aside class="dash-sidebar" id="sidebar">

        <div class="sidebar-head">
            <div class="sidebar-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                </svg>
                Filtros
                <?php if ($nAtivos): ?>
                <span class="filter-count"><?= $nAtivos ?></span>
                <?php endif; ?>
            </div>
            <a href="dashboard.php" id="btn-clear" class="sidebar-clear"<?= !$nAtivos ? ' hidden' : '' ?>>Limpar tudo</a>
        </div>

        <form id="filter-form" method="GET" action="dashboard.php">
            <input type="hidden" name="busca" id="busca-hidden" value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="ordem" value="<?= htmlspecialchars($ordem) ?>">

            <!-- ── Tipo ── -->
            <div class="filter-group">
                <div class="filter-label">Tipo de anúncio</div>
                <div class="filter-opts-row">
                    <?php foreach (['' => 'Todos', 'venda' => '↑ Venda', 'compra' => '↓ Compra'] as $v => $l): ?>
                    <label class="filter-opt<?= $tipo === $v ? ' active' : '' ?>">
                        <input type="radio" name="tipo" value="<?= $v ?>" <?= $tipo === $v ? 'checked' : '' ?>>
                        <?= $l ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-sep"></div>

            <!-- ── Categoria ── -->
            <div class="filter-group">
                <div class="filter-label">
                    Categoria
                    <?php if ($categoria): ?>
                    <span class="filter-label-badge"><?= $categorias_hierarquia[$categoria][1] ?></span>
                    <?php endif; ?>
                </div>

                <div class="filter-opts-categ">
                    <label class="filter-opt-categ<?= $categoria === '' ? ' active' : '' ?>">
                        <input type="radio" name="categoria" value="" <?= $categoria === '' ? 'checked' : '' ?>>
                        <span class="categ-icon">◈</span>
                        <span>Todas</span>
                    </label>
                    <?php foreach ($categorias_hierarquia as $ck => [$ci, $cl, $subs]): ?>
                    <label class="filter-opt-categ<?= $categoria === $ck ? ' active' : '' ?>">
                        <input type="radio" name="categoria" value="<?= $ck ?>" <?= $categoria === $ck ? 'checked' : '' ?>>
                        <span class="categ-icon"><?= $ci ?></span>
                        <span><?= $cl ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Sub-categorias -->
                <?php foreach ($categorias_hierarquia as $ck => [$ci, $cl, $subs]): ?>
                <div class="categ-sub-panel" data-cat="<?= $ck ?>"<?= $categoria !== $ck ? ' hidden' : '' ?>>
                    <div class="categ-sub-label"><?= $cl ?>:</div>
                    <div class="filter-opts-col">
                        <label class="filter-opt<?= ($categoria === $ck && $subcategoria === '') ? ' active' : '' ?>">
                            <input type="radio" name="subcategoria" value=""
                                   <?= ($categoria === $ck && $subcategoria === '') ? 'checked' : '' ?>>
                            Todos
                        </label>
                        <?php foreach ($subs as $sk => [$sl, $sitens]): ?>
                        <label class="filter-opt<?= ($categoria === $ck && $subcategoria === $sk) ? ' active' : '' ?>">
                            <input type="radio" name="subcategoria" value="<?= $sk ?>"
                                   <?= ($categoria === $ck && $subcategoria === $sk) ? 'checked' : '' ?>>
                            <?= $sl ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Itens (3º nível) -->
                <?php foreach ($subs as $sk => [$sl, $sitens]): ?>
                <?php if (!empty($sitens)): ?>
                <div class="categ-item-panel" data-cat="<?= $ck ?>" data-sub="<?= $sk ?>"
                     <?= ($categoria !== $ck || $subcategoria !== $sk) ? 'hidden' : '' ?>>
                    <div class="categ-sub-label"><?= $sl ?>:</div>
                    <div class="filter-opts-col">
                        <label class="filter-opt<?= ($categoria === $ck && $subcategoria === $sk && $item_nome === '') ? ' active' : '' ?>">
                            <input type="radio" name="item_nome" value=""
                                   <?= ($categoria === $ck && $subcategoria === $sk && $item_nome === '') ? 'checked' : '' ?>>
                            Todos
                        </label>
                        <?php foreach ($sitens as $ik => $il): ?>
                        <label class="filter-opt<?= ($categoria === $ck && $subcategoria === $sk && $item_nome === $ik) ? ' active' : '' ?>">
                            <input type="radio" name="item_nome" value="<?= $ik ?>"
                                   <?= ($categoria === $ck && $subcategoria === $sk && $item_nome === $ik) ? 'checked' : '' ?>>
                            <?= is_array($il) ? $il[0] : $il ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <div class="filter-sep"></div>

            <!-- ── Grau ── -->
            <div class="filter-group">
                <div class="filter-label">
                    Grau (Tier)
                    <?php if ($grau > 0): ?><span class="filter-label-badge">T<?= $grau ?></span><?php endif; ?>
                </div>
                <div class="filter-opts-row">
                    <label class="filter-opt-chip<?= $grau === 0 ? ' active' : '' ?>">
                        <input type="radio" name="grau" value="" <?= $grau === 0 ? 'checked' : '' ?>>—
                    </label>
                    <?php for ($t = 1; $t <= 8; $t++): ?>
                    <label class="filter-opt-chip<?= $grau === $t ? ' active' : '' ?>">
                        <input type="radio" name="grau" value="<?= $t ?>" <?= $grau === $t ? 'checked' : '' ?>>
                        <?= $t ?>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="filter-sep"></div>

            <!-- ── Encantamento ── -->
            <div class="filter-group">
                <div class="filter-label">
                    Encantamento
                    <?php if ($encantamento >= 0): ?><span class="filter-label-badge">.<?= $encantamento ?></span><?php endif; ?>
                </div>
                <div class="filter-opts-row">
                    <label class="filter-opt-chip<?= $encantamento === -1 ? ' active' : '' ?>">
                        <input type="radio" name="encantamento" value="" <?= $encantamento === -1 ? 'checked' : '' ?>>
                        Todos
                    </label>
                    <?php for ($e = 0; $e <= 4; $e++): ?>
                    <label class="filter-opt-chip<?= $encantamento === $e ? ' active' : '' ?>">
                        <input type="radio" name="encantamento" value="<?= $e ?>" <?= $encantamento === $e ? 'checked' : '' ?>>
                        .<?= $e ?>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="filter-sep"></div>

            <!-- ── Qualidade ── -->
            <div class="filter-group">
                <div class="filter-label">Qualidade</div>
                <div class="filter-opts-col">
                    <label class="filter-opt-qual<?= $qualidade === '' ? ' active' : '' ?>">
                        <input type="radio" name="qualidade" value="" <?= $qualidade === '' ? 'checked' : '' ?>>
                        <span class="qual-dot"></span>
                        <span>Qualquer</span>
                        <span class="check-icon">✓</span>
                    </label>
                    <?php foreach ($label_qualidade as $qv => [$ql, $qc]): ?>
                    <label class="filter-opt-qual<?= $qualidade === $qv ? ' active' : '' ?>">
                        <input type="radio" name="qualidade" value="<?= $qv ?>" <?= $qualidade === $qv ? 'checked' : '' ?>>
                        <span class="qual-dot <?= $qc ?>"></span>
                        <span><?= $ql ?></span>
                        <span class="check-icon">✓</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-sep"></div>

            <!-- ── Servidor ── -->
            <div class="filter-group">
                <div class="filter-label">Servidor</div>
                <div class="filter-opts-col">
                    <?php foreach (['' => ['Todos','🌐'], 'americas'=>['Américas','🌎'], 'europe'=>['Europa','🌍'], 'asia'=>['Ásia','🌏']] as $k => [$v, $icon]): ?>
                    <label class="filter-opt-loc<?= $servidor === $k ? ' active' : '' ?>">
                        <input type="radio" name="servidor" value="<?= $k ?>" <?= $servidor === $k ? 'checked' : '' ?>>
                        <span class="loc-icon"><?= $icon ?></span>
                        <span><?= $v ?></span>
                        <span class="check-icon">✓</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-sep"></div>

            <!-- ── Cidade ── -->
            <div class="filter-group">
                <div class="filter-label">Cidade</div>
                <div class="filter-opts-col">
                    <label class="filter-opt-loc<?= $cidade === '' ? ' active' : '' ?>">
                        <input type="radio" name="cidade" value="" <?= $cidade === '' ? 'checked' : '' ?>>
                        <span class="loc-icon">◈</span>
                        <span>Todas</span>
                        <span class="check-icon">✓</span>
                    </label>
                    <?php foreach ([
                        'caerleon'      => ['Caerleon',     '⚔', '#c9a84c'],
                        'bridgewatch'   => ['Bridgewatch',  '🏜', '#e67e22'],
                        'fort sterling' => ['Fort Sterling','🏔', '#95a5a6'],
                        'lymhurst'      => ['Lymhurst',     '🌿', '#27ae60'],
                        'martlock'      => ['Martlock',     '❄',  '#5dade2'],
                        'thetford'      => ['Thetford',     '🌑', '#8e44ad'],
                        'brecilien'     => ['Brecilien',    '🌲', '#2ecc71'],
                    ] as $k => [$label, $icon, $color]): ?>
                    <label class="filter-opt-loc<?= $cidade === $k ? ' active' : '' ?>"
                           style="--city-color:<?= $color ?>">
                        <input type="radio" name="cidade" value="<?= $k ?>" <?= $cidade === $k ? 'checked' : '' ?>>
                        <span class="loc-city-dot"></span>
                        <span><?= $icon ?> <?= $label ?></span>
                        <span class="check-icon">✓</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </form>
    </aside>

    <!-- ── Conteúdo principal ──────────────────────────────────────────── -->
    <main class="dash-main">

        <!-- Toolbar: busca + ordenação -->
        <div class="dash-toolbar">
            <form class="search-form" method="GET" action="dashboard.php" id="search-form">
                <?php foreach (['tipo','categoria','subcategoria','item_nome','grau','encantamento','qualidade','servidor','cidade','ordem'] as $f): ?>
                <?php if (isset($_GET[$f]) && $_GET[$f] !== ''): ?>
                <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($_GET[$f]) ?>">
                <?php endif; ?>
                <?php endforeach; ?>
                <span class="search-icon">🔍</span>
                <input type="text" name="busca" class="search-input"
                       placeholder="Buscar por nome do item…"
                       value="<?= htmlspecialchars($busca) ?>"
                       autocomplete="off">
                <?php if ($busca): ?>
                <a href="dashboard.php<?= qstr(['busca' => '']) ?>" class="search-clear" aria-label="Limpar busca">✕</a>
                <?php endif; ?>
            </form>

            <div class="toolbar-meta">
                <span class="result-count" id="result-count">
                    <?= $total ?> anúncio<?= $total !== 1 ? 's' : '' ?>
                </span>

                <select class="sort-select" onchange="changeOrder(this.value)" aria-label="Ordenar por">
                    <option value="recente"    <?= $ordem === 'recente'    ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="preco_asc"  <?= $ordem === 'preco_asc'  ? 'selected' : '' ?>>Menor preço</option>
                    <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço</option>
                </select>

                <button class="btn-filter-toggle" onclick="toggleSidebar()" aria-label="Abrir filtros">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filtros<?php if ($nAtivos): ?> <span class="filter-count"><?= $nAtivos ?></span><?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Tipo tabs -->
        <div class="tipo-tabs">
            <?php
            $tabTipo = $tipo;
            foreach (['' => 'Todos', 'venda' => '↑ Venda', 'compra' => '↓ Compra'] as $tv => $tl):
            ?>
            <a class="tipo-tab<?= $tabTipo === $tv ? ' active' : '' ?>"
               href="dashboard.php<?= qstr(['tipo' => $tv, 'pagina' => '']) ?>">
                <?= $tl ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Grid de cards -->
        <div id="ads-container">
            <?= $renderCards() ?>
        </div>

    </main>
</div>

<!-- ══ FOOTER ══════════════════════════════════════════════════════════════ -->
<footer class="dash-footer">
    <div class="footer-inner">
        <a href="<?= BASE_URL ?>/index.php" class="footer-logo">
            <svg width="16" height="16" viewBox="0 0 30 30" fill="none">
                <polygon points="15,2 28,8.5 28,21.5 15,28 2,21.5 2,8.5" fill="none" stroke="#c9a84c" stroke-width="1.4"/>
                <circle cx="15" cy="15" r="3.5" fill="#c9a84c"/>
            </svg>
            Albion P2P Trade
        </a>
        <span class="footer-disclaimer">Projeto comunitário — não afiliado à Sandbox Interactive GmbH</span>
        <span class="footer-copy">© <?= date('Y') ?> Albion P2P Trade</span>
    </div>
</footer>

<script>
/* ── Navbar scroll ─────────────────────────────────────────────────────── */
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 10);
}, { passive: true });

/* ── Sidebar (filtros mobile) ──────────────────────────────────────────── */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const open = sidebar.classList.toggle('open');
    document.getElementById('filter-overlay').classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('filter-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

/* ── Menu mobile (hamburger drawer) ───────────────────────────────────── */
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
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeMobileMenu(); closeSidebar(); } });

/* ── Estado dos filtros ─────────────────────────────────────────────────── */
const state = {
    tipo:         '<?= addslashes($tipo) ?>',
    categoria:    '<?= addslashes($categoria) ?>',
    subcategoria: '<?= addslashes($subcategoria) ?>',
    item_nome:    '<?= addslashes($item_nome) ?>',
    grau:         '<?= $grau ?: '' ?>',
    encantamento: '<?= $encantamento >= 0 ? $encantamento : '' ?>',
    qualidade:    '<?= addslashes($qualidade) ?>',
    servidor:     '<?= addslashes($servidor) ?>',
    cidade:       '<?= addslashes($cidade) ?>',
    busca:        '<?= addslashes($busca) ?>',
    ordem:        '<?= addslashes($ordem) ?>',
    pagina:       '1',
};

/* ── AJAX ───────────────────────────────────────────────────────────────── */
async function applyFilters(overrides = {}) {
    if (!('pagina' in overrides)) overrides.pagina = '1';
    Object.assign(state, overrides);
    updateClearBtn();

    const params = new URLSearchParams(
        Object.fromEntries(Object.entries(state).filter(([, v]) => v !== '' && v !== null))
    );
    const container = document.getElementById('ads-container');
    container.style.opacity = '0.4';
    container.style.pointerEvents = 'none';

    try {
        const res  = await fetch('dashboard.php?' + params.toString() + '&ajax=1');
        const data = await res.json();
        container.innerHTML = data.html;
        container.style.opacity = '';
        container.style.pointerEvents = '';

        const countEl = document.getElementById('result-count');
        if (countEl) countEl.textContent = data.count + ' anúncio' + (data.count !== 1 ? 's' : '');

        params.delete('ajax');
        history.pushState({}, '', 'dashboard.php' + (params.toString() ? '?' + params.toString() : ''));
    } catch (e) {
        container.style.opacity = '';
        container.style.pointerEvents = '';
        console.error('[dashboard] fetch error:', e);
    }
}

/* ── Botão "Limpar tudo" + badge ────────────────────────────────────────── */
function updateClearBtn() {
    const filterKeys = ['tipo','categoria','subcategoria','item_nome','grau','encantamento','qualidade','servidor','cidade','busca'];
    const activeCount = filterKeys.filter(k => state[k] !== '' && state[k] !== null).length;
    document.getElementById('btn-clear').hidden = activeCount === 0;

    const titleEl = document.querySelector('.sidebar-title');
    let badge = titleEl?.querySelector('.filter-count');
    if (activeCount > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className = 'filter-count'; titleEl.appendChild(badge); }
        badge.textContent = activeCount;
    } else {
        badge?.remove();
    }

    // Atualiza o badge do botão de filtros mobile
    const toggleBtn = document.querySelector('.btn-filter-toggle');
    if (toggleBtn) {
        let tbadge = toggleBtn.querySelector('.filter-count');
        if (activeCount > 0) {
            if (!tbadge) { tbadge = document.createElement('span'); tbadge.className = 'filter-count'; toggleBtn.appendChild(tbadge); }
            tbadge.textContent = activeCount;
        } else {
            tbadge?.remove();
        }
    }
}

/* ── Helpers UI ─────────────────────────────────────────────────────────── */
function setActive(name, value) {
    document.querySelectorAll(`[name="${name}"]`).forEach(r => {
        r.closest('label')?.classList.toggle('active', r.value === value);
    });
}

function showSubPanels(cat) {
    document.querySelectorAll('.categ-sub-panel[data-cat]').forEach(el => {
        el.hidden = el.dataset.cat !== cat;
    });
}

function showItemPanels(cat, sub) {
    document.querySelectorAll('.categ-item-panel[data-cat][data-sub]').forEach(el => {
        el.hidden = !(el.dataset.cat === cat && el.dataset.sub === sub);
    });
}

/* ── Listeners filtros ──────────────────────────────────────────────────── */
document.querySelectorAll('#filter-form input[type="radio"]').forEach(r => {
    r.addEventListener('change', () => {
        if (r.name === 'categoria') {
            document.querySelectorAll('[name="subcategoria"]').forEach(x => { x.checked = false; x.closest('label')?.classList.remove('active'); });
            document.querySelectorAll('[name="item_nome"]').forEach(x => { x.checked = false; x.closest('label')?.classList.remove('active'); });
            const subTodosInput = document.querySelector(`.categ-sub-panel[data-cat="${r.value}"] [name="subcategoria"][value=""]`);
            if (subTodosInput) { subTodosInput.checked = true; subTodosInput.closest('label')?.classList.add('active'); }
            showSubPanels(r.value);
            showItemPanels('', '');
            setActive('categoria', r.value);
            applyFilters({ categoria: r.value, subcategoria: '', item_nome: '' });
        } else if (r.name === 'subcategoria') {
            document.querySelectorAll('[name="item_nome"]').forEach(x => { x.checked = false; x.closest('label')?.classList.remove('active'); });
            const cat = document.querySelector('[name="categoria"]:checked')?.value || '';
            const itemTodosInput = document.querySelector(`.categ-item-panel[data-cat="${cat}"][data-sub="${r.value}"] [name="item_nome"][value=""]`);
            if (itemTodosInput) { itemTodosInput.checked = true; itemTodosInput.closest('label')?.classList.add('active'); }
            showItemPanels(cat, r.value);
            setActive('subcategoria', r.value);
            applyFilters({ subcategoria: r.value, item_nome: '' });
        } else {
            r.closest('label')?.classList.add('active');
            document.querySelectorAll(`[name="${r.name}"]`).forEach(x => {
                if (x !== r) x.closest('label')?.classList.remove('active');
            });
            applyFilters({ [r.name]: r.value });
        }
    });
});

/* ── Tipo tabs ──────────────────────────────────────────────────────────── */
document.querySelector('.tipo-tabs')?.addEventListener('click', e => {
    const tab = e.target.closest('.tipo-tab');
    if (!tab) return;
    e.preventDefault();
    const url  = new URL(tab.href, location.href);
    const tipo = url.searchParams.get('tipo') || '';
    document.querySelectorAll('[name="tipo"]').forEach(r => {
        r.checked = r.value === tipo;
        r.closest('label')?.classList.toggle('active', r.value === tipo);
    });
    document.querySelectorAll('.tipo-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    applyFilters({ tipo });
});

/* ── Paginação (delegada ao container) ─────────────────────────────────── */
document.getElementById('ads-container').addEventListener('click', e => {
    const btn = e.target.closest('.page-btn[href]');
    if (!btn) return;
    e.preventDefault();
    const url    = new URL(btn.href, location.href);
    const pagina = url.searchParams.get('pagina') || '1';
    applyFilters({ pagina });
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

/* ── Ordenação ──────────────────────────────────────────────────────────── */
function changeOrder(val) {
    applyFilters({ ordem: val });
}

/* ── Busca ──────────────────────────────────────────────────────────────── */
document.getElementById('search-form').addEventListener('submit', e => {
    e.preventDefault();
    const busca = e.target.querySelector('[name="busca"]').value.trim();
    document.getElementById('busca-hidden').value = busca;
    applyFilters({ busca });
});

/* ── Limpar tudo ────────────────────────────────────────────────────────── */
document.querySelector('.sidebar-clear')?.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('#filter-form input[type="radio"]').forEach(r => {
        r.checked = false;
        r.closest('label')?.classList.remove('active');
    });
    ['tipo','qualidade','servidor','cidade','encantamento'].forEach(name => {
        const first = document.querySelector(`[name="${name}"][value=""]`);
        if (first) { first.checked = true; first.closest('label')?.classList.add('active'); }
    });
    const grauFirst = document.querySelector('[name="grau"][value=""]');
    if (grauFirst) { grauFirst.checked = true; grauFirst.closest('label')?.classList.add('active'); }
    showSubPanels('');
    showItemPanels('', '');
    document.querySelector('#search-form [name="busca"]').value = '';
    document.getElementById('busca-hidden').value = '';
    applyFilters({ tipo:'', categoria:'', subcategoria:'', item_nome:'', grau:'', encantamento:'', qualidade:'', servidor:'', cidade:'', busca:'' });
});
</script>
</body>
</html>
