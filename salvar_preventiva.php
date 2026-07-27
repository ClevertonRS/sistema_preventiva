<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /preventivas');
    exit;
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    security_log('CSRF inválido em salvar_preventiva', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    header('Location: /preventivas');
    exit;
}

$preventivaId = (int)($_POST['preventiva_id'] ?? 0);
$atendimentoId = (int)($_POST['atendimento_id'] ?? 0);
$descricaoAnalise = trim($_POST['descricao_analise'] ?? '');
$descricaoExecucao = trim($_POST['descricao_execucao'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

if (isset($_POST['modo_atendimento'])) {
    $acao = ($_POST['modo_atendimento'] === 'completo') ? 'aceitar_finalizar' : 'iniciar_analise';
} else {
    $acao = $_POST['acao'] ?? 'finalizar';
}

if (!$preventivaId) {
    header('Location: /preventivas');
    exit;
}

if (empty($latitude) || empty($longitude)) {
    security_log('Tentativa sem localização', ['acao' => $acao, 'prev_id' => $preventivaId]);
    header('Location: /preventiva/' . $preventivaId . '?erro=localizacao');
    exit;
}

$stmt = $pdo->prepare("SELECT id, status FROM preventivas_rede WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $preventivaId]);
$prev = $stmt->fetch();

if (!$prev) {
    header('Location: /preventivas');
    exit;
}

$userId = $_SESSION['user_id'];

// ============================================================
// AÇÃO: iniciar_analise — Técnico faz apenas a análise primária
// ============================================================
if ($acao === 'iniciar_analise' && $prev['status'] === 'aberta') {
    if (empty($descricaoAnalise)) {
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO atendimentos (preventiva_id, tecnico_analise_id, descricao_analise, status, iniciado_em, criado_em, latitude_analise, longitude_analise)
             VALUES (:preventiva_id, :tecnico_id, :descricao, 'analise', NOW(), NOW(), :lat, :lng)"
        );
        $stmt->execute([
            ':preventiva_id' => $preventivaId,
            ':tecnico_id' => $userId,
            ':descricao' => $descricaoAnalise,
            ':lat' => $latitude,
            ':lng' => $longitude,
        ]);
        $novoAtendimentoId = $pdo->lastInsertId();

        $pdo->prepare("UPDATE preventivas_rede SET status = 'em_atendimento' WHERE id = :id")
            ->execute([':id' => $preventivaId]);

        salvarFotos($pdo, $preventivaId, $novoAtendimentoId, $userId);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        security_log('Erro ao iniciar análise', ['error' => $e->getMessage(), 'prev_id' => $preventivaId]);
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }
}

// ============================================================
// AÇÃO: aceitar_finalizar — Técnico faz tudo de uma vez
// ============================================================
elseif ($acao === 'aceitar_finalizar' && $prev['status'] === 'aberta') {
    if (empty($descricaoAnalise)) {
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO atendimentos (preventiva_id, tecnico_analise_id, tecnico_execucao_id, descricao_analise, descricao_execucao, status, iniciado_em, concluido_em, criado_em, latitude_analise, longitude_analise, latitude_execucao, longitude_execucao)
             VALUES (:preventiva_id, :tecnico_id, :tecnico_id2, :desc_analise, :desc_execucao, 'concluido', NOW(), NOW(), NOW(), :lat_a, :lng_a, :lat_e, :lng_e)"
        );
        $stmt->execute([
            ':preventiva_id' => $preventivaId,
            ':tecnico_id' => $userId,
            ':tecnico_id2' => $userId,
            ':desc_analise' => $descricaoAnalise,
            ':desc_execucao' => $descricaoExecucao ?: $descricaoAnalise,
            ':lat_a' => $latitude,
            ':lng_a' => $longitude,
            ':lat_e' => $latitude,
            ':lng_e' => $longitude,
        ]);
        $novoAtendimentoId = $pdo->lastInsertId();

        $pdo->prepare("UPDATE preventivas_rede SET status = 'concluida' WHERE id = :id")
            ->execute([':id' => $preventivaId]);

        salvarFotos($pdo, $preventivaId, $novoAtendimentoId, $userId);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        security_log('Erro ao aceitar e finalizar', ['error' => $e->getMessage(), 'prev_id' => $preventivaId]);
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }
}

// ============================================================
// AÇÃO: assumir_execucao — 2º técnico assume e finaliza
// ============================================================
elseif ($acao === 'assumir_execucao' && $atendimentoId) {
    if (empty($descricaoExecucao)) {
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, status, tecnico_analise_id, tecnico_execucao_id FROM atendimentos WHERE id = :id AND preventiva_id = :prev_id");
    $stmt->execute([':id' => $atendimentoId, ':prev_id' => $preventivaId]);
    $atend = $stmt->fetch();

    if (!$atend || $atend['status'] !== 'analise' || $atend['tecnico_execucao_id']) {
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE atendimentos SET tecnico_execucao_id = :tecnico_id, descricao_execucao = :descricao, status = 'concluido', concluido_em = NOW(), latitude_execucao = :lat_exec, longitude_execucao = :lng_exec
             WHERE id = :id"
        );
        $stmt->execute([
            ':tecnico_id' => $userId,
            ':descricao' => $descricaoExecucao,
            ':id' => $atendimentoId,
            ':lat_exec' => $latitude,
            ':lng_exec' => $longitude,
        ]);

        $pdo->prepare("UPDATE preventivas_rede SET status = 'concluida' WHERE id = :id")
            ->execute([':id' => $preventivaId]);

        salvarFotos($pdo, $preventivaId, $atendimentoId, $userId);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        security_log('Erro ao assumir execução', ['error' => $e->getMessage(), 'prev_id' => $preventivaId]);
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }
}

// ============================================================
// AÇÃO: finalizar — Técnico finaliza a execução
// ============================================================
elseif ($acao === 'finalizar' && $atendimentoId && !empty($descricao)) {
    $stmt = $pdo->prepare("SELECT id, tecnico_execucao_id, status FROM atendimentos WHERE id = :id AND preventiva_id = :prev_id");
    $stmt->execute([':id' => $atendimentoId, ':prev_id' => $preventivaId]);
    $atend = $stmt->fetch();

    if (!$atend || $atend['status'] !== 'execucao') {
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE atendimentos SET descricao_execucao = :descricao, status = 'revisao', latitude_execucao = :lat_exec, longitude_execucao = :lng_exec WHERE id = :id"
        );
        $stmt->execute([
            ':descricao' => $descricao,
            ':id' => $atendimentoId,
            ':lat_exec' => $latitude,
            ':lng_exec' => $longitude,
        ]);

        salvarFotos($pdo, $preventivaId, $atendimentoId, $userId);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        security_log('Erro ao finalizar', ['error' => $e->getMessage(), 'prev_id' => $preventivaId]);
    }
}

