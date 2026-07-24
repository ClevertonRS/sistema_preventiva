<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        security_log('CSRF inválido em salvar_preventiva', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        header('Location: /preventivas');
        exit;
    }

    $preventivaId = $_POST['preventiva_id'] ?? null;
    $acao = $_POST['acao'] ?? 'finalizar';
    $descricao = trim($_POST['descricao'] ?? '');
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    $locationSet = (!empty($latitude) && !empty($longitude)) ? ", latitude = :latitude, longitude = :longitude" : '';

    if (!$preventivaId) {
        header('Location: /preventivas');
        exit;
    }

    // Verifica ownership da preventiva
    $stmt = $pdo->prepare("SELECT id, tecnico_id, status FROM preventivas_rede WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $preventivaId]);
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prev) {
        header('Location: /preventivas');
        exit;
    }

    // Ações que exigem ownership (técnico dono)
    $isOwner = (int)$prev['tecnico_id'] === (int)$_SESSION['user_id'];
    $isSupervisor = ($_SESSION['user_nivel'] ?? 1) >= 2;

    if (in_array($acao, ['finalizar', 'aceitar_finalizar'], true) && !$isOwner && !$isSupervisor) {
        security_log('Tentativa de finalizar OS de outro técnico', ['user_id' => $_SESSION['user_id'], 'prev_id' => $preventivaId]);
        header('Location: /preventivas');
        exit;
    }

    // Aceitar apenas (Triagem -> Em Execução)
    if ($acao === 'aceitar') {
        $sql = "UPDATE preventivas_rede SET status = 'Em Execução', enviado_execucao_em = NOW(), tecnico_id = :tecnico_id$locationSet WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $params = [':id' => $preventivaId, ':tecnico_id' => $_SESSION['user_id']];
        if (!empty($latitude) && !empty($longitude)) {
            $params[':latitude'] = $latitude;
            $params[':longitude'] = $longitude;
        }
        $stmt->execute($params);
    }
    // Aceitar e Finalizar direto (Triagem -> Concluída/Em Análise)
    elseif ($acao === 'aceitar_finalizar' && !empty($descricao)) {
        try {
            $pdo->beginTransaction();

            // 1. Aceitar: atribui técnico, muda para Em Execução
            $sql = "UPDATE preventivas_rede SET status = 'Em Execução', enviado_execucao_em = NOW(), tecnico_id = :tecnico_id$locationSet WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $preventivaId, ':tecnico_id' => $_SESSION['user_id']];
            if (!empty($latitude) && !empty($longitude)) {
                $params[':latitude'] = $latitude;
                $params[':longitude'] = $longitude;
            }
            $stmt->execute($params);

            // 2. Finalizar: salva descrição, muda para Concluída
            $sql2 = "UPDATE preventivas_rede SET observacao_abertura = :descricao, status = 'Concluída', enviado_revisao_em = NOW()$locationSet WHERE id = :id";
            $stmt2 = $pdo->prepare($sql2);
            $params2 = [':descricao' => $descricao, ':id' => $preventivaId];
            if (!empty($latitude) && !empty($longitude)) {
                $params2[':latitude'] = $latitude;
                $params2[':longitude'] = $longitude;
            }
            $stmt2->execute($params2);

            // 3. Upload de fotos
            if (!empty($_FILES['foto']) && is_array($_FILES['foto']['name'])) {
                $pastaUpload = __DIR__ . '/uploads/';
                if (!is_dir($pastaUpload)) {
                    mkdir($pastaUpload, 0755, true);
                }

                foreach ($_FILES['foto']['name'] as $index => $nomeOriginal) {
                    if ($_FILES['foto']['error'][$index] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $file = [
                        'name' => $_FILES['foto']['name'][$index],
                        'type' => $_FILES['foto']['type'][$index],
                        'tmp_name' => $_FILES['foto']['tmp_name'][$index],
                        'error' => $_FILES['foto']['error'][$index],
                        'size' => $_FILES['foto']['size'][$index],
                    ];

                    $validated = validate_upload($file);
                    if (!$validated['ok']) {
                        security_log('Upload rejeitado', ['error' => $validated['error'], 'file' => $file['name']]);
                        continue;
                    }

                    $novoNome = safe_filename('preventiva_' . $preventivaId, $validated['ext']);
                    $destino = $pastaUpload . $novoNome;

                    if (move_uploaded_file($validated['tmp_name'], $destino)) {
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

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            security_log('Erro ao aceitar e finalizar', ['error' => $e->getMessage(), 'prev_id' => $preventivaId]);
        }
    }
    // Finalizar normal (Em Execução -> Concluída)
    elseif ($acao === 'finalizar' && !empty($descricao)) {
        $sql = "UPDATE preventivas_rede SET observacao_abertura = :descricao, status = 'Concluída', enviado_revisao_em = NOW()$locationSet WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $params = [':descricao' => $descricao, ':id' => $preventivaId];
        if (!empty($latitude) && !empty($longitude)) {
            $params[':latitude'] = $latitude;
            $params[':longitude'] = $longitude;
        }
        $stmt->execute($params);

        // Upload seguro
        if (!empty($_FILES['foto']) && is_array($_FILES['foto']['name'])) {
            $pastaUpload = __DIR__ . '/uploads/';
            if (!is_dir($pastaUpload)) {
                mkdir($pastaUpload, 0755, true);
            }

            foreach ($_FILES['foto']['name'] as $index => $nomeOriginal) {
                if ($_FILES['foto']['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $file = [
                    'name' => $_FILES['foto']['name'][$index],
                    'type' => $_FILES['foto']['type'][$index],
                    'tmp_name' => $_FILES['foto']['tmp_name'][$index],
                    'error' => $_FILES['foto']['error'][$index],
                    'size' => $_FILES['foto']['size'][$index],
                ];

                $validated = validate_upload($file);
                if (!$validated['ok']) {
                    security_log('Upload rejeitado', ['error' => $validated['error'], 'file' => $file['name']]);
                    continue;
                }

                $novoNome = safe_filename('preventiva_' . $preventivaId, $validated['ext']);
                $destino = $pastaUpload . $novoNome;

                if (move_uploaded_file($validated['tmp_name'], $destino)) {
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

    // Safe redirect (allowlist)
    $returnUrl = $_POST['return_url'] ?? '';
    $allowedReturns = ['/preventivas', '/triagem', '/execucao', '/revisao', '/concluidas', '/dashboard'];
    if (!empty($returnUrl) && in_array($returnUrl, $allowedReturns, true)) {
        header('Location: ' . $returnUrl);
        exit;
    }

    header('Location: /preventiva/' . urlencode($preventivaId));
    exit;
}