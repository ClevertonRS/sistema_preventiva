<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /concluidas');
    exit;
}

$tecnicoId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM preventivas_rede WHERE id = :id AND tecnico_id = :tecnico_id AND status = 'Concluída' LIMIT 1");
$stmt->execute([':id' => $id, ':tecnico_id' => $tecnicoId]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /concluidas');
    exit;
}

$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE preventiva_id = :id ORDER BY criado_em ASC");
$stmtArquivos->execute([':id' => $id]);
$arquivos = $stmtArquivos->fetchAll();

$descricao = $p['descricao'] ?? $p['observacao_abertura'] ?? $p['observacao'] ?? null;
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Detalhe da Preventiva</h1>
      <p class="text-sm text-gray-500 mt-1">Informações, descrição e imagens do serviço concluído.</p>
    </div>
    <a href="/concluidas" class="text-xs text-vivo-purple font-semibold">Voltar</a>
  </div>
</div>

<main class="p-4 max-w-3xl mx-auto w-full space-y-4 flex-grow">

  <!-- Dados do serviço -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-extrabold text-vivo-dark">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h2>
        <?php if (!empty($p['titulo'])): ?>
          <p class="text-sm text-gray-600 mt-0.5"><?= htmlspecialchars($p['titulo']) ?></p>
        <?php endif; ?>
        <p class="text-xs text-gray-400 mt-1">
          Aberta em: <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?>
          <?php if (!empty($p['concluido_em'])): ?>
            • Concluída em: <?= date('d/m/Y H:i', strtotime($p['concluido_em'])) ?>
          <?php endif; ?>
        </p>
      </div>
      <span class="inline-flex items-center text-[10px] uppercase tracking-[0.22em] px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-200 shrink-0">Concluído</span>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
      <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500 space-y-1">
        <p><strong class="text-gray-700">GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
        <p><strong class="text-gray-700">Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
        <p><strong class="text-gray-700">Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
        <p><strong class="text-gray-700">UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
      </div>
      <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500 space-y-1">
        <p><strong class="text-gray-700">Prioridade:</strong> <?= htmlspecialchars($p['prioridade']) ?></p>
        <?php if (!empty($p['chave_combinacao'])): ?>
          <p><strong class="text-gray-700">Chave Combinação:</strong> <?= htmlspecialchars($p['chave_combinacao']) ?></p>
        <?php endif; ?>
        <?php if (!empty($p['tecnico_nome'] ?? '')): ?>
          <p><strong class="text-gray-700">Técnico:</strong> <?= htmlspecialchars($p['tecnico_nome']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Descrição do técnico -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-3">
    <h3 class="text-sm font-bold text-vivo-purple">Descrição do técnico</h3>
    <div class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
      <?php if (!empty($descricao)): ?>
        <?= nl2br(htmlspecialchars($descricao)) ?>
      <?php else: ?>
        <span class="text-gray-400">Nenhuma descrição registrada para este serviço.</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Imagens enviadas -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-bold text-vivo-purple">Imagens do serviço</h3>
      <span class="text-xs text-gray-400"><?= count($arquivos) ?> arquivo(s)</span>
    </div>

    <?php if (empty($arquivos)): ?>
      <div class="rounded-2xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
        Nenhuma imagem registrada para esta preventiva.
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 gap-4">
        <?php foreach ($arquivos as $arq): ?>
          <div class="space-y-1">
            <a href="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" target="_blank">
              <img
                src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>"
                alt="Foto da preventiva"
                class="w-full h-[220px] object-cover rounded-xl border border-gray-200 shadow-sm hover:opacity-90 transition"
              >
            </a>
            <p class="text-[10px] text-gray-400"><?= htmlspecialchars($arq['nome_original'] ?? basename($arq['caminho_arquivo'])) ?></p>
            <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