// ============================================================
// AÇÃO: finalizar_revisao — Técnico reenvia para revisão
// ============================================================
elseif ($acao === 'finalizar_revisao' && $atendimentoId && !empty($descricao)) {
    $stmt = $pdo->prepare("SELECT id, status FROM atendimentos WHERE id = :id AND preventiva_id = :prev_id");
    $stmt->execute([':id' => $atendimentoId, ':prev_id' => $preventivaId]);
    $atend = $stmt->fetch();

    if (!$atend || $atend['status'] !== 'revisao') {
        header('Location: /preventiva/' . $preventivaId);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE atendimentos SET descricao_execucao = :descricao, status = 'revisao', latitude_execucao = :lat_exec, longitude_execucao = :lng_exec WHERE id = :id"
        );
        $stmt->execute([
            ':descricao' => $descricao,
            ':id' => $atendimentoId,
            ':lat_exec' => $latitude,
            ':lng_exec' => $longitude,
        ]);

        salvarFotos($pdo, $preventivaId, $atendimentoId, $userId);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        security_log('Erro ao reenviar revisão', ['error' => $e->getMessage(), 'prev_id' => $preventivaId]);
    }
}

// ============================================================
// Redirecionamento seguro
// ============================================================
$returnUrl = $_POST['return_url'] ?? '';
$allowedReturns = ['/preventivas', '/triagem', '/execucao', '/revisao', '/concluidas', '/dashboard'];
if (!empty($returnUrl) && in_array($returnUrl, $allowedReturns, true)) {
    header('Location: ' . $returnUrl);
    exit;
}

header('Location: /preventiva/' . urlencode($preventivaId) . '?sucesso=1');
exit;

// ============================================================
// FUNÇÃO AUXILIAR: salvar fotos
// ============================================================
function salvarFotos(PDO $pdo, int $preventivaId, int $atendimentoId, int $userId): void {
    $pastaUpload = __DIR__ . '/uploads/';
    if (!is_dir($pastaUpload)) {
        mkdir($pastaUpload, 0755, true);
    }

    $processarFotos = function (string $inputName, string $tipo, string $momento) use ($pdo, $preventivaId, $atendimentoId, $userId, $pastaUpload) {
        if (empty($_FILES[$inputName]) || !is_array($_FILES[$inputName]['name'])) {
            return;
        }
        foreach ($_FILES[$inputName]['name'] as $index => $nomeOriginal) {
            if ($_FILES[$inputName]['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }
            $file = [
                'name'     => $_FILES[$inputName]['name'][$index],
                'type'     => $_FILES[$inputName]['type'][$index],
                'tmp_name' => $_FILES[$inputName]['tmp_name'][$index],
                'error'    => $_FILES[$inputName]['error'][$index],
                'size'     => $_FILES[$inputName]['size'][$index],
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
                $stmt = $pdo->prepare("INSERT INTO preventivas_arquivos 
                    (preventiva_id, atendimento_id, tipo, momento, caminho_arquivo, nome_original, enviado_por, criado_em) 
                    VALUES (:preventiva_id, :atendimento_id, :tipo, :momento, :caminho_arquivo, :nome_original, :enviado_por, NOW())");
                $stmt->execute([
                    ':preventiva_id'   => $preventivaId,
                    ':atendimento_id'  => $atendimentoId,
                    ':tipo'            => $tipo,
                    ':momento'         => $momento,
                    ':caminho_arquivo' => $caminhoRelativo,
                    ':nome_original'   => $nomeOriginal,
                    ':enviado_por'     => $userId,
                ]);
            }
        }
    };

    $processarFotos('foto_antes', 'analise', 'antes');
    $processarFotos('foto_depois', 'execucao', 'depois');
}
