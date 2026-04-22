<?php
/**
 * Albion P2P Trade — Criar anúncio
 */
require_once __DIR__ . '/../app/config/auth.php';
iniciar_sessao();
exigir_login();

$userNick = htmlspecialchars($_SESSION['user_nick'] ?? '');
$userId   = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../app/config/categorias.php';
$categorias_validas = array_keys($categorias_hierarquia);
$qualidades_validas = ['normal','bom','excepcional','excelente','obra-prima'];
$servidores_validos = ['americas','europe','asia'];
$cidades_validas    = ['caerleon','bridgewatch','fort sterling','lymhurst','martlock','thetford','brecilien'];

$labels_qualidade = [
    'normal'      => ['Normal',      'qd-normal'],
    'bom'         => ['Bom',         'qd-bom'],
    'excepcional' => ['Excepcional', 'qd-excepcional'],
    'excelente'   => ['Excelente',   'qd-excelente'],
    'obra-prima'  => ['Obra-Prima',  'qd-obra-prima'],
];
$labels_cidade = [
    'caerleon'      => ['Caerleon',      '⚔'],
    'bridgewatch'   => ['Bridgewatch',   '🏜'],
    'fort sterling' => ['Fort Sterling', '🏔'],
    'lymhurst'      => ['Lymhurst',      '🌿'],
    'martlock'      => ['Martlock',      '❄'],
    'thetford'      => ['Thetford',      '🌑'],
    'brecilien'     => ['Brecilien',     '🌲'],
];

$v = [
    'tipo'         => 'venda',
    'titulo'       => '',
    'categoria'    => '',
    'subcategoria' => '',
    'item_nome'    => '',
    'grau'         => 4,
    'encantamento' => 0,
    'qualidade'    => 'normal',
    'preco'        => '',
    'descricao'    => '',
    'servidor'     => $_SESSION['user_servidor'] ?? '',
    'cidade'       => '',
];
$errors = [];

// ── POST ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['tipo']         = $_POST['tipo']         ?? '';
    $v['titulo']       = trim($_POST['titulo']  ?? '');
    $v['categoria']    = $_POST['categoria']    ?? '';
    $v['subcategoria'] = $_POST['subcategoria'] ?? '';
    $v['item_nome']    = $_POST['item_nome']    ?? '';
    $v['grau']         = (int)($_POST['grau']   ?? 0);
    $v['encantamento'] = isset($_POST['encantamento']) && ctype_digit($_POST['encantamento'])
                         ? (int)$_POST['encantamento'] : -1;
    $v['qualidade']    = $_POST['qualidade']    ?? '';
    $v['preco']        = trim($_POST['preco']   ?? '');
    $v['descricao']    = trim($_POST['descricao'] ?? '');
    $v['servidor']     = $_POST['servidor']     ?? '';
    $v['cidade']       = $_POST['cidade']       ?? '';

    if (!in_array($v['tipo'], ['venda','compra'], true))
        $errors['tipo'] = 'Selecione o tipo do anúncio.';
    if (mb_strlen($v['titulo']) < 3)
        $errors['titulo'] = 'Título deve ter pelo menos 3 caracteres.';
    elseif (mb_strlen($v['titulo']) > 120)
        $errors['titulo'] = 'Título deve ter no máximo 120 caracteres.';
    if (!in_array($v['categoria'], $categorias_validas, true)) {
        $errors['categoria'] = 'Selecione uma categoria.';
    } else {
        $subs_validas = array_keys($categorias_hierarquia[$v['categoria']][2]);
        if (!in_array($v['subcategoria'], $subs_validas, true)) { $v['subcategoria'] = ''; $v['item_nome'] = ''; }
        elseif ($v['item_nome']) {
            $itens_validos = array_keys($categorias_hierarquia[$v['categoria']][2][$v['subcategoria']][1]);
            if (!in_array($v['item_nome'], $itens_validos, true)) $v['item_nome'] = '';
        }
    }
    if ($v['grau'] < 1 || $v['grau'] > 8)       $errors['grau'] = 'Selecione um tier (T1–T8).';
    if ($v['encantamento'] < 0 || $v['encantamento'] > 4) $errors['encantamento'] = 'Selecione o encantamento.';
    if (!in_array($v['qualidade'], $qualidades_validas, true)) $errors['qualidade'] = 'Selecione uma qualidade.';
    $preco_num = (int)preg_replace('/[^0-9]/', '', $v['preco']);
    if ($preco_num < 1) $errors['preco'] = 'Informe um preço válido maior que zero.';
    if (!in_array($v['servidor'], $servidores_validos, true)) $errors['servidor'] = 'Selecione um servidor.';
    if ($v['cidade'] !== '' && !in_array($v['cidade'], $cidades_validas, true)) $v['cidade'] = '';
    if (mb_strlen($v['descricao']) > 1000) $errors['descricao'] = 'Descrição deve ter no máximo 1000 caracteres.';

    if (empty($errors)) {
        try {
            $pdo  = db();
            $stmt = $pdo->prepare("
                INSERT INTO anuncios
                    (usuario_id, tipo, titulo, categoria, subcategoria, item_nome, grau, encantamento,
                     qualidade, preco, descricao, servidor, cidade, ativo, expira_em)
                VALUES
                    (:uid, :tipo, :titulo, :categoria, :subcategoria, :item_nome, :grau, :enc,
                     :qualidade, :preco, :descricao, :servidor, :cidade, 1,
                     DATE_ADD(NOW(), INTERVAL 60 DAY))
            ");
            $stmt->execute([
                ':uid'          => $userId,
                ':tipo'         => $v['tipo'],
                ':titulo'       => $v['titulo'],
                ':categoria'    => $v['categoria'],
                ':subcategoria' => $v['subcategoria'] ?: null,
                ':item_nome'    => $v['item_nome']    ?: null,
                ':grau'         => $v['grau'],
                ':enc'          => $v['encantamento'],
                ':qualidade'    => $v['qualidade'],
                ':preco'        => $preco_num,
                ':descricao'    => $v['descricao'] ?: null,
                ':servidor'     => $v['servidor'],
                ':cidade'       => $v['cidade'] ?: null,
            ]);
            $novoId = (int)$pdo->lastInsertId();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Anúncio publicado com sucesso!'];
            header('Location: ' . BASE_URL . '/pages/anuncio.php?id=' . $novoId);
            exit;
        } catch (PDOException $e) {
            error_log('[albiontrade] anuncio_criar: ' . $e->getMessage());
            $errors['_geral'] = 'Erro interno ao publicar o anúncio. Tente novamente.';
        }
    }
}

