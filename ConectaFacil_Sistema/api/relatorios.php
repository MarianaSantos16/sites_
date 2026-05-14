<?php
// API — Relatórios (apenas admin)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
exigirLogin();
if (!isAdmin()) { echo json_encode(['erro' => 'Acesso negado.']); exit; }

$pdo = getDB();
$de  = $_GET['de']  ?? date('Y-m-01');
$ate = $_GET['ate'] ?? date('Y-m-d');

// Locações concluídas no período
$stmt = $pdo->prepare("SELECT l.*, d.modelo FROM locacoes l LEFT JOIN dispositivos d ON d.id = l.dispositivo_id
    WHERE l.status = 'concluida' AND (COALESCE(l.data_fim_real, l.data_inicio) BETWEEN ? AND ?) ORDER BY l.id DESC");
$stmt->execute([$de, $ate]);
$concluidas = $stmt->fetchAll();

$receita    = array_sum(array_column($concluidas, 'valor_total'));
$multas     = array_sum(array_column($concluidas, 'multa_atraso')) + array_sum(array_column($concluidas, 'multa_dano'));
$ticket     = count($concluidas) > 0 ? $receita / count($concluidas) : 0;

// Locações ativas no momento
$stmt   = $pdo->query("SELECT COUNT(*) FROM locacoes WHERE status='ativa'");
$ativas = (int) $stmt->fetchColumn();

// Ranking de aparelhos (todos os tempos)
$stmt = $pdo->query("SELECT d.modelo, COUNT(*) as total FROM locacoes l LEFT JOIN dispositivos d ON d.id = l.dispositivo_id GROUP BY l.dispositivo_id ORDER BY total DESC LIMIT 5");
$ranking = $stmt->fetchAll();

echo json_encode([
    'receita'    => round($receita, 2),
    'multas'     => round($multas, 2),
    'concluidas' => count($concluidas),
    'ticket'     => round($ticket, 2),
    'ativas'     => $ativas,
    'lista'      => $concluidas,
    'ranking'    => $ranking,
]);
