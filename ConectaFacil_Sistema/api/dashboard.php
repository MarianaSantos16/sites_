<?php
// API — Dados do Dashboard
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    exigirLogin();
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
    exit;
}
$hoje = date('Y-m-d');
$mes  = date('Y-m');

// Contagens de dispositivos
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM dispositivos GROUP BY status");
$contagens = [];
foreach ($stmt->fetchAll() as $row) {
    $contagens[$row['status']] = (int) $row['total'];
}

// Receita do mês
$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_total),0) as receita FROM locacoes WHERE status='concluida' AND data_fim_real LIKE ?");
$stmt->execute([$mes . '%']);
$receitaMes = (float) $stmt->fetchColumn();

// Locações ativas (últimas 6)
$stmt = $pdo->query("SELECT l.*, d.modelo FROM locacoes l LEFT JOIN dispositivos d ON d.id = l.dispositivo_id WHERE l.status='ativa' ORDER BY l.id DESC LIMIT 6");
$locacoesAtivas = $stmt->fetchAll();

// Dispositivos disponíveis (primeiros 6)
$stmt = $pdo->query("SELECT * FROM dispositivos WHERE status='disponivel' ORDER BY id LIMIT 6");
$disponiveis = $stmt->fetchAll();

echo json_encode([
    'disponiveis'    => $contagens['disponivel'] ?? 0,
    'locados'        => $contagens['locado'] ?? 0,
    'manutencao'     => $contagens['manutencao'] ?? 0,
    'receitaMes'     => $receitaMes,
    'hoje'           => $hoje,
    'locacoesAtivas' => $locacoesAtivas,
    'disponivelLista'=> $disponiveis,
]);