$esc = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$sel = fn($a, $b): string    => $a === $b ? 'active' : '';
$chk = fn($a, $b): string    => $a == $b  ? 'checked' : '';

// Preview: preço formatado inicial
$pvPreco = '';
if ($v['preco']) {
    $pn = (int)preg_replace('/[^0-9]/', '', $v['preco']);
    $pvPreco = $pn >= 1_000_000 ? number_format($pn/1_000_000,1,',','.') . 'M'
             : ($pn >= 1_000   ? number_format($pn/1_000,1,',','.') . 'K'
             : number_format($pn,0,',','.'));
}
$pvCategLabel = '';
if ($v['categoria']) {
    $pvCategLabel = $categorias_hierarquia[$v['categoria']][1] ?? $v['categoria'];
    if ($v['subcategoria']) {
        $sd = $categorias_hierarquia[$v['categoria']][2][$v['subcategoria']] ?? null;
        if ($sd) {
            $pvCategLabel .= ' › ' . $sd[0];
            if ($v['item_nome'] && isset($sd[1][$v['item_nome']])) {
                $iVal = $sd[1][$v['item_nome']];
                $pvCategLabel .= ' › ' . (is_array($iVal) ? $iVal[0] : $iVal);
            }
        }
    }
}
$snomes = ['americas'=>'Américas','europe'=>'Europa','asia'=>'Ásia'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Anúncio — Albion P2P Trade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/anuncio-novo.css">
</head>
<body>

<!-- ══ FLASH ══════════════════════════════════════════════════════════════ -->
<?php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if ($flash):
    $isSuccess  = $flash['type'] === 'success';
    $toastClass = $isSuccess ? 'success' : 'error';
    $toastIcon  = $isSuccess ? '✦' : '⚠';
    $toastTitle = $isSuccess ? 'Sucesso' : 'Atenção';
?>
<div id="flash-toast">
    <span class="toast-icon <?= $toastClass ?>"><?= $toastIcon ?></span>
    <div class="toast-body">
        <div class="toast-title"><?= $toastTitle ?></div>
        <div class="toast-message"><?= $esc($flash['msg']) ?></div>
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
        <li><a href="<?= BASE_URL ?>/pages/dashboard.php">Marketplace</a></li>
        <li><a href="<?= BASE_URL ?>/pages/meus-anuncios.php">Meus Anúncios</a></li>
        <li><a href="<?= BASE_URL ?>/pages/negociacoes.php">Negociações</a></li>
    </ul>
    <div class="nav-auth">
        <a href="<?= BASE_URL ?>/pages/anuncio_novo.php" class="nav-btn-primary">+ Anunciar</a>
        <span class="nav-user">⚔ <?= $userNick ?></span>
        <a href="<?= BASE_URL ?>/app/actions/logout.php" class="nav-btn-ghost">Sair</a>
    </div>
    <button id="nav-hamburger" onclick="toggleMobileMenu()" aria-label="Abrir menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- ══ DRAWER MOBILE ════════════════════════════════════════════════════════ -->
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
            <li><a href="<?= BASE_URL ?>/pages/meus-anuncios.php">Meus Anúncios</a></li>
            <li><a href="<?= BASE_URL ?>/pages/negociacoes.php">Negociações</a></li>
        </ul>
        <div class="drawer-auth">
            <div class="drawer-user">⚔ <?= $userNick ?></div>
            <a href="<?= BASE_URL ?>/pages/anuncio_novo.php" class="drawer-btn drawer-btn-primary">+ Anunciar</a>
            <a href="<?= BASE_URL ?>/app/actions/logout.php" class="drawer-btn drawer-btn-ghost">Sair</a>
        </div>
    </div>
</div>

<!-- ══ CONTEÚDO ════════════════════════════════════════════════════════════ -->
<div class="novo-wrap">

    <div class="novo-header">
        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="novo-back">← Marketplace</a>
        <h1>Publicar anúncio</h1>
        <p>Preencha os detalhes do item que deseja vender ou comprar</p>
    </div>

    <?php if (!empty($errors['_geral'])): ?>
    <div class="form-error-geral">
        <span>⚠</span>
        <?= $esc($errors['_geral']) ?>
    </div>
    <?php endif; ?>

    <div class="novo-grid">

        <!-- ══ COLUNA FORM ══════════════════════════════════════════════ -->
        <div class="form-col">
        <form id="novo-form" method="POST" action="<?= BASE_URL ?>/pages/anuncio_novo.php" novalidate>

            <!-- ① Tipo ──────────────────────────────────────────────── -->
            <div class="form-card">
                <div class="form-card-head">
                    <span class="form-card-num">1</span>
                    <h2>Tipo de anúncio</h2>
                </div>
                <div class="form-card-body">
                    <div class="tipo-grid">
                        <label class="tipo-big<?= $v['tipo']==='venda' ? ' sel-venda' : '' ?>" id="lbl-venda">
                            <input type="radio" name="tipo" value="venda"
                                   <?= $chk($v['tipo'],'venda') ?> onchange="onTipoChange()">
                            <span class="tipo-icon">↑</span>
                            <span class="tipo-name">Quero vender</span>
                            <span class="tipo-desc">Tenho o item e quero vender</span>
                        </label>
                        <label class="tipo-big<?= $v['tipo']==='compra' ? ' sel-compra' : '' ?>" id="lbl-compra">
                            <input type="radio" name="tipo" value="compra"
                                   <?= $chk($v['tipo'],'compra') ?> onchange="onTipoChange()">
                            <span class="tipo-icon">↓</span>
                            <span class="tipo-name">Quero comprar</span>
                            <span class="tipo-desc">Procuro um item para comprar</span>
                        </label>
                    </div>
                    <?php if (!empty($errors['tipo'])): ?>
                    <span class="field-err show" id="err-tipo"><?= $esc($errors['tipo']) ?></span>
                    <?php else: ?>
                    <span class="field-err" id="err-tipo">Selecione o tipo do anúncio.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ② Dados do item ──────────────────────────────────────── -->
            <div class="form-card">
                <div class="form-card-head">
                    <span class="form-card-num">2</span>
                    <h2>Dados do item</h2>
                </div>
                <div class="form-card-body">

                    <!-- Título -->
                    <div class="fld">
                        <label for="titulo">Título do anúncio</label>
                        <div class="fld-input-wrap">
                            <input class="fld-input with-counter<?= !empty($errors['titulo']) ? ' is-error' : '' ?>"
                                   type="text" id="titulo" name="titulo"
                                   placeholder="Ex.: Claymore T8.3 Excelente — entrega Caerleon"
                                   maxlength="120"
                                   value="<?= $esc($v['titulo']) ?>"
                                   oninput="onTituloInput(this); updatePreview()">
                            <span class="titulo-counter" id="titulo-counter"><?= mb_strlen($v['titulo']) ?>/120</span>
                        </div>
                        <span class="field-err<?= !empty($errors['titulo']) ? ' show' : '' ?>" id="err-titulo">
                            <?= $esc($errors['titulo'] ?? 'Título deve ter entre 3 e 120 caracteres.') ?>
                        </span>
                    </div>

                    <!-- Categoria -->
                    <div class="fld">
                        <label>Categoria</label>
                        <div class="categ-grid">
                            <?php foreach ($categorias_hierarquia as $slug => [$icon, $label, $subs]): ?>
                            <label class="cp-chip<?= $v['categoria']===$slug ? ' active' : '' ?>" id="cp-<?= $slug ?>">
                                <input type="radio" name="categoria" value="<?= $slug ?>"
                                       <?= $chk($v['categoria'], $slug) ?> onchange="onCategChange(); updatePreview()">
                                <span class="cp-icon"><?= $icon ?></span>
                                <span class="cp-label"><?= $label ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- 2º nível: sub-categorias -->
                        <?php foreach ($categorias_hierarquia as $slug => [$icon, $label, $subs]): ?>
                        <div class="categ-sub-panel" id="subpanel-<?= $slug ?>"
                             <?= $v['categoria'] === $slug ? '' : 'style="display:none"' ?>>
                            <div class="sub-panel-label"><?= $label ?>:</div>
                            <div class="sub-chips">
                                <label class="categ-sub-chip<?= ($v['categoria']===$slug && $v['subcategoria']==='') ? ' active' : '' ?>"
                                       id="sub-all-<?= $slug ?>">
                                    <input type="radio" name="subcategoria" value=""
                                           <?= ($v['categoria']===$slug && $v['subcategoria']==='') ? 'checked' : '' ?>
                                           onchange="onSubCategChange('<?= $slug ?>'); updatePreview()">
                                    Todos
                                </label>
                                <?php foreach ($subs as $sslug => [$slabel, $sitens]): ?>
                                <label class="categ-sub-chip<?= ($v['categoria']===$slug && $v['subcategoria']===$sslug) ? ' active' : '' ?>"
                                       id="sub-<?= $slug ?>-<?= $sslug ?>">
                                    <input type="radio" name="subcategoria" value="<?= $sslug ?>"
                                           <?= ($v['categoria']===$slug && $v['subcategoria']===$sslug) ? 'checked' : '' ?>
                                           onchange="onSubCategChange('<?= $slug ?>'); updatePreview()">
                                    <?= $slabel ?>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- 3º nível: itens -->
                            <?php foreach ($subs as $sslug => [$slabel, $sitens]): ?>
                            <?php if (!empty($sitens)): ?>
                            <div class="categ-item-panel" id="itempanel-<?= $slug ?>-<?= $sslug ?>"
                                 <?= ($v['categoria']===$slug && $v['subcategoria']===$sslug) ? '' : 'style="display:none"' ?>>
                                <div class="sub-panel-label"><?= $slabel ?>:</div>
                                <div class="sub-chips">
                                    <label class="categ-sub-chip categ-item-chip<?= ($v['categoria']===$slug && $v['subcategoria']===$sslug && $v['item_nome']==='') ? ' active' : '' ?>"
                                           id="item-all-<?= $slug ?>-<?= $sslug ?>">
                                        <input type="radio" name="item_nome" value=""
                                               <?= ($v['categoria']===$slug && $v['subcategoria']===$sslug && $v['item_nome']==='') ? 'checked' : '' ?>
                                               onchange="onItemChange('<?= $slug ?>','<?= $sslug ?>'); updatePreview()">
                                        Todos
                                    </label>
                                    <?php foreach ($sitens as $islug => $ilabel): ?>
                                    <label class="categ-sub-chip categ-item-chip<?= ($v['categoria']===$slug && $v['subcategoria']===$sslug && $v['item_nome']===$islug) ? ' active' : '' ?>"
                                           id="item-<?= $slug ?>-<?= $sslug ?>-<?= $islug ?>">
                                        <input type="radio" name="item_nome" value="<?= $islug ?>"
                                               <?= ($v['categoria']===$slug && $v['subcategoria']===$sslug && $v['item_nome']===$islug) ? 'checked' : '' ?>
                                               onchange="onItemChange('<?= $slug ?>','<?= $sslug ?>'); updatePreview()">
                                        <?= is_array($ilabel) ? $ilabel[0] : $ilabel ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>

                        <span class="field-err<?= !empty($errors['categoria']) ? ' show' : '' ?>" id="err-categoria">
                            <?= $esc($errors['categoria'] ?? 'Selecione uma categoria.') ?>
                        </span>
                    </div>

                </div>
            </div>

            <!-- ③ Atributos ──────────────────────────────────────────── -->
            <div class="form-card">
                <div class="form-card-head">
                    <span class="form-card-num">3</span>
                    <h2>Atributos do item</h2>
                </div>
                <div class="form-card-body">

                    <!-- Tier -->
                    <div class="fld">
                        <label>Tier (Grau)</label>
                        <div class="tier-row">
                            <?php for ($t = 1; $t <= 8; $t++): ?>
                            <label class="tp-btn<?= $v['grau']==$t ? ' active' : '' ?>" id="tp-<?= $t ?>">
                                <input type="radio" name="grau" value="<?= $t ?>"
                                       <?= $chk($v['grau'], $t) ?> onchange="onGrauChange(); updatePreview()">
                                T<?= $t ?>
                            </label>
                            <?php endfor; ?>
                        </div>
                        <span class="field-err<?= !empty($errors['grau']) ? ' show' : '' ?>">
                            <?= $esc($errors['grau'] ?? 'Selecione um tier.') ?>
                        </span>
                    </div>

                    <!-- Encantamento -->
                    <div class="fld">
                        <label>Encantamento</label>
                        <div class="enc-row">
                            <?php for ($e = 0; $e <= 4; $e++): ?>
                            <label class="ep-btn<?= $v['encantamento']==$e ? ' active' : '' ?>" id="ep-<?= $e ?>">
                                <input type="radio" name="encantamento" value="<?= $e ?>"
                                       <?= $chk($v['encantamento'], $e) ?> onchange="onEncChange(); updatePreview()">
                                .<?= $e ?>
                            </label>
                            <?php endfor; ?>
                        </div>
                        <span class="field-err<?= !empty($errors['encantamento']) ? ' show' : '' ?>">
                            <?= $esc($errors['encantamento'] ?? 'Selecione o encantamento.') ?>
                        </span>
                    </div>

                    <!-- Qualidade -->
                    <div class="fld">
                        <label>Qualidade</label>
                        <div class="qual-list">
                            <?php foreach ($labels_qualidade as $qv => [$ql, $qc]): ?>
                            <label class="qp-row<?= $v['qualidade']===$qv ? ' active' : '' ?>" id="qp-<?= $qv ?>">
                                <input type="radio" name="qualidade" value="<?= $qv ?>"
                                       <?= $chk($v['qualidade'], $qv) ?> onchange="onQualChange(); updatePreview()">
                                <span class="qp-dot <?= $qc ?>"></span>
                                <span class="qp-name"><?= $ql ?></span>
                                <span class="qp-check">✓</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="field-err<?= !empty($errors['qualidade']) ? ' show' : '' ?>">
                            <?= $esc($errors['qualidade'] ?? 'Selecione uma qualidade.') ?>
                        </span>
                    </div>

                </div>
            </div>

            <!-- ④ Precificação ───────────────────────────────────────── -->
            <div class="form-card">
                <div class="form-card-head">
                    <span class="form-card-num">4</span>
                    <h2>Precificação</h2>
                </div>
                <div class="form-card-body">

                    <div class="fld">
                        <label for="preco">Preço em prata</label>
                        <div class="preco-wrap">
                            <input class="fld-input<?= !empty($errors['preco']) ? ' is-error' : '' ?>"
                                   type="text" id="preco" name="preco"
                                   placeholder="0"
                                   inputmode="numeric"
                                   value="<?= $esc($v['preco']) ?>"
                                   oninput="onPrecoInput(this); updatePreview()"
                                   autocomplete="off">
                            <span class="preco-suffix">prata</span>
                        </div>
                        <div class="preco-display" id="preco-display">
                            <?php if ($v['preco']): $pn = (int)preg_replace('/[^0-9]/','',$v['preco']); ?>
                            <?= $pn >= 1_000_000 ? number_format($pn/1_000_000,2,',','.') . ' M prata'
                              : ($pn >= 1_000 ? number_format($pn/1_000,2,',','.') . ' K prata'
                              : number_format($pn,0,',','.') . ' prata') ?>
                            <?php endif; ?>
                        </div>
                        <span class="field-err<?= !empty($errors['preco']) ? ' show' : '' ?>">
                            <?= $esc($errors['preco'] ?? 'Informe um preço válido.') ?>
                        </span>
                    </div>

                    <div class="fld">
                        <label for="descricao">
                            Descrição
                            <span class="fld-optional">Opcional</span>
                        </label>
                        <textarea class="fld-textarea" id="descricao" name="descricao"
                                  placeholder="Detalhes adicionais sobre o item, condições de entrega, etc."
                                  rows="3" maxlength="1000"
                                  oninput="onDescInput(this)"><?= $esc($v['descricao']) ?></textarea>
                        <span class="field-err<?= !empty($errors['descricao']) ? ' show' : '' ?>">
                            <?= $esc($errors['descricao'] ?? '') ?>
                        </span>
                        <span class="desc-counter" id="desc-counter"><?= mb_strlen($v['descricao']) ?>/1000</span>
                    </div>

                </div>
            </div>

            <!-- ⑤ Localização ───────────────────────────────────────── -->
            <div class="form-card">
                <div class="form-card-head">
                    <span class="form-card-num">5</span>
                    <h2>Localização</h2>
                </div>
                <div class="form-card-body">

                    <div class="fld">
                        <label>Servidor</label>
                        <div class="srv-grid">
                            <?php foreach (['americas'=>['🌎','Américas'],'europe'=>['🌍','Europa'],'asia'=>['🌏','Ásia']] as $sk=>[$sf,$sl]): ?>
                            <label class="sp-chip<?= $v['servidor']===$sk ? ' active' : '' ?>" id="sp-<?= $sk ?>">
                                <input type="radio" name="servidor" value="<?= $sk ?>"
                                       <?= $chk($v['servidor'], $sk) ?> onchange="onSrvChange(); updatePreview()">
                                <span class="sp-flag"><?= $sf ?></span>
                                <span class="sp-name"><?= $sl ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="field-err<?= !empty($errors['servidor']) ? ' show' : '' ?>">
                            <?= $esc($errors['servidor'] ?? 'Selecione um servidor.') ?>
                        </span>
                    </div>

                    <div class="fld">
                        <label for="cidade">
                            Cidade
                            <span class="fld-optional">Opcional</span>
                        </label>
                        <select class="fld-select" id="cidade" name="cidade" onchange="updatePreview()">
                            <option value="">Qualquer cidade</option>
                            <?php foreach ($labels_cidade as $ck => [$cl, $ci]): ?>
                            <option value="<?= $ck ?>" <?= $chk($v['cidade'], $ck) ?>>
                                <?= $ci ?> <?= $cl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <!-- Submit ──────────────────────────────────────────────── -->
            <div class="submit-card">
                <button type="submit" class="btn-submit" id="btn-submit"
                        onclick="return validateForm()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                    </svg>
                    Publicar anúncio
                </button>
                <p class="submit-note">
                    Seu anúncio ficará visível no marketplace imediatamente.<br>
                    <strong>Validade: 60 dias</strong> a partir da publicação.
                </p>
            </div>

        </form>
        </div>

        <!-- ══ COLUNA PREVIEW ══════════════════════════════════════════ -->
        <div class="preview-col">
            <div class="preview-panel">
                <div class="preview-header">
                    <span class="preview-title">Pré-visualização</span>
                    <span class="preview-live">
                        <span class="preview-live-dot"></span>
                        ao vivo
                    </span>
                </div>

                <div class="preview-ad-card pv-<?= $v['tipo'] ?: 'empty' ?>" id="pv-card">
                    <div class="pv-head">
                        <div class="pv-icon" id="pv-icon">
                            <?= $v['categoria'] ? ($categorias_hierarquia[$v['categoria']][0] ?? '📦') : '📦' ?>
                        </div>
                        <div class="pv-badges" id="pv-badges">
                            <span class="pv-badge pv-badge-<?= $v['tipo'] ?: 'empty' ?>" id="pv-badge-tipo">
                                <?= $v['tipo'] === 'venda' ? '↑ Venda' : ($v['tipo'] === 'compra' ? '↓ Compra' : '—') ?>
                            </span>
                            <span class="pv-badge pv-badge-tier" id="pv-badge-tier">
                                T<?= $v['grau'] ?><?= $v['encantamento'] > 0 ? '.'.$v['encantamento'] : '' ?>
                            </span>
                        </div>
                    </div>

                    <div class="pv-body">
                        <div class="pv-title<?= !$v['titulo'] ? ' placeholder' : '' ?>" id="pv-title">
                            <?= $v['titulo'] ? $esc($v['titulo']) : 'Título do seu anúncio aparece aqui…' ?>
                        </div>
                        <div class="pv-attrs">
                            <div class="pv-attr">
                                <span class="pv-attr-key">Categoria</span>
                                <span class="pv-attr-val" id="pv-categoria"><?= $pvCategLabel ?: '—' ?></span>
                            </div>
                            <div class="pv-attr">
                                <span class="pv-attr-key">Qualidade</span>
                                <span class="pv-attr-val" id="pv-qualidade"><?= $labels_qualidade[$v['qualidade']][0] ?? '—' ?></span>
                            </div>
                            <div class="pv-attr">
                                <span class="pv-attr-key">Servidor</span>
                                <span class="pv-attr-val" id="pv-servidor"><?= $snomes[$v['servidor']] ?? '—' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="pv-foot">
                        <div class="pv-price">
                            <span class="pv-price-val<?= !$pvPreco ? ' placeholder' : '' ?>" id="pv-preco">
                                <?= $pvPreco ?: '—' ?>
                            </span>
                            <span class="pv-price-cur" id="pv-preco-cur"
                                  <?= !$pvPreco ? 'style="display:none"' : '' ?>>prata</span>
                        </div>
                        <div class="pv-meta">
                            <span class="pv-nick">⚔ <?= $userNick ?></span>
                            <span class="pv-expiry" id="pv-expiry">⏱ expira em 60 dias</span>
                        </div>
                    </div>
                </div>

                <div class="preview-hint">
                    O card acima é uma prévia de como seu anúncio aparecerá no marketplace.
                </div>
            </div>
        </div>

    </div><!-- /novo-grid -->
</div><!-- /novo-wrap -->

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
/* ── Dados JS ───────────────────────────────────────────────────────────── */
const CATEG_ICONS  = <?= json_encode(array_map(fn($c) => $c[0], $categorias_hierarquia)) ?>;
const CATEG_LABELS = <?= json_encode(array_map(fn($c) => $c[1], $categorias_hierarquia)) ?>;
const CATEG_SUBS   = <?= json_encode(array_map(fn($c) => $c[2], $categorias_hierarquia)) ?>;
const QUAL_LABELS  = <?= json_encode(array_map(fn($q) => $q[0], $labels_qualidade)) ?>;
const SRV_NAMES    = { americas: 'Américas', europe: 'Europa', asia: 'Ásia' };

/* ── Handlers de estado (ativo) ─────────────────────────────────────────── */
function onTipoChange() {
    const val = document.querySelector('[name="tipo"]:checked')?.value;
    document.getElementById('lbl-venda').className  = 'tipo-big' + (val === 'venda'  ? ' sel-venda'  : '');
    document.getElementById('lbl-compra').className = 'tipo-big' + (val === 'compra' ? ' sel-compra' : '');
}

function onCategChange() {
    const slug = document.querySelector('[name="categoria"]:checked')?.value || '';
    document.querySelectorAll('.cp-chip').forEach(l =>
        l.classList.toggle('active', l.querySelector('input').checked));
    document.querySelectorAll('[id^="subpanel-"]').forEach(p =>
        p.style.display = p.id === `subpanel-${slug}` ? '' : 'none');
    document.querySelectorAll('[id^="itempanel-"]').forEach(p => p.style.display = 'none');
    if (slug) {
        const allRadio = document.querySelector(`#sub-all-${slug} input`);
        if (allRadio) {
            allRadio.checked = true;
            document.querySelectorAll(`#subpanel-${slug} .categ-sub-chip`)
                .forEach(c => c.classList.remove('active'));
            document.getElementById(`sub-all-${slug}`)?.classList.add('active');
        }
        document.querySelectorAll('[name="item_nome"]').forEach(r => r.checked = false);
    }
    clearErr('err-categoria');
}

function onSubCategChange(catSlug) {
    const subPanel = document.getElementById(`subpanel-${catSlug}`);
    if (!subPanel) return;
    const subSlug = subPanel.querySelector('[name="subcategoria"]:checked')?.value || '';
    subPanel.querySelectorAll('.categ-sub-chip:not(.categ-item-chip)').forEach(c =>
        c.classList.toggle('active', c.querySelector('input[name="subcategoria"]')?.checked || false));
    subPanel.querySelectorAll('[id^="itempanel-"]').forEach(p => p.style.display = 'none');
    if (subSlug) {
        const ip = document.getElementById(`itempanel-${catSlug}-${subSlug}`);
        if (ip) {
            ip.style.display = '';
            const allItem = document.querySelector(`#item-all-${catSlug}-${subSlug} input`);
            if (allItem) {
                allItem.checked = true;
                ip.querySelectorAll('.categ-item-chip').forEach(c => c.classList.remove('active'));
                document.getElementById(`item-all-${catSlug}-${subSlug}`)?.classList.add('active');
            }
        }
    }
}

function onItemChange(catSlug, subSlug) {
    document.getElementById(`itempanel-${catSlug}-${subSlug}`)
        ?.querySelectorAll('.categ-item-chip')
        .forEach(c => c.classList.toggle('active', c.querySelector('input').checked));
}

function onGrauChange() {
    document.querySelectorAll('.tp-btn').forEach(l =>
        l.classList.toggle('active', l.querySelector('input').checked));
}
function onEncChange() {
    document.querySelectorAll('.ep-btn').forEach(l =>
        l.classList.toggle('active', l.querySelector('input').checked));
}
function onQualChange() {
    document.querySelectorAll('.qp-row').forEach(l =>
        l.classList.toggle('active', l.querySelector('input').checked));
}
function onSrvChange() {
    document.querySelectorAll('.sp-chip').forEach(l =>
        l.classList.toggle('active', l.querySelector('input').checked));
}

/* ── Inputs ─────────────────────────────────────────────────────────────── */
function onTituloInput(el) {
    const len = el.value.length;
    const counter = document.getElementById('titulo-counter');
    counter.textContent = len + '/120';
    counter.className = 'titulo-counter' + (len >= 120 ? ' over' : len >= 100 ? ' warn' : '');
}

function onPrecoInput(el) {
    const raw = el.value.replace(/[^0-9]/g, '');
    el.value = raw;
    const num = parseInt(raw, 10) || 0;
    const display = document.getElementById('preco-display');
    if (!num) { display.textContent = ''; return; }
    if (num >= 1_000_000) display.textContent = (num/1_000_000).toLocaleString('pt-BR',{maximumFractionDigits:2}) + ' M prata';
    else if (num >= 1_000) display.textContent = (num/1_000).toLocaleString('pt-BR',{maximumFractionDigits:2}) + ' K prata';
    else display.textContent = num.toLocaleString('pt-BR') + ' prata';
}

function onDescInput(el) {
    document.getElementById('desc-counter').textContent = el.value.length + '/1000';
}

/* ── Preview ────────────────────────────────────────────────────────────── */
function fmtPreco(raw) {
    const n = parseInt(String(raw).replace(/[^0-9]/g,''), 10) || 0;
    if (!n) return null;
    if (n >= 1_000_000) return (n/1_000_000).toLocaleString('pt-BR',{maximumFractionDigits:1}) + 'M';
    if (n >= 1_000)     return (n/1_000).toLocaleString('pt-BR',{maximumFractionDigits:1}) + 'K';
    return n.toLocaleString('pt-BR');
}

function updatePreview() {
    const tipo         = document.querySelector('[name="tipo"]:checked')?.value || '';
    const titulo       = document.getElementById('titulo').value.trim();
    const categoria    = document.querySelector('[name="categoria"]:checked')?.value || '';
    const subcategoria = document.querySelector('[name="subcategoria"]:checked')?.value || '';
    const item_nome    = document.querySelector('[name="item_nome"]:checked')?.value || '';
    const grau         = document.querySelector('[name="grau"]:checked')?.value || '';
    const enc          = document.querySelector('[name="encantamento"]:checked')?.value ?? '';
    const qualidade    = document.querySelector('[name="qualidade"]:checked')?.value || '';
    const servidor     = document.querySelector('[name="servidor"]:checked')?.value || '';
    const precoRaw     = document.getElementById('preco').value;

    // Card border class
    document.getElementById('pv-card').className = 'preview-ad-card pv-' + (tipo || 'empty');

    // Ícone
    document.getElementById('pv-icon').textContent = categoria ? (CATEG_ICONS[categoria] || '📦') : '📦';

    // Badge tipo
    const badgeTipo = document.getElementById('pv-badge-tipo');
    badgeTipo.textContent = tipo === 'venda' ? '↑ Venda' : tipo === 'compra' ? '↓ Compra' : '—';
    badgeTipo.className   = 'pv-badge pv-badge-' + (tipo || 'empty');

    // Badge tier
    const encNum = parseInt(enc, 10);
    document.getElementById('pv-badge-tier').textContent =
        grau ? 'T' + grau + (encNum > 0 ? '.' + encNum : '') : '—';

    // Título
    const titleEl = document.getElementById('pv-title');
    if (titulo) { titleEl.textContent = titulo; titleEl.classList.remove('placeholder'); }
    else        { titleEl.textContent = 'Título do seu anúncio aparece aqui…'; titleEl.classList.add('placeholder'); }

    // Atributos
    let categDisplay = categoria ? (CATEG_LABELS[categoria] || categoria) : '—';
    if (subcategoria && CATEG_SUBS[categoria]?.[subcategoria]) {
        categDisplay += ' › ' + CATEG_SUBS[categoria][subcategoria][0];
        if (item_nome && CATEG_SUBS[categoria][subcategoria][1]?.[item_nome]) {
            const iVal = CATEG_SUBS[categoria][subcategoria][1][item_nome];
            categDisplay += ' › ' + (Array.isArray(iVal) ? iVal[0] : iVal);
        }
    }
    document.getElementById('pv-categoria').textContent = categDisplay;
    document.getElementById('pv-qualidade').textContent = qualidade ? (QUAL_LABELS[qualidade] || qualidade) : '—';
    document.getElementById('pv-servidor').textContent  = servidor  ? (SRV_NAMES[servidor]   || servidor)  : '—';

    // Preço
    const precoFmt = fmtPreco(precoRaw);
    const precoEl  = document.getElementById('pv-preco');
    const moedaEl  = document.getElementById('pv-preco-cur');
    if (precoFmt) {
        precoEl.textContent = precoFmt; precoEl.classList.remove('placeholder');
        moedaEl.textContent = 'prata';  moedaEl.style.display = '';
    } else {
        precoEl.textContent = '—'; precoEl.classList.add('placeholder');
        moedaEl.textContent = ''; moedaEl.style.display = 'none';
    }
}

/* ── Validação ──────────────────────────────────────────────────────────── */
function showErr(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.classList.add('show'); }
}
function clearErr(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
}

