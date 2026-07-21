<?php
// Configurações do MySQL
$host    = 'localhost';
$dbname  = 'tom_preventiva'; // Altere para o nome do seu banco MySQL
$usuario = 'tom_preventiva'; // Altere para seu usuário do MySQL (ex: root)
$senha   = 'Filhotinho';   // Altere para sua senha do MySQL
####################################
try {
    // Conexão via PDO para MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabela de Usuários (Sintaxe MySQL)
    // $pdo->exec("
    //     CREATE TABLE IF NOT EXISTS usuarios (
    //         id INT AUTO_INCREMENT PRIMARY KEY,
    //         nome VARCHAR(255) NOT NULL,
    //         usuario VARCHAR(100) UNIQUE NOT NULL,
    //         senha VARCHAR(255) NOT NULL
    //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    // ");

    // // Tabela de Preventivas (Sintaxe MySQL)
    // $pdo->exec("
    //     CREATE TABLE IF NOT EXISTS preventivas (
    //         id INT AUTO_INCREMENT PRIMARY KEY,
    //         titulo VARCHAR(255) NOT NULL,
    //         equipamento VARCHAR(255) NOT NULL,
    //         local VARCHAR(255) NOT NULL,
    //         status VARCHAR(50) DEFAULT 'Pendente',
    //         descricao TEXT,
    //         foto VARCHAR(255),
    //         tecnico_id INT,
    //         data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    //         data_conclusao DATETIME
    //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    // ");

} catch (PDOException $e) {
    die("Erro ao conectar ao banco MySQL: " . $e->getMessage());
}