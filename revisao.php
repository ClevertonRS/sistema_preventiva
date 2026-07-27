<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

$tecnicoId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT p.id, p.gpon, p.splitter, p.uf, p.localidade, p.prioridade, p.criado_em
     FROM preventivas_rede p
     JOIN atendimentos a ON a.preventiva_id = p.id
     WHERE a.status = 'revisao'
       AND (a.tecnico_analise_id = :tecnico_id OR a.tecnico_execucao_id = :tecnico_id2)
     ORDER BY p.criado_em DESC"
);
$stmt->execute([':tecnico_id' => $tecnicoId, ':tecnico_id2' => $tecnicoId]);
$tasks = $stmt->fetchAll();
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Preventivas em Revisão</h1>
      <p class="text-sm text-gray-500 mt-1">Liste as preventivas solicitadas para revisão e envie novamente para análise.</p>
    </div>
    <a href="/dashboard" class="text-xs text-vivo-purple font-semibold">Voltar ao painel</a>
  </div>
</div>

<main class="p-4 max-w-4xl mx-auto w-full space-y-4 flex-grow">
  <?php if (empty($tasks)): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500">
      Não há preventivas em revisão no momento.
    </div>
  <?php else: ?>
    <div class="grid gap-4">
      <?php foreach ($tasks as $task): ?>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <p class="text-sm font-bold text-gray-900">OS #<?= str_pad($task['id'], 4, '0', STR_PAD_LEFT) ?></p>
              <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?> • <?= htmlspecialchars($task['localidade']) ?> / <?= htmlspecialchars($task['uf']) ?></p>
            </div>
            <div class="space-y-2 text-right">
              <span class="inline-flex items-center text-[10px] uppercase tracking-[0.24em] px-3 py-1 rounded-full font-bold border border-violet-200 bg-violet-50 text-violet-700">Revisão</span>
              <a href="/revisao-detalhe/<?= htmlspecialchars($task['id']) ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-vivo-purple text-white text-xs font-semibold transition hover:bg-vivo-purpleDark">Detalhes</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
