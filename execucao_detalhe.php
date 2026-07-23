<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /execucao');
    exit;
}

$tecnicoId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM preventivas_rede WHERE id = :id AND tecnico_id = :tecnico_id LIMIT 1");
$stmt->execute([':id' => $id, ':tecnico_id' => $tecnicoId]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /execucao');
    exit;
}

$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE preventiva_id = :id AND enviado_por = :tecnico_id ORDER BY criado_em DESC");
$stmtArquivos->execute([':id' => $id, ':tecnico_id' => $tecnicoId]);
$arquivos = $stmtArquivos->fetchAll();
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Detalhes da Preventiva</h1>
      <p class="text-sm text-gray-500 mt-1">Informações e fotos registradas por você.</p>
    </div>
    <a href="/execucao" class="text-xs text-vivo-purple font-semibold">Voltar</a>
  </div>
</div>

<main class="p-4 max-w-3xl mx-auto w-full space-y-4 flex-grow">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-3">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-extrabold text-vivo-dark">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h2>
                <p class="text-xs text-gray-500 mt-1">Status: <span class="font-semibold text-gray-700"><?= htmlspecialchars($p['status']) ?></span></p>
            </div>
            <span class="inline-flex items-center text-[10px] uppercase tracking-[0.22em] px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">Em Execução</span>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500">
                <p><strong class="text-gray-700">GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
                <p><strong class="text-gray-700">Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
                <p><strong class="text-gray-700">Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
                <p><strong class="text-gray-700">UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
                <?php if (!empty($p['latitude']) && !empty($p['longitude'])): ?>
                    <p><strong class="text-gray-700">Localização:</strong>
                        <a href="https://www.google.com/maps?q=<?= $p['latitude'] ?>,<?= $p['longitude'] ?>" target="_blank" class="text-vivo-purple underline">
                            <?= $p['latitude'] ?>, <?= $p['longitude'] ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500">
                <p><strong class="text-gray-700">Prioridade:</strong> <?= htmlspecialchars($p['prioridade']) ?></p>
                <p><strong class="text-gray-700">Chave Combinação:</strong> <?= htmlspecialchars($p['chave_combinacao']) ?></p>
                <p><strong class="text-gray-700">Aberta em:</strong> <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="text-sm font-bold text-vivo-purple">Descrição do técnico</h3>
        <p class="mt-3 text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
            <?= nl2br(htmlspecialchars($p['observacao_abertura'] ?? $p['descricao'] ?? 'Nenhuma descrição registrada.')) ?>
        </p>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-vivo-purple">Fotos enviadas por você</h3>
            <span class="text-xs text-gray-400"><?= count($arquivos) ?> arquivo(s)</span>
        </div>

        <?php if (empty($arquivos)): ?>
            <div class="rounded-2xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
                Ainda não há imagens registradas por você para esta preventiva.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($arquivos as $arq): ?>
                    <div class="space-y-1">
                        <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Foto da preventiva" class="w-full h-[200px] object-cover rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400"><?= htmlspecialchars($arq['nome_original'] ?? basename($arq['caminho_arquivo'])) ?></p>
                        <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>