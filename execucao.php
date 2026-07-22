<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$tecnicoId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare(
        "SELECT id, gpon, splitter, uf, localidade, status, prioridade, criado_em FROM preventivas_rede WHERE status = 'Em Execução' AND tecnico_id = :tecnico_id ORDER BY criado_em DESC"
    );
    $stmt->execute([':tecnico_id' => $tecnicoId]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
}
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Em Execução </h1>
      <p class="text-sm text-gray-500 mt-1">Preventivas em execução registradas com seu usuário.</p>
    </div>
    <a href="/dashboard" class="text-xs text-vivo-purple font-semibold">Voltar</a>
  </div>
</div>

<div class="grid gap-4 mb-16">
  <?php if (empty($tasks)): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500">
      Nenhuma preventiva em execução encontrada para o seu usuário.
    </div>
  <?php else: ?>
    <?php foreach ($tasks as $task): ?>
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="space-y-2">
          <h2 class="text-base font-bold text-gray-900"><?= htmlspecialchars($task['titulo'] ?? 'Preventiva') ?></h2>
          <p class="text-xs text-gray-500">#<?= htmlspecialchars($task['id']) ?> • <?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?> • <?= htmlspecialchars($task['localidade']) ?></p>
          <p class="text-[12px] text-gray-400">Prioridade: <?= htmlspecialchars($task['prioridade']) ?> • Aberta: <?= date('d/m/Y H:i', strtotime($task['criado_em'])) ?></p>
        </div>
        <div class="flex flex-col items-start sm:items-end gap-2">
          <span class="inline-flex items-center text-[10px] uppercase tracking-[0.24em] px-3 py-1 rounded-full font-bold border bg-blue-50 text-blue-700 border-blue-200">Em Execução</span>
          <!-- <a href="/execucao-detalhe/<?= htmlspecialchars($task['id']) ?>" class="text-xs font-semibold text-vivo-purple hover:text-vivo-purpleDark">Ver detalhes</a> -->
          <a href="/preventiva/<?= htmlspecialchars($task['id']) ?>" class="text-xs font-semibold text-vivo-purple hover:text-vivo-purpleDark">Ver detalhes</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>