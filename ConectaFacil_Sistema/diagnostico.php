<?php
/**
 * ConectaFácil — Diagnóstico
 * Acesse: http://localhost/ConectaFacil_Sistema/diagnostico.php
 * APAGUE após resolver o problema.
 */

$resultados = [];

// 1. Versão do PHP
$resultados[] = ['ok' => true, 'msg' => 'PHP ' . PHP_VERSION];

// 2. Extensão PDO MySQL
$pdoOk = extension_loaded('pdo_mysql');
$resultados[] = ['ok' => $pdoOk, 'msg' => 'Extensão pdo_mysql: ' . ($pdoOk ? 'OK' : 'NÃO ENCONTRADA — habilite no php.ini')];

// 3. Sessões
session_start();
$_SESSION['teste'] = 'ok';
$sessOk = ($_SESSION['teste'] === 'ok');
$resultados[] = ['ok' => $sessOk, 'msg' => 'Sessões PHP: ' . ($sessOk ? 'OK' : 'FALHOU')];

// 4. Conexão com MySQL
$dbOk = false;
$dbMsg = '';
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dbOk = true;
    $dbMsg = 'Conexão MySQL: OK (usuário root sem senha)';
} catch (PDOException $e) {
    $dbMsg = 'Conexão MySQL FALHOU: ' . $e->getMessage();
}
$resultados[] = ['ok' => $dbOk, 'msg' => $dbMsg];

// 5. Banco conectafacil existe?
$bancoOk = false;
if ($dbOk) {
    try {
        $pdo->exec('USE conectafacil');
        $bancoOk = true;
        $resultados[] = ['ok' => true, 'msg' => 'Banco "conectafacil": existe'];
    } catch (PDOException $e) {
        $resultados[] = ['ok' => false, 'msg' => 'Banco "conectafacil" NÃO EXISTE — rode setup.php primeiro'];
    }
}

// 6. Tabela usuarios existe e tem dados?
if ($bancoOk) {
    try {
        $stmt = $pdo->query('SELECT id, nome, usuario, perfil FROM usuarios');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($users) === 0) {
            $resultados[] = ['ok' => false, 'msg' => 'Tabela usuarios está VAZIA — rode setup.php'];
        } else {
            foreach ($users as $u) {
                $resultados[] = [
                    'ok'  => true,
                    'msg' => "Usuário [{$u['usuario']}] perfil={$u['perfil']} — encontrado"
                ];
            }
        }
    } catch (PDOException $e) {
        $resultados[] = ['ok' => false, 'msg' => 'Tabela usuarios: ' . $e->getMessage()];
    }
}

// 7. Testa senha em texto puro
if ($bancoOk) {
    try {
        $stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE usuario = ?');
        $stmt->execute(['admin']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $senhaOk = ($row['senha'] === 'admin123');
            $resultados[] = [
                'ok'  => $senhaOk,
                'msg' => 'Senha do admin: ' . ($senhaOk ? 'OK (admin123) ✓' : 'INCORRETA — valor atual: "' . htmlspecialchars($row['senha']) . '" — rode gerar_senhas.php')
            ];
        }
    } catch (PDOException $e) {}
}

// 8. output_buffering
$obOk = (bool) ini_get('output_buffering');
$resultados[] = ['ok' => true, 'msg' => 'output_buffering: ' . (ini_get('output_buffering') ?: 'Off') . ($obOk ? '' : ' (header() pode falhar se houver output antes)') ];

$tudo_ok = !in_array(false, array_column($resultados, 'ok'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diagnóstico — ConectaFácil</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #F1F5F9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #fff; border-radius: 16px; padding: 36px; max-width: 600px; width: 100%; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
    h1 { font-size: 20px; margin-bottom: 4px; }
    .sub { color: #64748B; font-size: 13px; margin-bottom: 24px; }
    .item { display: flex; align-items: flex-start; gap: 12px; padding: 11px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 13px; }
    .item.ok  { background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534; }
    .item.err { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
    .icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
    .actions { margin-top: 24px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-block; }
    .btn-blue  { background: #2563EB; color: #fff; }
    .btn-green { background: #16A34A; color: #fff; }
    .btn-gray  { background: #E2E8F0; color: #334155; }
    .banner { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
    .banner.ok  { background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; }
    .banner.err { background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; }
  </style>
</head>
<body>
  <div class="card">
    <h1>🔍 Diagnóstico do Sistema</h1>
    <p class="sub">ConectaFácil — verificação de ambiente</p>

    <div class="banner <?= $tudo_ok ? 'ok' : 'err' ?>">
      <?= $tudo_ok ? '✅ Tudo OK! O sistema deve funcionar normalmente.' : '❌ Foram encontrados problemas. Veja os itens em vermelho abaixo.' ?>
    </div>

    <?php foreach ($resultados as $r): ?>
      <div class="item <?= $r['ok'] ? 'ok' : 'err' ?>">
        <span class="icon"><?= $r['ok'] ? '✓' : '✗' ?></span>
        <span><?= htmlspecialchars($r['msg']) ?></span>
      </div>
    <?php endforeach; ?>

    <div class="actions">
      <?php if (!$tudo_ok): ?>
        <a href="setup.php" class="btn btn-green">🔧 Rodar Setup Automático</a>
      <?php endif; ?>
      <a href="index.php" class="btn btn-blue">→ Ir para o Login</a>
      <a href="diagnostico.php" class="btn btn-gray">↺ Retestar</a>
    </div>

    <p style="margin-top:20px;font-size:11px;color:#94A3B8">⚠️ Apague este arquivo após resolver o problema.</p>
  </div>
</body>
</html>
