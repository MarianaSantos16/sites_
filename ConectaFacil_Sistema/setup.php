<?php
/**
 * ConectaFácil — Setup Inicial
 * Acesse: http://localhost/ConectaFacil_Sistema/setup.php
 * APAGUE após o uso.
 */

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$ok = [];

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;color:red;padding:30px"><h2>❌ MySQL não conectou</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
}

// ── Banco ─────────────────────────────────────────────────────
$pdo->exec("CREATE DATABASE IF NOT EXISTS `conectafacil` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `conectafacil`");
$ok[] = 'Banco <strong>conectafacil</strong> OK';

// ── Tabelas ───────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin','operador') NOT NULL DEFAULT 'operador',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS dispositivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo VARCHAR(100) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    cor VARCHAR(50),
    armazenamento VARCHAR(30),
    imei VARCHAR(20),
    diaria DECIMAL(10,2) NOT NULL,
    status ENUM('disponivel','locado','manutencao') NOT NULL DEFAULT 'disponivel',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS locacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nome VARCHAR(150) NOT NULL,
    cliente_cpfcnpj VARCHAR(30) NOT NULL,
    cliente_telefone VARCHAR(20) NOT NULL,
    dispositivo_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim_prevista DATE NOT NULL,
    data_fim_real DATE,
    status ENUM('ativa','concluida','cancelada') NOT NULL DEFAULT 'ativa',
    observacoes TEXT,
    valor_diaria DECIMAL(10,2) NOT NULL,
    multa_dano DECIMAL(10,2) DEFAULT 0,
    multa_atraso DECIMAL(10,2) DEFAULT 0,
    valor_total DECIMAL(10,2),
    danos TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivos(id)
) ENGINE=InnoDB");

$ok[] = 'Tabelas criadas/verificadas';

// ── Limpa dados antigos para reinserir ────────────────────────
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("TRUNCATE TABLE locacoes");
$pdo->exec("TRUNCATE TABLE dispositivos");
$pdo->exec("TRUNCATE TABLE usuarios");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

