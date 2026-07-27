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
       AND p.status = 'concluida'
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
      <p><strong>Analista:</strong> <?= htmlspecialchars($p['nome_analista'] ?? 'N/A') ?></p>
      <p><strong>Executor:</strong> <?= htmlspecialchars($p['nome_executor'] ?? 'N/A') ?></p>
      <p><strong>GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
      <p><strong>Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
      <p><strong>Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
      <p><strong>UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
      <?php if (!empty($p['latitude_analise']) && !empty($p['longitude_analise'])): ?>
          <p><strong>Local (Análise):</strong>
              <a href="https://www.google.com/maps?q=<?= $p['latitude_analise'] ?>,<?= $p['longitude_analise'] ?>" target="_blank" class="text-vivo-purple underline">
                  <?= $p['latitude_analise'] ?>, <?= $p['longitude_analise'] ?>
              </a>
          </p>
      <?php endif; ?>
      <?php if (!empty($p['latitude_execucao']) && !empty($p['longitude_execucao'])): ?>
          <p><strong>Local (Execução):</strong>
              <a href="https://www.google.com/maps?q=<?= $p['latitude_execucao'] ?>,<?= $p['longitude_execucao'] ?>" target="_blank" class="text-vivo-purple underline">
                  <?= $p['latitude_execucao'] ?>, <?= $p['longitude_execucao'] ?>
              </a>
          </p>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($p['descricao_analise'])): ?>
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-3">
    <h3 class="text-sm font-bold text-vivo-purple">Descrição da Análise</h3>
    <div class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
      <?= nl2br(htmlspecialchars($p['descricao_analise'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($p['descricao_execucao'])): ?>
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-3">
    <h3 class="text-sm font-bold text-vivo-purple">Descrição da Execução</h3>
    <div class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
      <?= nl2br(htmlspecialchars($p['descricao_execucao'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($arquivos)): ?>
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-bold text-vivo-purple">Evidências</h3>
      <span class="text-xs text-gray-400"><?= count($arquivos) ?> arquivo(s)</span>
    </div>
    <div class="grid grid-cols-1 gap-4">
      <?php foreach ($arquivos as $arq): ?>
        <div>
          <div class="flex gap-2 mb-1">
            <?php if (!empty($arq['tipo'])): ?>
              <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full <?= $arq['tipo'] === 'analise' ? 'bg-indigo-50 text-indigo-700' : 'bg-blue-50 text-blue-700' ?>"><?= htmlspecialchars($arq['tipo']) ?></span>
            <?php endif; ?>
            <?php if (!empty($arq['momento'])): ?>
              <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full <?= $arq['momento'] === 'antes' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>"><?= htmlspecialchars($arq['momento']) ?></span>
            <?php endif; ?>
          </div>
          <a href="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" target="_blank">
            <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Foto" class="w-full h-[220px] object-cover rounded-xl border border-gray-200 shadow-sm hover:opacity-90 transition">
          </a>
          <p class="text-[10px] text-gray-400 text-right mt-1"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
