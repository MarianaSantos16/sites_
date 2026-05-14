<?php
// ===== CONFIGURAÇÃO DO BANCO DE DADOS =====

define('DB_HOST',    'localhost');
define('DB_NAME',    'conectafacil');
define('DB_USER',    'root');   // altere se necessário
define('DB_PASS',    '');       // altere se necessário
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        // Lança PDOException — o chamador decide o que fazer
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
