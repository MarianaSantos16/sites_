<?php
// API — Usuários (apenas admin)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$user = exigirLogin();
if (!isAdmin()) { echo json_encode(['erro' => 'Acesso negado.']); exit; }

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ===== LISTAR =====
if ($method === 'GET' && $action === 'listar') {
    $stmt = $pdo->query('SELECT id, nome, usuario, perfil, criado_em FROM usuarios ORDER BY id');
    echo json_encode($stmt->fetchAll());
    exit;
}

// ===== SALVAR =====
if ($method === 'POST' && $action === 'salvar') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = (int) ($data['id'] ?? 0);
    $nome   = trim($data['nome'] ?? '');
    $login  = trim($data['usuario'] ?? '');
    $senha  = trim($data['senha'] ?? '');
    $perfil = $data['perfil'] ?? 'operador';

    if (!$nome || !$login) { echo json_encode(['erro' => 'Nome e usuário são obrigatórios.']); exit; }
    if (!in_array($perfil, ['admin', 'operador'])) { echo json_encode(['erro' => 'Perfil inválido.']); exit; }

    if ($id) {
        // Edição — senha opcional
        if ($senha) {
            if (strlen($senha) < 4) { echo json_encode(['erro' => 'A senha deve ter pelo menos 4 caracteres.']); exit; }
            $stmt = $pdo->prepare('UPDATE usuarios SET nome=?, usuario=?, senha=?, perfil=? WHERE id=?');
            $stmt->execute([$nome, $login, $senha, $perfil, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET nome=?, usuario=?, perfil=? WHERE id=?');
            $stmt->execute([$nome, $login, $perfil, $id]);
        }
        echo json_encode(['sucesso' => true, 'id' => $id]);
    } else {
        // Criação
        if (!$senha || strlen($senha) < 4) { echo json_encode(['erro' => 'A senha deve ter pelo menos 4 caracteres.']); exit; }

        // Verifica username único
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = ?');
        $stmt->execute([$login]);
        if ($stmt->fetchColumn() > 0) { echo json_encode(['erro' => 'Nome de usuário já cadastrado.']); exit; }

        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, usuario, senha, perfil) VALUES (?,?,?,?)');
        $stmt->execute([$nome, $login, $senha, $perfil]);
        echo json_encode(['sucesso' => true, 'id' => (int) $pdo->lastInsertId()]);
    }
    exit;
}

// ===== EXCLUIR =====
if ($method === 'POST' && $action === 'excluir') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int) ($data['id'] ?? 0);

    if ($id === 1) { echo json_encode(['erro' => 'O administrador principal não pode ser excluído.']); exit; }
    if ($id === $user['id']) { echo json_encode(['erro' => 'Você não pode excluir sua própria conta.']); exit; }

    $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}

echo json_encode(['erro' => 'Ação inválida.']);
