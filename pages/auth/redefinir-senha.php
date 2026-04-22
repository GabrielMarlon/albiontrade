<?php
require_once __DIR__ . '/../../app/config/auth.php';
iniciar_sessao();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$token      = $_GET['token'] ?? '';
$tokenOk    = false;
$tokenEmail = '';

if ($token !== '') {
    try {
        $pdo       = db();
        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare('
            SELECT email FROM senha_resets
            WHERE  token_hash = :hash
              AND  expira_em  > NOW()
              AND  usado      = 0
            LIMIT 1
        ');
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();

        if ($row) {
            $tokenOk    = true;
            $tokenEmail = $row['email'];
        }
    } catch (PDOException $e) {
        error_log('[albiontrade] redefinir-senha GET: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha — Albion P2P Trade</title>
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
                    <!-- Escudo com cadeado -->
                    <path d="M45 31 L56 36 L56 46 Q56 54 45 59 Q34 54 34 46 L34 36 Z"
                          fill="rgba(201,168,76,.12)" stroke="#c9a84c" stroke-width="1.5" stroke-linejoin="round"/>
                    <rect x="40" y="43" width="10" height="8" rx="2"
                          fill="rgba(201,168,76,.3)" stroke="#c9a84c" stroke-width="1"/>
                    <path d="M42 43 L42 40 Q42 37 45 37 Q48 37 48 40 L48 43"
                          fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="45" cy="47" r="1.5" fill="#c9a84c"/>
                </svg>
            </div>

            <h2 class="aside-heading">Crie uma nova<br><span>senha segura</span></h2>
            <p class="aside-desc">Escolha uma senha forte para proteger sua conta e seus anúncios na plataforma.</p>

            <ul class="aside-features">
                <li>
                    <span class="feat-icon">✓</span>
                    <span>Mínimo de 8 caracteres</span>
                </li>
                <li>
                    <span class="feat-icon">🔤</span>
                    <span>Misture letras, números e símbolos</span>
                </li>
                <li>
                    <span class="feat-icon">🛡</span>
                    <span>Não reutilize senhas de outros serviços</span>
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

            <?php if ($flash): ?>
            <div class="flash-alert flash-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <span class="flash-icon"><?= $flash['type'] === 'error' ? '⚠' : '✓' ?></span>
                <?= $flash['msg'] ?>
            </div>
            <?php endif; ?>

            <?php if (!$tokenOk): ?>
            <!-- Token inválido ou expirado -->
            <div class="auth-heading">
                <h1>Link inválido</h1>
                <p>Este link de recuperação expirou ou já foi utilizado.</p>
            </div>

            <div class="flash-alert flash-error" style="margin-bottom:28px">
                <span class="flash-icon">⚠</span>
                Links de recuperação são válidos por apenas 1 hora e de uso único.
            </div>

            <a href="<?= BASE_URL ?>/pages/auth/recuperar-senha.php" class="btn-primary"
               style="text-decoration:none;display:flex">
                Solicitar novo link
            </a>

            <?php else: ?>
            <!-- Formulário de nova senha -->
            <div class="auth-heading">
                <h1>Redefinir senha</h1>
                <p>Conta: <strong style="color:var(--gold)"><?= htmlspecialchars($tokenEmail) ?></strong></p>
            </div>

            <div id="alert-error"></div>

            <form id="reset-form"
                  action="<?= BASE_URL ?>/app/actions/redefinir-senha.php"
                  method="POST" novalidate>

                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="field">
                    <label for="password">Nova senha</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password"
                               placeholder="Mínimo 8 caracteres"
                               autocomplete="new-password"
                               oninput="updateStrength(this.value)">
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
                    <label for="confirm">Confirmar nova senha</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="confirm" name="confirm_password"
                               placeholder="Repita a nova senha"
                               autocomplete="new-password">
                        <button type="button" class="btn-eye" onclick="togglePw('confirm', this)"
                                aria-label="Mostrar senha">👁</button>
                    </div>
                    <span class="field-msg" id="err-confirm">As senhas não coincidem.</span>
                </div>

                <button type="submit" class="btn-primary" id="btn-submit">
                    Salvar nova senha
                </button>

            </form>
            <?php endif; ?>

            <div class="auth-switch" style="margin-top:20px">
                <a href="<?= BASE_URL ?>/pages/auth/login.php">← Voltar para o login</a>
            </div>

        </div>
    </main>

</div>

<script>
function togglePw(id, btn) {
    const el   = document.getElementById(id);
    const show = el.type === 'password';
    el.type    = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}

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

const form = document.getElementById('reset-form');
if (form) {
    form.addEventListener('submit', function(e) {
        const pass    = document.getElementById('password');
        const confirm = document.getElementById('confirm');
        const errP    = document.getElementById('err-pass');
        const errC    = document.getElementById('err-confirm');
        errP.className = 'field-msg';
        errC.className = 'field-msg';
        pass.classList.remove('is-error');
        confirm.classList.remove('is-error');
        let ok = true;

        if (pass.value.length < 8) {
            errP.className = 'field-msg err';
            pass.classList.add('is-error');
            ok = false;
        }
        if (pass.value !== confirm.value || !confirm.value) {
            errC.className = 'field-msg err';
            confirm.classList.add('is-error');
            ok = false;
        }
        if (!ok) { e.preventDefault(); return; }

        const btn = document.getElementById('btn-submit');
        btn.textContent = 'Salvando…';
        btn.disabled = true;
    });
}
</script>
</body>
</html>