function validateForm() {
    let ok = true;

    if (!document.querySelector('[name="tipo"]:checked')) {
        showErr('err-tipo', 'Selecione o tipo do anúncio.'); ok = false;
    } else clearErr('err-tipo');

    const titulo = document.getElementById('titulo').value.trim();
    if (titulo.length < 3) {
        const el = document.getElementById('err-titulo');
        if (el) el.classList.add('show');
        document.getElementById('titulo').classList.add('is-error');
        ok = false;
    } else {
        document.getElementById('titulo').classList.remove('is-error');
        clearErr('err-titulo');
    }

    if (!document.querySelector('[name="categoria"]:checked')) {
        showErr('err-categoria', 'Selecione uma categoria.'); ok = false;
    } else clearErr('err-categoria');

    const preco = parseInt(document.getElementById('preco').value, 10) || 0;
    if (preco < 1) {
        document.getElementById('preco').classList.add('is-error'); ok = false;
    } else {
        document.getElementById('preco').classList.remove('is-error');
    }

    if (!document.querySelector('[name="servidor"]:checked')) ok = false;

    if (!ok) {
        const firstErr = document.querySelector('.field-err.show, .is-error');
        firstErr?.closest('.form-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        const btn = document.getElementById('btn-submit');
        btn.textContent = 'Publicando…';
        btn.disabled = true;
    }
    return ok;
}

/* ── Init ───────────────────────────────────────────────────────────────── */
const nav = document.getElementById('navbar');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10), { passive: true });

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

updatePreview();

// Sincronizar estados de sub/item chips no carregamento (volta de erro PHP)
document.querySelectorAll('[id^="subpanel-"]').forEach(panel => {
    panel.querySelectorAll('.categ-sub-chip:not(.categ-item-chip)').forEach(c =>
        c.classList.toggle('active', c.querySelector('input[name="subcategoria"]')?.checked || false));
});
document.querySelectorAll('[id^="itempanel-"]').forEach(panel => {
    panel.querySelectorAll('.categ-item-chip').forEach(c =>
        c.classList.toggle('active', c.querySelector('input')?.checked || false));
});
</script>

</body>
</html>
