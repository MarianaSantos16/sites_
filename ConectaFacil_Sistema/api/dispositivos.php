<?php
// API — Dispositivos
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

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
        $where   .= ' AND (modelo LIKE ? OR marca LIKE ?)';
        $params[] = '%' . $_GET['busca'] . '%';
        $params[] = '%' . $_GET['busca'] . '%';
    }
    if (!empty($_GET['status'])) {
        $where   .= ' AND status = ?';
        $params[] = $_GET['status'];
    }

    $stmt = $pdo->prepare("SELECT * FROM dispositivos WHERE $where ORDER BY id DESC");
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

// ===== BUSCAR POR ID =====
if ($method === 'GET' && $action === 'buscar') {
    $id   = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM dispositivos WHERE id = ?');
    $stmt->execute([$id]);
    $d = $stmt->fetch();
    echo json_encode($d ?: ['erro' => 'Dispositivo não encontrado.']);
    exit;
}

// ===== SALVAR (criar / editar) — apenas admin =====
if ($method === 'POST' && $action === 'salvar') {
    if (!isAdmin()) { echo json_encode(['erro' => 'Acesso negado.']); exit; }

    $data = json_decode(file_get_contents('php://input'), true);

    $modelo        = trim($data['modelo'] ?? '');
    $marca         = trim($data['marca'] ?? '');
    $cor           = trim($data['cor'] ?? '');
    $armazenamento = trim($data['armazenamento'] ?? '');
    $imei          = trim($data['imei'] ?? '');
    $diaria        = (float) ($data['diaria'] ?? 0);
    $status        = $data['status'] ?? 'disponivel';
    $id            = (int) ($data['id'] ?? 0);

    if (!$modelo || !$marca) { echo json_encode(['erro' => 'Modelo e marca são obrigatórios.']); exit; }
    if ($diaria <= 0)         { echo json_encode(['erro' => 'Informe um valor de diária válido.']); exit; }

    $statusValidos = ['disponivel', 'manutencao', 'locado'];
    if (!in_array($status, $statusValidos)) { echo json_encode(['erro' => 'Status inválido.']); exit; }

    if ($id) {
        $stmt = $pdo->prepare('UPDATE dispositivos SET modelo=?, marca=?, cor=?, armazenamento=?, imei=?, diaria=?, status=? WHERE id=?');
        $stmt->execute([$modelo, $marca, $cor, $armazenamento, $imei, $diaria, $status, $id]);
        echo json_encode(['sucesso' => true, 'id' => $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO dispositivos (modelo, marca, cor, armazenamento, imei, diaria, status) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$modelo, $marca, $cor, $armazenamento, $imei, $diaria, $status]);
        echo json_encode(['sucesso' => true, 'id' => (int) $pdo->lastInsertId()]);
    }
    exit;
}

// ===== EXCLUIR — apenas admin =====
if ($method === 'POST' && $action === 'excluir') {
    if (!isAdmin()) { echo json_encode(['erro' => 'Acesso negado.']); exit; }

    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int) ($data['id'] ?? 0);

    // Verifica locação ativa
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM locacoes WHERE dispositivo_id = ? AND status = 'ativa'");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['erro' => 'Dispositivo possui locação ativa e não pode ser excluído.']);
        exit;
    }

    $pdo->prepare('DELETE FROM dispositivos WHERE id = ?')->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

echo json_encode(['erro' => 'Ação inválida.']);
