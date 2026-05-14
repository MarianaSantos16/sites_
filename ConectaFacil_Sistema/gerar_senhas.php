<?php
/**
 * Redefine as senhas no banco em texto puro.
 * Acesse: http://localhost/ConectaFacil_Sistema/gerar_senhas.php
 * Apague este arquivo após o uso.
 */
require_once __DIR__ . '/config/db.php';

$senhas = [
    'admin'    => 'admin123',
    'operador' => 'op123',
];

$pdo = getDB();
foreach ($senhas as $usuario => $senha) {
    $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE usuario = ?');
    $stmt->execute([$senha, $usuario]);
    echo "Usuário <strong>$usuario</strong> — senha definida como: <code>$senha</code><br>";
}

echo '<br><strong style="color:red">⚠️ APAGUE ESTE ARQUIVO AGORA!</strong>';
echo '<br><a href="index.php">Ir para o Login</a>';