// ── Usuários ──────────────────────────────────────────────────
$pdo->exec("INSERT INTO usuarios (nome, usuario, senha, perfil) VALUES
('Carlos Eduardo Silva',  'admin',     'admin123', 'admin'),
('Fernanda Oliveira',     'fernanda',  'fer456',   'operador'),
('Ricardo Mendes',        'ricardo',   'ric789',   'operador')");
$ok[] = '3 usuários inseridos (admin / fernanda / ricardo)';

// ── Dispositivos ──────────────────────────────────────────────
$pdo->exec("INSERT INTO dispositivos (modelo, marca, cor, armazenamento, imei, diaria, status) VALUES
('iPhone 15 Pro',        'Apple',     'Titânio Natural', '256GB', '352099001761481', 89.90, 'disponivel'),
('iPhone 14',            'Apple',     'Azul',            '128GB', '352099001761482', 65.00, 'locado'),
('iPhone 13',            'Apple',     'Meia-Noite',      '128GB', '352099001761483', 49.90, 'disponivel'),
('Galaxy S24 Ultra',     'Samsung',   'Preto Titânio',   '256GB', '352099001761484', 95.00, 'locado'),
('Galaxy S23 FE',        'Samsung',   'Grafite',         '128GB', '352099001761485', 42.00, 'disponivel'),
('Galaxy A55',           'Samsung',   'Azul Gelo',       '128GB', '352099001761486', 32.00, 'disponivel'),
('Galaxy A35',           'Samsung',   'Preto',           '128GB', '352099001761487', 25.00, 'manutencao'),
('Moto Edge 40',         'Motorola',  'Eclipse Black',   '256GB', '352099001761488', 38.00, 'disponivel'),
('Moto G84',             'Motorola',  'Marsala',         '256GB', '352099001761489', 28.00, 'locado'),
('Moto G54',             'Motorola',  'Azul',            '128GB', '352099001761490', 22.00, 'disponivel'),
('Redmi Note 13 Pro',    'Xiaomi',    'Preto Meia-Noite','256GB', '352099001761491', 35.00, 'disponivel'),
('Redmi 13C',            'Xiaomi',    'Azul Marinho',    '128GB', '352099001761492', 18.00, 'disponivel'),
('iPad Air 5ª Geração',  'Apple',     'Cinza Espacial',  '64GB',  '352099001761493', 75.00, 'locado'),
('iPad 10ª Geração',     'Apple',     'Amarelo',         '64GB',  '352099001761494', 55.00, 'disponivel'),
('Galaxy Tab S9 FE',     'Samsung',   'Grafite',         '128GB', '352099001761495', 60.00, 'disponivel')");
$ok[] = '15 dispositivos inseridos';

// ── Locações históricas (concluídas) ─────────────────────────
// Usamos datas relativas ao mês atual para os relatórios funcionarem
$pdo->exec("INSERT INTO locacoes
    (cliente_nome, cliente_cpfcnpj, cliente_telefone, dispositivo_id,
     data_inicio, data_fim_prevista, data_fim_real,
     status, observacoes, valor_diaria, multa_dano, multa_atraso, valor_total, danos)
VALUES
-- Concluídas do mês atual
('Marcos Antônio Ferreira',  '382.451.920-15', '(11) 98234-5671', 1,
 DATE_FORMAT(NOW(), '%Y-%m-02'), DATE_FORMAT(NOW(), '%Y-%m-07'), DATE_FORMAT(NOW(), '%Y-%m-07'),
 'concluida', 'Cliente frequente, sempre pontual', 89.90, 0, 0, 449.50, NULL),

('Juliana Costa Ramos',      '045.782.310-88', '(21) 97654-3210', 3,
 DATE_FORMAT(NOW(), '%Y-%m-03'), DATE_FORMAT(NOW(), '%Y-%m-06'), DATE_FORMAT(NOW(), '%Y-%m-08'),
 'concluida', 'Evento corporativo', 49.90, 0, 49.90, 249.40, NULL),

('Tech Solutions Ltda',      '18.234.567/0001-45', '(11) 3456-7890', 5,
 DATE_FORMAT(NOW(), '%Y-%m-01'), DATE_FORMAT(NOW(), '%Y-%m-05'), DATE_FORMAT(NOW(), '%Y-%m-05'),
 'concluida', 'Treinamento de equipe', 42.00, 0, 0, 168.00, NULL),

('Pedro Henrique Alves',     '521.034.870-62', '(31) 99123-4567', 6,
 DATE_FORMAT(NOW(), '%Y-%m-04'), DATE_FORMAT(NOW(), '%Y-%m-09'), DATE_FORMAT(NOW(), '%Y-%m-09'),
 'concluida', NULL, 32.00, 0, 0, 160.00, NULL),

('Construtora Horizonte SA', '72.345.891/0001-23', '(41) 3234-5678', 10,
 DATE_FORMAT(NOW(), '%Y-%m-01'), DATE_FORMAT(NOW(), '%Y-%m-04'), DATE_FORMAT(NOW(), '%Y-%m-06'),
 'concluida', 'Obra em campo, aparelho devolvido com arranhões', 22.00, 80.00, 22.00, 168.00, 'Tela com arranhões leves na lateral'),

('Beatriz Souza Lima',       '198.345.670-44', '(85) 98765-4321', 11,
 DATE_FORMAT(NOW(), '%Y-%m-05'), DATE_FORMAT(NOW(), '%Y-%m-10'), DATE_FORMAT(NOW(), '%Y-%m-10'),
 'concluida', 'Viagem a trabalho', 35.00, 0, 0, 175.00, NULL),

('Grupo Educacional Saber',  '34.567.890/0001-12', '(11) 2345-6789', 15,
 DATE_FORMAT(NOW(), '%Y-%m-02'), DATE_FORMAT(NOW(), '%Y-%m-08'), DATE_FORMAT(NOW(), '%Y-%m-08'),
 'concluida', 'Uso em sala de aula', 60.00, 0, 0, 360.00, NULL),

('Fernando Gomes Neto',      '673.210.540-91', '(51) 99234-5678', 12,
 DATE_FORMAT(NOW(), '%Y-%m-06'), DATE_FORMAT(NOW(), '%Y-%m-09'), DATE_FORMAT(NOW(), '%Y-%m-09'),
 'concluida', NULL, 18.00, 0, 0, 54.00, NULL),

-- Locações ativas (aparelhos atualmente locados: 2, 4, 9, 13)
('Rodrigo Nascimento',       '234.567.890-11', '(11) 97890-1234', 2,
 DATE_FORMAT(NOW(), '%Y-%m-10'), DATE_ADD(NOW(), INTERVAL 5 DAY), NULL,
 'ativa', 'Substituição de aparelho pessoal em reparo', 65.00, 0, 0, NULL, NULL),

('Eventos & Cia Ltda',       '56.789.012/0001-34', '(21) 3456-7891', 4,
 DATE_FORMAT(NOW(), '%Y-%m-08'), DATE_ADD(NOW(), INTERVAL 3 DAY), NULL,
 'ativa', 'Cobertura fotográfica de evento', 95.00, 0, 0, NULL, NULL),

('Luciana Pereira Santos',   '456.789.012-33', '(62) 98901-2345', 9,
 DATE_FORMAT(NOW(), '%Y-%m-11'), DATE_ADD(NOW(), INTERVAL 7 DAY), NULL,
 'ativa', 'Uso pessoal durante viagem', 28.00, 0, 0, NULL, NULL),

('Instituto Conecta Digital', '90.123.456/0001-78', '(11) 4567-8901', 13,
 DATE_FORMAT(NOW(), '%Y-%m-09'), DATE_ADD(NOW(), INTERVAL 4 DAY), NULL,
 'ativa', 'Capacitação de professores', 75.00, 0, 0, NULL, NULL)
");
$ok[] = '12 locações inseridas (8 concluídas + 4 ativas)';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup — ConectaFácil</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #2563EB; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #fff; border-radius: 16px; padding: 40px; max-width: 540px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,0.25); }
    h1 { font-size: 22px; margin-bottom: 4px; }
    .sub { color: #64748B; font-size: 13px; margin-bottom: 24px; }
    .item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #E2E8F0; font-size: 13px; }
    .item:last-child { border-bottom: none; }
    .creds { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 16px; margin: 20px 0; font-size: 13px; }
    .creds h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; margin-bottom: 10px; }
    .cred-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #E2E8F0; }
    .cred-row:last-child { border-bottom: none; }
    .btn { display: inline-block; margin-top: 4px; padding: 12px 28px; background: #2563EB; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
    .warn { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px 16px; font-size: 12px; color: #92400E; margin-top: 16px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>✅ Setup Concluído</h1>
    <p class="sub">Banco populado com dados realistas para demonstração.</p>

    <?php foreach ($ok as $msg): ?>
      <div class="item"><span>✓</span><span><?= $msg ?></span></div>
    <?php endforeach; ?>

    <div class="creds">
      <h3>Credenciais de Acesso</h3>
      <div class="cred-row"><span>👑 <strong>admin</strong> — Carlos Eduardo Silva</span><span><code>admin123</code></span></div>
      <div class="cred-row"><span>👤 <strong>fernanda</strong> — Fernanda Oliveira</span><span><code>fer456</code></span></div>
      <div class="cred-row"><span>👤 <strong>ricardo</strong> — Ricardo Mendes</span><span><code>ric789</code></span></div>
    </div>

    <a href="index.php" class="btn">Ir para o Login →</a>

    <div class="warn">⚠️ <strong>Apague o arquivo setup.php</strong> após o primeiro acesso.</div>
  </div>
</body>
</html>
