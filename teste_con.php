<?php
// teste_conexao.php

// Insira as credenciais do DirectAdmin que você quer testar
$host    = 'localhost';
$dbname  = 'tom_preventiva'; // Altere para o nome do seu banco MySQL
$usuario = 'tom'; // Altere para seu usuário do MySQL (ex: root)
$senha   = 'Filhotinho';   // Altere para sua senha do MySQL
header('Content-Type: text/html; charset=utf-8');

echo "<style>body { font-family: sans-serif; padding: 20px; line-height: 1.6; }</style>";
echo "<h2>🔍 Testando Conexão com o MySQL...</h2>";

try {
    // Tenta estabelecer a conexão
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Se chegou aqui, a conexão funcionou 100%!
    echo "<div style='background: #e6ffed; border: 1px solid #2da44e; color: #1a7f37; padding: 15px; border-radius: 8px;'>";
    echo "<h3>✅ SUCESSO! Conexão estabelecida com o banco.</h3>";
    echo "<ul>";
    echo "<li><strong>Host:</strong> $host</li>";
    echo "<li><strong>Banco:</strong> $dbname</li>";
    echo "<li><strong>Usuário:</strong> $usuario</li>";
    echo "<li><strong>Versão do MySQL:</strong> " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
    echo "</ul>";
    echo "</div>";

} catch (PDOException $e) {
    // Se der qualquer erro, mostra o motivo exato
    echo "<div style='background: #ffebe9; border: 1px solid #cf222e; color: #a40e26; padding: 15px; border-radius: 8px;'>";
    echo "<h3>❌ FALHA NA CONEXÃO</h3>";
    echo "<p><strong>Mensagem de Erro:</strong></p>";
    echo "<pre style='background: #fff; padding: 10px; border-radius: 5px; border: 1px solid #f85149; overflow-x: auto;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    echo "</div>";
}