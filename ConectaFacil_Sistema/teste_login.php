<?php
require_once __DIR__ . '/config/db.php';

$pdo = getDB();
$stmt = $pdo->query("SELECT id, nome, usuario, senha, perfil FROM usuarios");
$users = $stmt->fetchAll();

echo '<style>body{font-family:sans-serif;padding:30px;background:#f1f5f9}
table{border-collapse:collapse;width:100%;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.1)}
th{background:#6366f1;color:#fff;padding:10px 14px;text-align:left;font-size:12px}
td{padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:13px}
code{background:#eef2ff;color:#4f46e5;padding:2px 8px;border-radius:4px}
.ok{color:#059669;font-weight:700} .err{color:#dc2626;font-weight:700}
h2{margin-bottom:16px}</style>';

echo '<h2>Usuários no banco</h2>';
echo '<table><tr><th>ID</th><th>Nome</th><th>Usuário</th><th>Senha (exata)</th><th>Perfil</th><th>Teste fer456</th><th>Teste ric789</th></tr>';
foreach ($users as $u) {
    $testFer = ($u['senha'] === 'fer456') ? '<span class="ok">✓ OK</span>' : '<span class="err">✗ FALHOU</span>';
    $testRic = ($u['senha'] === 'ric789') ? '<span class="ok">✓ OK</span>' : '<span class="err">✗ FALHOU</span>';
    echo "<tr>
        <td>{$u['id']}</td>
        <td>{$u['nome']}</td>
        <td><code>{$u['usuario']}</code></td>
        <td><code>" . htmlspecialchars($u['senha']) . "</code></td>
        <td>{$u['perfil']}</td>
        <td>$testFer</td>
        <td>$testRic</td>
    </tr>";
}
echo '</table>';

echo '<br><h2>Ação: Forçar senhas corretas agora</h2>';

// Força as senhas certas direto
$pdo->exec("UPDATE usuarios SET senha = 'admin123' WHERE usuario = 'admin'");
$pdo->exec("UPDATE usuarios SET senha = 'fer456'   WHERE usuario = 'fernanda'");
$pdo->exec("UPDATE usuarios SET senha = 'ric789'   WHERE usuario = 'ricardo'");

// Se não existirem, insere
$pdo->exec("INSERT IGNORE INTO usuarios (nome, usuario, senha, perfil) VALUES
    ('Carlos Eduardo Silva', 'admin',    'admin123', 'admin'),
    ('Fernanda Oliveira',    'fernanda', 'fer456',   'operador'),
    ('Ricardo Mendes',       'ricardo',  'ric789',   'operador')");

echo '<p class="ok">✓ Senhas atualizadas com sucesso!</p>';

// Mostra estado final
$stmt2 = $pdo->query("SELECT usuario, senha, perfil FROM usuarios");
echo '<br><table><tr><th>Usuário</th><th>Senha</th><th>Perfil</th></tr>';
foreach ($stmt2->fetchAll() as $u) {
    echo "<tr><td><code>{$u['usuario']}</code></td><td><code>" . htmlspecialchars($u['senha']) . "</code></td><td>{$u['perfil']}</td></tr>";
}
echo '</table>';

echo '<br><a href="index.php" style="display:inline-block;padding:11px 28px;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:700">→ Ir para o Login</a>';
echo '<br><br><small style="color:#ef4444">⚠️ Apague este arquivo após o uso.</small>';
