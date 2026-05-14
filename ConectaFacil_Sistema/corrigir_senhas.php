<?php
/**
 * Corrige/recria todos os usuários com as senhas corretas.
 * Acesse: http://localhost/ConectaFacil_Sistema/corrigir_senhas.php
 * APAGUE após o uso.
 */
require_once __DIR__ . '/config/db.php';

$pdo = getDB();

// Remove usuários antigos e recria com senhas corretas
$pdo->exec("DELETE FROM usuarios");
$pdo->exec("ALTER TABLE usuarios AUTO_INCREMENT = 1");

$usuarios = [
    ['Carlos Eduardo Silva', 'admin',    'admin123', 'admin'],
    ['Fernanda Oliveira',    'fernanda', 'fer456',   'operador'],
    ['Ricardo Mendes',       'ricardo',  'ric789',   'operador'],
];

$stmt = $pdo->prepare("INSERT INTO usuarios (nome, usuario, senha, perfil) VALUES (?, ?, ?, ?)");

echo '<style>body{font-family:sans-serif;padding:30px;background:#f8fafc} .ok{color:#059669} .box{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;max-width:500px} code{background:#eef2ff;color:#4f46e5;padding:2px 8px;border-radius:4px;font-size:13px}</style>';
echo '<div class="box"><h2>🔧 Corrigindo usuários...</h2><br>';

foreach ($usuarios as [$nome, $usuario, $senha, $perfil]) {
    $stmt->execute([$nome, $usuario, $senha, $perfil]);
    echo '<p class="ok">✓ <strong>' . $usuario . '</strong> → senha: <code>' . $senha . '</code> (' . $perfil . ')</p>';
}

echo '<br><p>✅ Pronto! Agora tente fazer login.</p>';
echo '<br><a href="index.php" style="display:inline-block;padding:10px 24px;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">→ Ir para o Login</a>';
echo '<br><br><p style="color:#ef4444;font-size:12px">⚠️ Apague este arquivo após o uso.</p>';
echo '</div>';
