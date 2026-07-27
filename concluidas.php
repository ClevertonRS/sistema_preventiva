<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

$tecnicoId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.gpon, p.splitter, p.uf, p.localidade, p.prioridade, p.criado_em,
                a.concluido_em
         FROM preventivas_rede p
         JOIN atendimentos a ON a.preventiva_id = p.id
         WHERE p.status = 'concluida'
           AND (a.tecnico_analise_id = :tecnico_id OR a.tecnico_execucao_id = :tecnico_id2)
         ORDER BY a.concluido_em DESC, p.criado_em DESC"
    );
    $stmt->execute([':tecnico_id' => $tecnicoId, ':tecnico_id2' => $tecnicoId]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
}
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Preventivas Concluídas</h1>
      <p class="text-sm text-gray-500 mt-1">Histórico de preventivas finalizadas em que você participou.</p>
    </div>
    <a href="/dashboard" class="text-xs text-vivo-purple font-semibold">Voltar ao painel</a>
  </div>
</div>

<div class="grid gap-4 mb-16">
  <?php if (empty($tasks)): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500">
      Nenhuma preventiva concluída encontrada para o seu usuário.
    </div>
  <?php else: ?>
    <?php foreach ($tasks as $task): ?>
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="space-y-2">
          <h2 class="text-base font-bold text-gray-900">
            #<?= str_pad($task['id'], 4, '0', STR_PAD_LEFT) ?>
          </h2>
          <p class="text-xs text-gray-500">
            <?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?>
            • <?= htmlspecialchars($task['localidade']) ?> / <?= htmlspecialchars($task['uf']) ?>
          </p>
          <p class="text-[11px] text-gray-400">
            Prioridade: <strong class="text-gray-600"><?= htmlspecialchars($task['prioridade']) ?></strong>
            • Aberta: <?= date('d/m/Y', strtotime($task['criado_em'])) ?>
            <?php if (!empty($task['concluido_em'])): ?>
              • Concluída: <?= date('d/m/Y', strtotime($task['concluido_em'])) ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="flex flex-col items-start sm:items-end gap-2 shrink-0">
          <span class="inline-flex items-center text-[10px] uppercase tracking-[0.24em] px-3 py-1 rounded-full font-bold border bg-green-50 text-green-700 border-green-200">Concluído</span>
          <a href="/concluidas-detalhe/<?= htmlspecialchars($task['id']) ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-vivo-purple text-white text-xs font-semibold transition hover:bg-vivo-purpleDark">
            Detalhar
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
