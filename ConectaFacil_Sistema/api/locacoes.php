<?php
// API — Locações
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
exigirLogin();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ===== LISTAR =====
if ($method === 'GET' && $action === 'listar') {
    $where  = '1=1';
    $params = [];

    if (!empty($_GET['busca'])) {
        $where   .= ' AND (l.cliente_nome LIKE ? OR l.cliente_cpfcnpj LIKE ?)';
        $params[] = '%' . $_GET['busca'] . '%';
        $params[] = '%' . $_GET['busca'] . '%';
    }
    if (!empty($_GET['status'])) {
        $where   .= ' AND l.status = ?';
        $params[] = $_GET['status'];
    }

    $sql  = "SELECT l.*, d.modelo, d.marca FROM locacoes l
             LEFT JOIN dispositivos d ON d.id = l.dispositivo_id
             WHERE $where ORDER BY l.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

// ===== BUSCAR POR ID =====
if ($method === 'GET' && $action === 'buscar') {
    $id   = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT l.*, d.modelo, d.marca FROM locacoes l LEFT JOIN dispositivos d ON d.id = l.dispositivo_id WHERE l.id = ?');
    $stmt->execute([$id]);
    $l = $stmt->fetch();
    echo json_encode($l ?: ['erro' => 'Locação não encontrada.']);
    exit;
}

// ===== CRIAR LOCAÇÃO =====
if ($method === 'POST' && $action === 'criar') {
    $data = json_decode(file_get_contents('php://input'), true);

    $clienteNome     = trim($data['clienteNome'] ?? '');
    $clienteCpfcnpj  = trim($data['clienteCpfcnpj'] ?? '');
    $clienteTelefone = trim($data['clienteTelefone'] ?? '');
    $dispositivoId   = (int) ($data['dispositivoId'] ?? 0);
    $dataInicio      = $data['dataInicio'] ?? '';
    $dataFimPrevista = $data['dataFimPrevista'] ?? '';
    $observacoes     = trim($data['observacoes'] ?? '');

    if (!$clienteNome || !$clienteCpfcnpj || !$clienteTelefone) {
        echo json_encode(['erro' => 'Preencha todos os dados do cliente.']); exit;
    }
    if (!$dispositivoId) { echo json_encode(['erro' => 'Selecione um dispositivo.']); exit; }
    if (!$dataInicio || !$dataFimPrevista) { echo json_encode(['erro' => 'Informe as datas.']); exit; }
    if ($dataFimPrevista <= $dataInicio) { echo json_encode(['erro' => 'A data de devolução deve ser após a data de início.']); exit; }

    // Verifica disponibilidade
    $stmt = $pdo->prepare("SELECT * FROM dispositivos WHERE id = ? AND status = 'disponivel'");
    $stmt->execute([$dispositivoId]);
    $disp = $stmt->fetch();
    if (!$disp) { echo json_encode(['erro' => 'Este aparelho não está disponível para locação.']); exit; }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO locacoes (cliente_nome, cliente_cpfcnpj, cliente_telefone, dispositivo_id, data_inicio, data_fim_prevista, status, observacoes, valor_diaria) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$clienteNome, $clienteCpfcnpj, $clienteTelefone, $dispositivoId, $dataInicio, $dataFimPrevista, 'ativa', $observacoes, $disp['diaria']]);
        $novoId = (int) $pdo->lastInsertId();

        $pdo->prepare("UPDATE dispositivos SET status = 'locado' WHERE id = ?")->execute([$dispositivoId]);
        $pdo->commit();
        echo json_encode(['sucesso' => true, 'id' => $novoId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['erro' => 'Erro ao registrar locação: ' . $e->getMessage()]);
    }
    exit;
}

// ===== DEVOLVER =====
if ($method === 'POST' && $action === 'devolver') {
    $data = json_decode(file_get_contents('php://input'), true);

    $id          = (int) ($data['id'] ?? 0);
    $dataFimReal = $data['dataFimReal'] ?? '';
    $danos       = trim($data['danos'] ?? '');
    $multaDano   = (float) ($data['multaDano'] ?? 0);

    if (!$dataFimReal) { echo json_encode(['erro' => 'Informe a data de devolução.']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM locacoes WHERE id = ? AND status = 'ativa'");
    $stmt->execute([$id]);
    $loc = $stmt->fetch();
    if (!$loc) { echo json_encode(['erro' => 'Locação não encontrada ou já encerrada.']); exit; }

    if ($dataFimReal < $loc['data_inicio']) {
        echo json_encode(['erro' => 'Data de devolução não pode ser antes do início.']); exit;
    }

    $dias       = calcularDias($loc['data_inicio'], $dataFimReal);
    $valorBase  = $dias * (float) $loc['valor_diaria'];
    $multaAtraso = calcularMultaAtraso($loc['data_fim_prevista'], $dataFimReal, (float) $loc['valor_diaria']);
    $valorTotal = round($valorBase + $multaAtraso + $multaDano, 2);

    $novoStatus = $danos ? 'manutencao' : 'disponivel';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE locacoes SET data_fim_real=?, danos=?, multa_dano=?, multa_atraso=?, valor_total=?, status=? WHERE id=?');
        $stmt->execute([$dataFimReal, $danos, $multaDano, $multaAtraso, $valorTotal, 'concluida', $id]);

        $pdo->prepare("UPDATE dispositivos SET status = ? WHERE id = ?")->execute([$novoStatus, $loc['dispositivo_id']]);
        $pdo->commit();

        echo json_encode([
            'sucesso'     => true,
            'valorTotal'  => $valorTotal,
            'multaAtraso' => $multaAtraso,
            'dias'        => $dias,
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['erro' => 'Erro ao registrar devolução: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['erro' => 'Ação inválida.']);
