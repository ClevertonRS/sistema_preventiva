<?php
// Inclui o arquivo de conexão com o banco de dados
require_once 'config/db.php';

$mensagem = '';
$tipo_mensagem = ''; // 'sucesso' ou 'erro'

// Processa o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome            = trim($_POST['nome'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $senha           = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // 1. Validações básicas dos campos
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
        $tipo_mensagem = "erro";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Formato de e-mail inválido.";
        $tipo_mensagem = "erro";
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = "A senha e a confirmação de senha não coincidem!";
        $tipo_mensagem = "erro";
    } elseif (strlen($senha) < 6) {
        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
        $tipo_mensagem = "erro";
    } else {
        try {
            // 2. Verifica se o e-mail já está cadastrado no banco
            $sqlCheck = "SELECT id FROM usuarios WHERE email = :email";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->rowCount() > 0) {
                $mensagem = "Este e-mail já está cadastrado no sistema!";
                $tipo_mensagem = "erro";
            } else {
                // 3. Criptografa a senha com hash seguro (BCRYPT)
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // 4. Insere o novo usuário no banco de dados
                $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
                $stmt = $pdo->prepare($sql);
                
                $sucesso = $stmt->execute([
                    ':nome'  => $nome,
                    ':email' => $email,
                    ':senha' => $senha_hash
                ]);

                if ($sucesso) {
                    $mensagem = "Usuário cadastrado com sucesso!";
                    $tipo_mensagem = "sucesso";
                    
                    // Limpa os campos após o sucesso
                    $nome = '';
                    $email = '';
                }
            }
        } catch (PDOException $e) {
            $mensagem = "Erro no banco de dados: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Usuário</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 40px 20px; }
        .container { max-width: 450px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; text-align: center; margin-bottom: 20px; }
        .campo { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="email"], input[type="password"] { 
            width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; 
        }
        input:focus { border-color: #0d6efd; outline: none; }
        button { 
            width: 100%; padding: 12px; background-color: #198754; color: white; border: none; border-radius: 4px; 
            font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 10px;
        }
        button:hover { background-color: #157347; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .sucesso { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .erro { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>

<div class="container">
    <h2>👤 Novo Usuário</h2>

    <?php if (!empty($mensagem)): ?>
        <div class="alert <?= $tipo_mensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="campo">
            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($nome ?? '') ?>">
        </div>

        <div class="campo">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
        </div>

        <div class="campo">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres">
        </div>

        <div class="campo">
            <label for="confirmar_senha">Confirmar Senha:</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required>
        </div>

        <button type="submit">Cadastrar Usuário</button>
    </form>
</div>

</body>
</html>