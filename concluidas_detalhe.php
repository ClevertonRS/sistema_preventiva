<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /concluidas');
    exit;
}

$tecnicoId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT p.*, a.id AS atendimento_id, a.status AS atendimento_status,
            a.tecnico_analise_id, a.tecnico_execucao_id,
            a.descricao_analise, a.descricao_execucao,
            a.latitude_analise, a.longitude_analise,
            a.latitude_execucao, a.longitude_execucao,
            a.concluido_em,
            ua.nome AS nome_analista, ue.nome AS nome_executor
     FROM preventivas_rede p
     JOIN atendimentos a ON a.preventiva_id = p.id
     LEFT JOIN usuarios ua ON ua.id = a.tecnico_analise_id
     LEFT JOIN usuarios ue ON ue.id = a.tecnico_execucao_id
     WHERE p.id = :id
       AND a.status = 'concluido'
       AND (a.tecnico_analise_id = :tecnico_id OR a.tecnico_execucao_id = :tecnico_id2)
     ORDER BY a.criado_em DESC
     LIMIT 1"
);
$stmt->execute([':id' => $id, ':tecnico_id' => $tecnicoId, ':tecnico_id2' => $tecnicoId]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /concluidas');
    exit;
}

$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE atendimento_id = :atendimento_id ORDER BY criado_em ASC");
$stmtArquivos->execute([':atendimento_id' => $p['atendimento_id']]);
$arquivos = $stmtArquivos->fetchAll();
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

  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-extrabold text-vivo-dark">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h2>
        <p class="text-xs text-gray-400 mt-1">
          Aberta em: <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?>
          <?php if (!empty($p['concluido_em'])): ?>
            • Concluída em: <?= date('d/m/Y H:i', strtotime($p['concluido_em'])) ?>
          <?php endif; ?>
        </p>
      </div>
      <span class="inline-flex items-center text-[10px] uppercase tracking-[0.22em] px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-200 shrink-0">Concluído</span>
    </div>

    <div class="text-xs text-gray-500 space-y-1 border-t border-gray-100 pt-3">
      <p><strong>GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
      <p><strong>Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
      <p><strong>Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
      <p><strong>UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
    </div>
  </div>

  <?php
    $fotosAnaliseConcluida = array_filter($arquivos, fn($f) => $f['tipo'] === 'analise');
    $fotosExecucaoConcluida = array_filter($arquivos, fn($f) => $f['tipo'] === 'execucao');
  ?>

  <div class="border-l-4 border-amber-400 pl-4 space-y-2 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
    <h4 class="text-xs font-bold text-amber-700 uppercase tracking-wider">Análise</h4>
    <p class="text-xs text-gray-500"><strong>Técnico:</strong> <?= htmlspecialchars($p['nome_analista'] ?? 'N/A') ?></p>
    <?php if (!empty($p['descricao_analise'])): ?>
      <p class="text-sm text-gray-800 bg-amber-50 p-3 rounded-xl border border-amber-200">
        <?= nl2br(htmlspecialchars($p['descricao_analise'])) ?>
      </p>
    <?php endif; ?>
    <?php if (!empty($p['latitude_analise']) && !empty($p['longitude_analise'])): ?>
      <a href="https://www.google.com/maps?q=<?= $p['latitude_analise'] ?>,<?= $p['longitude_analise'] ?>" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition w-fit">
        <i data-lucide="map-pin" class="w-3 h-3"></i>Local
      </a>
    <?php endif; ?>
    <?php if (!empty($fotosAnaliseConcluida)): ?>
      <p class="text-xs font-semibold text-amber-600">Fotos - Análise:</p>
      <div class="grid grid-cols-2 gap-3">
        <?php foreach ($fotosAnaliseConcluida as $arq): ?>
          <div class="space-y-1">
            <a href="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" target="_blank">
              <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="w-full h-32 object-cover rounded-xl border border-amber-200 shadow-sm hover:opacity-90 transition">
            </a>
            <p class="text-[10px] text-gray-400 text-right"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="border-l-4 border-emerald-400 pl-4 space-y-2 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
    <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Execução</h4>
    <p class="text-xs text-gray-500"><strong>Técnico:</strong> <?= htmlspecialchars($p['nome_executor'] ?? 'N/A') ?></p>
    <?php if (!empty($p['descricao_execucao'])): ?>
      <p class="text-sm text-gray-800 bg-emerald-50 p-3 rounded-xl border border-emerald-200">
        <?= nl2br(htmlspecialchars($p['descricao_execucao'])) ?>
      </p>
    <?php endif; ?>
    <?php if (!empty($p['latitude_execucao']) && !empty($p['longitude_execucao'])): ?>
      <a href="https://www.google.com/maps?q=<?= $p['latitude_execucao'] ?>,<?= $p['longitude_execucao'] ?>" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition w-fit">
        <i data-lucide="map-pin" class="w-3 h-3"></i>Local
      </a>
    <?php endif; ?>
    <?php if (!empty($fotosExecucaoConcluida)): ?>
      <p class="text-xs font-semibold text-emerald-600">Fotos - Execução:</p>
      <div class="grid grid-cols-2 gap-3">
        <?php foreach ($fotosExecucaoConcluida as $arq): ?>
          <div class="space-y-1">
            <a href="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" target="_blank">
              <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="w-full h-32 object-cover rounded-xl border border-emerald-200 shadow-sm hover:opacity-90 transition">
            </a>
            <p class="text-[10px] text-gray-400 text-right"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
