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
    <title>Criar Conta — Albion P2P Trade</title>
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
                    <line x1="45" y1="35" x2="45" y2="55" stroke="#c9a84c" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="35" y1="45" x2="55" y2="45" stroke="#c9a84c" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="45" cy="45" r="14" fill="rgba(201,168,76,.1)" stroke="rgba(201,168,76,.5)" stroke-width=".8"/>
                </svg>
            </div>

            <h2 class="aside-heading">Entre para a guilda<br><span>de negociantes</span></h2>
            <p class="aside-desc">Sua conta é gratuita. Em minutos você já pode publicar ofertas e começar a negociar com a comunidade.</p>

            <ul class="aside-features">
                <li>
                    <span class="feat-icon">🆓</span>
                    <span>Conta 100% gratuita — sem cartão de crédito necessário</span>
                </li>
                <li>
                    <span class="feat-icon">⚡</span>
                    <span>Publique sua primeira oferta em menos de 2 minutos</span>
                </li>
                <li>
                    <span class="feat-icon">🛡</span>
                    <span>Todas as transações protegidas por escrow automático</span>
                </li>
                <li>
                    <span class="feat-icon">🌍</span>
                    <span>Acesso ao mercado de todos os servidores mundialmente</span>
                </li>
            </ul>
        </div>

        <div class="aside-footer">
            Já tem uma conta? <a href="login.php">Entrar →</a>
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
                <h1>Criar conta gratuita</h1>
                <p>Preencha os dados abaixo para começar</p>
            </div>

            <!-- Step tracker -->
            <div class="step-track" id="step-track">
                <div class="step-node active" id="sn-1">
                    <div class="step-bubble" id="sb-1">1</div>
                    <span class="step-label">Acesso</span>
                </div>
                <div class="step-node" id="sn-2">
                    <div class="step-bubble" id="sb-2">2</div>
                    <span class="step-label">Perfil</span>
                </div>
                <div class="step-node" id="sn-3">
                    <div class="step-bubble" id="sb-3">3</div>
                    <span class="step-label">Confirmar</span>
                </div>
            </div>

            <?php if ($flash): ?>
            <div class="flash-alert flash-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠' : '✓' ?></span>
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
            <?php endif; ?>

            <div id="alert-error"></div>

            <form id="reg-form" action="<?= BASE_URL ?>/app/actions/register.php" method="POST" novalidate>

                <!-- ── STEP 1 ── -->
                <div class="step-panel active" id="step-1">

                    <div class="field">
                        <label for="email">E-mail</label>
                        <div class="input-wrap">
                            <span class="input-icon">✉</span>
                            <input type="email" id="email" name="email"
                                   placeholder="seu@email.com" autocomplete="email">
                        </div>
                        <span class="field-msg" id="err-email">Informe um e-mail válido.</span>
                    </div>

                    <div class="field">
                        <label for="password">Senha</label>
                        <div class="input-wrap">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="password" name="password"
                                   placeholder="Mínimo 8 caracteres"
                                   autocomplete="new-password"
                                   oninput="updateStrength(this.value); checkConfirm();">
                            <button type="button" class="btn-eye" onclick="togglePw('password', this)"
                                    aria-label="Mostrar senha">👁</button>
                        </div>
                        <div class="pw-strength">
                            <span id="st1"></span><span id="st2"></span>
                            <span id="st3"></span><span id="st4"></span>
                        </div>
                        <span class="str-label" id="str-msg"></span>
                        <span class="field-msg" id="err-pass">Mínimo de 8 caracteres.</span>
                    </div>

                    <div class="field" style="margin-bottom:28px">
                        <label for="confirm">Confirmar senha</label>
                        <div class="input-wrap">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="confirm" name="confirm_password"
                                   placeholder="Repita a senha" autocomplete="new-password"
                                   oninput="checkConfirm()">
                            <span class="confirm-indicator" id="confirm-indicator"></span>
                            <button type="button" class="btn-eye" onclick="togglePw('confirm', this)"
                                    aria-label="Mostrar senha">👁</button>
                        </div>
                        <span class="field-msg" id="err-confirm">As senhas não coincidem.</span>
                    </div>

                    <button type="button" class="btn-primary" onclick="next(2)">
                        Continuar →
                    </button>
                </div>

                <!-- ── STEP 2 ── -->
                <div class="step-panel" id="step-2">

                    <div class="field-row">
                        <div class="field" style="margin-bottom:0">
                            <label for="nome">Nome</label>
                            <input class="input-bare" type="text" id="nome" name="nome"
                                   placeholder="Seu nome" autocomplete="given-name">
                            <span class="field-msg" id="err-nome">Campo obrigatório.</span>
                        </div>
                        <div class="field" style="margin-bottom:0">
                            <label for="sobrenome">Sobrenome</label>
                            <input class="input-bare" type="text" id="sobrenome" name="sobrenome"
                                   placeholder="Sobrenome" autocomplete="family-name">
                        </div>
                    </div>

                    <div class="field" style="margin-top:20px">
                        <label for="nick">Nick no Albion Online</label>
                        <div class="input-wrap">
                            <span class="input-icon">⚔</span>
                            <input type="text" id="nick" name="nick"
                                   placeholder="Seu personagem principal">
                        </div>
                        <span class="field-msg" style="color:var(--text-muted);opacity:1" id="nick-hint">Será exibido nas suas ofertas e avaliações.</span>
                        <span class="field-msg" id="err-nick">Campo obrigatório.</span>
                    </div>

                    <div class="field" style="margin-bottom:28px">
                        <label for="servidor">Servidor principal</label>
                        <div class="input-wrap">
                            <span class="input-icon">🌐</span>
                            <select id="servidor" name="servidor">
                                <option value="">Selecione o servidor…</option>
                                <option value="americas">🌎 Américas</option>
                                <option value="europe">🌍 Europa</option>
                                <option value="asia">🌏 Ásia</option>
                            </select>
                        </div>
                        <span class="field-msg" id="err-servidor">Selecione um servidor.</span>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn-secondary" onclick="goTo(1)">← Voltar</button>
                        <button type="button" class="btn-primary" onclick="next(3)">Continuar →</button>
                    </div>
                </div>

                <!-- ── STEP 3 ── -->
                <div class="step-panel" id="step-3">

                    <div class="reg-summary">
                        <dl>
                            <dt>E-mail</dt>    <dd id="s-email">—</dd>
                            <dt>Nick</dt>      <dd id="s-nick">—</dd>
                            <dt>Servidor</dt>  <dd id="s-servidor">—</dd>
                        </dl>
                    </div>

                    <div class="field">
                        <label class="check-wrap" style="text-transform:none;letter-spacing:0;font-size:.85rem;font-weight:400">
                            <input type="checkbox" id="termo" name="termo_uso" value="1">
                            Li e aceito os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a>
                        </label>
                        <span class="field-msg" id="err-termo">Você precisa aceitar os termos.</span>
                    </div>

                    <div class="field" style="margin-bottom:28px">
                        <label class="check-wrap" style="text-transform:none;letter-spacing:0;font-size:.85rem;font-weight:400">
                            <input type="checkbox" id="newsletter" name="newsletter" value="1" checked>
                            Quero receber alertas de ofertas e novidades por e-mail
                        </label>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn-secondary" onclick="goTo(2)">← Voltar</button>
                        <button type="submit" class="btn-primary" id="btn-reg">Criar conta</button>
                    </div>
                </div>

            </form>

            <div class="auth-switch">
                Já tem conta? <a href="login.php">Entrar</a>
            </div>

        </div>
    </main>

