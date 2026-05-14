<?php
require_once __DIR__ . '/config/db.php';
$pdo = getDB();

// Simula exatamente o que o index.php faz
$usuario = 'fernanda';
$senha   = 'fer456';

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? LIMIT 1');
$stmt->execute([$usuario]);
$user = $stmt->fetch();

echo '<style>body{font-family:monospace;padding:30px;background:#0f172a;color:#e2e8f0;font-size:14px}
.ok{color:#10b981} .err{color:#ef4444} .box{background:#1e293b;padding:16px;border-radius:8px;margin:10px 0}
a{color:#818cf8}</style>';

echo '<h2 style="color:#fff">🔍 Debug do Login</h2>';

echo '<div class="box">';
echo '<b>Usuário buscado:</b> ' . $usuario . '<br>';
echo '<b>Senha digitada:</b> ' . $senha . '<br>';
echo '</div>';

if (!$user) {
    echo '<div class="box"><span class="err">❌ Usuário NÃO encontrado no banco!</span></div>';
} else {
    echo '<div class="box">';
    echo '<span class="ok">✓ Usuário encontrado</span><br><br>';
    echo '<b>ID:</b> '     . $user['id']      . '<br>';
    echo '<b>Nome:</b> '   . $user['nome']    . '<br>';
    echo '<b>Login:</b> '  . $user['usuario'] . '<br>';
    echo '<b>Perfil:</b> ' . $user['perfil']  . '<br>';
    echo '<b>Senha no banco (raw):</b> [' . $user['senha'] . ']<br>';
    echo '<b>Senha digitada (raw):</b> [' . $senha . ']<br>';
    echo '<b>strlen banco:</b> '   . strlen($user['senha']) . '<br>';
    echo '<b>strlen digitada:</b> ' . strlen($senha) . '<br>';
    echo '<b>Comparação ===:</b> ';
    if ($user['senha'] === $senha) {
        echo '<span class="ok">✓ IGUAL — login deveria funcionar!</span>';
    } else {
        echo '<span class="err">❌ DIFERENTE</span><br>';
        // Mostra bytes para detectar espaços/caracteres invisíveis
        echo '<b>Bytes banco:</b> ';
        for ($i = 0; $i < strlen($user['senha']); $i++) echo ord($user['senha'][$i]) . ' ';
        echo '<br><b>Bytes digitada:</b> ';
        for ($i = 0; $i < strlen($senha); $i++) echo ord($senha[$i]) . ' ';
    }
    echo '</div>';
}

// Mostra TODOS os usuários
echo '<div class="box"><b>Todos os usuários:</b><br><br>';
$all = $pdo->query("SELECT id, usuario, senha, perfil FROM usuarios")->fetchAll();
foreach ($all as $u) {
    echo "ID={$u['id']} | usuario=[{$u['usuario']}] | senha=[{$u['senha']}] | perfil={$u['perfil']}<br>";
}
echo '</div>';

// Verifica se a sessão funciona
require_once __DIR__ . '/includes/auth.php';
echo '<div class="box"><b>Sessão atual:</b> ';
$logado = getUsuarioLogado();
echo $logado ? json_encode($logado) : 'nenhuma sessão ativa';
echo '</div>';

echo '<br><a href="index.php">→ Ir para o Login</a>';
