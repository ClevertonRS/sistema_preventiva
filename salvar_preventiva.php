<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preventivaId = $_POST['preventiva_id'] ?? null;
    $acao = $_POST['acao'] ?? 'finalizar';
    $descricao = trim($_POST['descricao'] ?? '');

    if (!$preventivaId) {
        header('Location: /preventivas');
        exit;
    }

    if ($acao === 'aceitar') {
        $stmt = $pdo->prepare("UPDATE preventivas_rede SET status = 'Em Execução', enviado_execucao_em = NOW(), tecnico_id = :tecnico_id WHERE id = :id");
        $stmt->execute([':id' => $preventivaId, ':tecnico_id' => $_SESSION['user_id']]);
    } elseif ($acao === 'finalizar' && !empty($descricao)) {
        $stmt = $pdo->prepare("UPDATE preventivas_rede SET observacao_abertura = :descricao, status = 'Em Análise', enviado_revisao_em = NOW() WHERE id = :id");
        $stmt->execute([
            ':descricao' => $descricao,
            ':id' => $preventivaId
        ]);

        if (!empty($_FILES['foto']) && is_array($_FILES['foto']['name'])) {
            $pastaUpload = __DIR__ . '/uploads/';
            if (!is_dir($pastaUpload)) {
                mkdir($pastaUpload, 0755, true);
            }

            foreach ($_FILES['foto']['name'] as $index => $nomeOriginal) {
                if ($_FILES['foto']['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
                if (empty($extensao)) {
                    $extensao = 'jpg';
                }

                $novoNome = 'preventiva_' . $preventivaId . '_' . time() . '_' . $index . '.' . $extensao;
                $destino = $pastaUpload . $novoNome;

                if (move_uploaded_file($_FILES['foto']['tmp_name'][$index], $destino)) {
                    $caminhoRelativo = 'uploads/' . $novoNome;

                    $stmtArquivo = $pdo->prepare("INSERT INTO preventivas_arquivos 
                        (preventiva_id, tipo, caminho_arquivo, nome_original, enviado_por, criado_em) 
                        VALUES (:preventiva_id, :tipo, :caminho_arquivo, :nome_original, :enviado_por, NOW())");

                    $stmtArquivo->execute([
                        ':preventiva_id'   => $preventivaId,
                        ':tipo'            => 'foto',
                        ':caminho_arquivo' => $caminhoRelativo,
                        ':nome_original'   => $nomeOriginal,
                        ':enviado_por'     => $_SESSION['user_id']
                    ]);
                }
            }
        }
    }

    $returnUrl = $_POST['return_url'] ?? '';
    if (!empty($returnUrl) && strpos($returnUrl, '/') === 0) {
        header('Location: ' . $returnUrl);
        exit;
    }

    header('Location: /preventiva/' . urlencode($preventivaId));
    exit;
}