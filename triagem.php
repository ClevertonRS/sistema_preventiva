<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

try {
    $stmt = $pdo->prepare("SELECT id, gpon, splitter, uf, localidade, prioridade, criado_em FROM preventivas_rede WHERE status = 'aberta' ORDER BY criado_em DESC");
    $stmt->execute();
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
}
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Fila <?= count($tasks) ?> de Triagem</h1>
      <p class="text-sm text-gray-500 mt-1">Preventivas com status <strong>aberta</strong></p>
    </div>
  </div>
</div>

<div class="grid gap-4 mb-16">
  <?php if (empty($tasks)): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500">
      Nenhuma preventiva em triagem no momento.
    </div>
  <?php else: ?>
    <?php foreach ($tasks as $task): ?>
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-start justify-between gap-4">
        <div class="space-y-1">
          <h2 class="text-base font-bold text-gray-900"><?= htmlspecialchars($task['titulo'] ?? 'Preventiva') ?></h2>
          <p class="text-xs text-gray-500">#<?= htmlspecialchars($task['id']) ?> • <?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?> • <?= htmlspecialchars($task['localidade']) ?></p>
          <p class="text-[12px] text-gray-400">Prioridade: <?= htmlspecialchars($task['prioridade']) ?> • Aberta: <?= date('d/m/Y H:i', strtotime($task['criado_em'])) ?></p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <a href="/preventiva/<?= htmlspecialchars($task['id']) ?>" class="bg-vivo-purple text-white px-3 py-2 rounded-lg text-sm font-bold text-center">Detalhar</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
