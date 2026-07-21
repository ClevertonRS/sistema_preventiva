<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preventivaId = $_POST['preventiva_id'] ?? null;
    $descricao = trim($_POST['descricao'] ?? '');

    if ($preventivaId && !empty($descricao)) {

        // 1. Atualiza os dados principais da preventiva/ocorrência
        $stmt = $pdo->prepare("
            UPDATE ocorrencias 
            SET descricao = :descricao, status = 'Concluída'
            WHERE id = :id
        ");
        $stmt->execute([
            ':descricao' => $descricao,
            ':id' => $preventivaId
        ]);

        // 2. Processa a foto enviada pelo PWA
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $nomeOriginal = $_FILES['foto']['name'];
            $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
            if (empty($extensao)) $extensao = 'jpg';

            // Gera um nome único para o arquivo no servidor
            $novoNome = 'preventiva_' . $preventivaId . '_' . time() . '.' . $extensao;
            $pastaUpload = __DIR__ . '/uploads/';

            if (!is_dir($pastaUpload)) {
                mkdir($pastaUpload, 0755, true);
            }

            $caminhoRelativo = 'uploads/' . $novoNome;
            $destino = $pastaUpload . $novoNome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                
                // 3. Salva os metadados da imagem na tabela preventivas_arquivos
                $stmtArquivo = $pdo->prepare("
                    INSERT INTO preventivas_arquivos 
                    (preventiva_id, tipo, caminho_arquivo, nome_original, enviado_por, criado_em) 
                    VALUES (:preventiva_id, :tipo, :caminho_arquivo, :nome_original, :enviado_por, NOW())
                ");

                $stmtArquivo->execute([
                    ':preventiva_id'   => $preventivaId,
                    ':tipo'            => 'foto',
                    ':caminho_arquivo' => $caminhoRelativo,
                    ':nome_original'   => $nomeOriginal,
                    ':enviado_por'     => $_SESSION['usuario_id']
                ]);
            }
        }
    }

    header('Location: /dashboard');
    exit;
}