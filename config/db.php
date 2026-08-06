<?php
require_once __DIR__ . '/env.php';

$host    = $_ENV['DB_HOST'] ?? 'localhost';
$dbname  = $_ENV['DB_NAME'] ?? 'tom_preventiva';
$usuario = $_ENV['DB_USER'] ?? 'tom_preventiva';
$senha   = $_ENV['DB_PASS'] ?? 'Filhotinho';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
####################################
try {
    // Conexão via PDO para MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Em produção, não mostrar detalhes do erro
    if (($_ENV['APP_DEBUG'] ?? 'false') !== 'true') {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    }

} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    die('Erro ao conectar ao banco de dados. Tente novamente mais tarde.');
}