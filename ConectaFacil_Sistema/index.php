<?php
require_once __DIR__ . '/includes/auth.php';

if (getUsuarioLogado()) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $base . '/pages/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if (!$usuario || !$senha) {
        $erro = 'Preencha usuário e senha.';
    } else {
        try {
            require_once __DIR__ . '/config/db.php';
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? LIMIT 1');
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            if ($user && $user['senha'] === $senha) {
                $_SESSION['usuario'] = [
                    'id'      => $user['id'],
                    'nome'    => $user['nome'],
                    'usuario' => $user['usuario'],
                    'perfil'  => $user['perfil'],
                ];
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                header('Location: ' . $base . '/pages/dashboard.php');
                exit;
            } else {
                $erro = 'Usuário ou senha incorretos.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão com o banco. Verifique se o MySQL está rodando.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-icons-base="<?= htmlspecialchars(cf_icons_base_path(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <?php cfThemeHeadScript(); ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ConectaFácil — Login</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">

  <!-- Lado esquerdo: hero -->
  <div class="login-left">
    <div class="login-hero">
      <div class="login-hero-badge">
        <span></span> Sistema de Gestão v2.0
      </div>
      <h1>Gerencie suas<br><span>locações</span> com<br>facilidade</h1>
      <p>Controle completo de dispositivos, clientes e receitas em uma plataforma moderna e intuitiva.</p>
      <div class="login-features-list">
        <div class="login-feat">
          <div class="login-feat-icon" style="background:rgba(99,102,241,0.15)"><?= cf_icon_img('phone.svg', 'login-feat-img', 20, 20) ?></div>
          <span>Gestão completa de dispositivos e estoque</span>
        </div>
        <div class="login-feat">
          <div class="login-feat-icon" style="background:rgba(16,185,129,0.15)"><?= cf_icon_img('clipboard.svg', 'login-feat-img', 20, 20) ?></div>
          <span>Registro e acompanhamento de locações em tempo real</span>
        </div>
        <div class="login-feat">
          <div class="login-feat-icon" style="background:rgba(245,158,11,0.15)"><?= cf_icon_img('chart.svg', 'login-feat-img', 20, 20) ?></div>
          <span>Relatórios financeiros e análise de desempenho</span>
        </div>
        <div class="login-feat">
          <div class="login-feat-icon" style="background:rgba(239,68,68,0.15)"><?= cf_icon_img('reply.svg', 'login-feat-img', 20, 20) ?></div>
          <span>Controle de devoluções com cálculo automático de multas</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Lado direito: formulário -->
  <div class="login-right">
    <button type="button" class="theme-toggle-btn login-theme-toggle" aria-pressed="false" aria-label="Ativar modo escuro">
      <?= cf_icon_img('moon.svg', 'theme-toggle-icon', 18, 18) ?>
      <span class="theme-toggle-label">Modo escuro</span>
    </button>
    <div class="login-form-wrap">
      <div class="login-logo">
        <div class="login-logo-icon"><?= cf_icon_img('phone-brand.svg', 'login-logo-img', 22, 22) ?></div>
        <div>
          <div class="login-logo-name">ConectaFácil</div>
          <div class="login-logo-sub">Locações Tecnológicas</div>
        </div>
      </div>

      <h2>Bem-vindo de volta</h2>
      <p>Entre com suas credenciais para acessar o painel</p>

      <?php if ($erro): ?>
        <div class="login-error"><?= cf_icon_img('warning.svg', 'login-error-icon', 20, 20) ?><span><?= htmlspecialchars($erro) ?></span></div>
      <?php endif; ?>

      <form method="POST" action="index.php">
        <div class="input-group">
          <label>Usuário</label>
          <span class="input-icon"><?= cf_icon_img('user.svg', 'input-icon-img', 16, 16) ?></span>
          <input type="text" name="usuario"
                 value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                 placeholder="Digite seu usuário"
                 autocomplete="username" required autofocus>
        </div>
        <div class="input-group">
          <label>Senha</label>
          <span class="input-icon"><?= cf_icon_img('lock.svg', 'input-icon-img', 16, 16) ?></span>
          <input type="password" name="senha"
                 placeholder="Digite sua senha"
                 autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-login">Entrar no Sistema →</button>
      </form>

      <div class="login-creds">
        <div class="login-creds-title">Credenciais de acesso</div>
        <div class="cred-item">
          <span class="cred-item-label"><?= cf_icon_img('crown.svg', 'cred-icon', 16, 16) ?> <strong>admin</strong> — Administrador</span>
          <code>admin123</code>
        </div>
        <div class="cred-item">
          <span class="cred-item-label"><?= cf_icon_img('user.svg', 'cred-icon', 16, 16) ?> <strong>fernanda</strong> — Operador</span>
          <code>fer456</code>
        </div>
        <div class="cred-item">
          <span class="cred-item-label"><?= cf_icon_img('user.svg', 'cred-icon', 16, 16) ?> <strong>ricardo</strong> — Operador</span>
          <code>ric789</code>
        </div>
      </div>
    </div>
  </div>

<script src="js/theme.js" defer></script>
</body>
</html>