</div>

<script>
/* ── Helpers ── */
function togglePw(id, btn) {
    const el = document.getElementById(id);
    const show = el.type === 'password';
    el.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}

function setMsg(id, type, txt) {
    const el = document.getElementById(id);
    el.textContent = txt || el.textContent;
    el.className = 'field-msg' + (type ? ' ' + type : '');
}
function clearMsg(id) { document.getElementById(id).className = 'field-msg'; }

function inputState(id, state) {
    const el = document.getElementById(id);
    el.classList.remove('is-error', 'is-success');
    if (state) el.classList.add(state);
}

/* ── Step navigation ── */
const STEPS = 3;

function goTo(n) {
    document.querySelectorAll('.step-panel').forEach((p, i) => {
        p.classList.toggle('active', i + 1 === n);
    });
    for (let i = 1; i <= STEPS; i++) {
        const node   = document.getElementById('sn-' + i);
        const bubble = document.getElementById('sb-' + i);
        node.className   = 'step-node' + (i < n ? ' done' : i === n ? ' active' : '');
        bubble.textContent = i < n ? '✓' : String(i);
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function next(n) {
    if (n === 2 && !validateStep1()) return;
    if (n === 3 && !validateStep2()) return;
    if (n === 3) fillSummary();
    goTo(n);
}

/* ── Validation ── */
function validateStep1() {
    let ok = true;
    const email   = document.getElementById('email');
    const pass    = document.getElementById('password');
    const confirm = document.getElementById('confirm');

    clearMsg('err-email'); clearMsg('err-pass'); clearMsg('err-confirm');
    inputState('email', ''); inputState('password', ''); inputState('confirm', '');

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        setMsg('err-email', 'err', 'Informe um e-mail válido.');
        inputState('email', 'is-error');
        ok = false;
    } else {
        inputState('email', 'is-success');
    }

    if (pass.value.length < 8) {
        setMsg('err-pass', 'err', 'Mínimo de 8 caracteres.');
        inputState('password', 'is-error');
        ok = false;
    } else {
        inputState('password', 'is-success');
    }

    if (pass.value !== confirm.value || !confirm.value) {
        setMsg('err-confirm', 'err', 'As senhas não coincidem.');
        inputState('confirm', 'is-error');
        ok = false;
    } else if (pass.value.length >= 8) {
        inputState('confirm', 'is-success');
    }

    return ok;
}

function validateStep2() {
    let ok = true;
    const nick = document.getElementById('nick');
    const nome = document.getElementById('nome');
    const serv = document.getElementById('servidor');

    clearMsg('err-nick'); clearMsg('err-nome'); clearMsg('err-servidor');

    if (!nome.value.trim()) {
        setMsg('err-nome', 'err', 'Campo obrigatório.');
        inputState('nome', 'is-error');
        ok = false;
    }
    if (!nick.value.trim()) {
        setMsg('err-nick', 'err', 'Campo obrigatório.');
        inputState('nick', 'is-error');
        ok = false;
    }
    if (!serv.value) {
        setMsg('err-servidor', 'err', 'Selecione um servidor.');
        inputState('servidor', 'is-error');
        ok = false;
    }
    return ok;
}

/* ── Summary ── */
const serverNames = { americas: '🌎 Américas', europe: '🌍 Europa', asia: '🌏 Ásia' };
function fillSummary() {
    document.getElementById('s-email').textContent    = document.getElementById('email').value    || '—';
    document.getElementById('s-nick').textContent     = document.getElementById('nick').value     || '—';
    const sv = document.getElementById('servidor').value;
    document.getElementById('s-servidor').textContent = serverNames[sv] || '—';
}

/* ── Confirmação de senha em tempo real ── */
function checkConfirm() {
    const pass    = document.getElementById('password');
    const confirm = document.getElementById('confirm');
    const icon    = document.getElementById('confirm-indicator');
    const val     = confirm.value;

    if (!val) {
        clearMsg('err-confirm');
        inputState('confirm', '');
        confirm.classList.remove('has-indicator');
        icon.className = 'confirm-indicator';
        return;
    }

    confirm.classList.add('has-indicator');

    if (val === pass.value) {
        setMsg('err-confirm', 'success', '✓ Senhas coincidem');
        inputState('confirm', 'is-success');
        icon.textContent = '✓';
        icon.className = 'confirm-indicator ci-show ci-ok';
    } else {
        setMsg('err-confirm', 'err', 'As senhas não coincidem.');
        inputState('confirm', 'is-error');
        icon.textContent = '✗';
        icon.className = 'confirm-indicator ci-show ci-err';
    }
}

/* ── Password strength ── */
const colors = ['#c0392b', '#e67e22', '#e8c97a', '#27ae60'];
const labels = ['Muito fraca', 'Fraca', 'Razoável', 'Forte'];

function updateStrength(val) {
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    for (let i = 1; i <= 4; i++) {
        document.getElementById('st' + i).style.background =
            i <= score ? colors[score - 1] : 'var(--border)';
    }

    const msg = document.getElementById('str-msg');
    msg.textContent = val.length ? (labels[score - 1] || '') : '';
    msg.style.color = score > 0 ? colors[score - 1] : 'var(--text-muted)';
}

/* ── Submit ── */
document.getElementById('reg-form').addEventListener('submit', function(e) {
    clearMsg('err-termo');
    if (!document.getElementById('termo').checked) {
        setMsg('err-termo', 'err', 'Você precisa aceitar os termos.');
        e.preventDefault();
        return;
    }
    const btn = document.getElementById('btn-reg');
    btn.textContent = 'Criando conta…';
    btn.disabled = true;
});
</script>

</body>
</html>
