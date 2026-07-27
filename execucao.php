<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

$tecnicoId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.gpon, p.splitter, p.uf, p.localidade, p.status AS preventiva_status, p.prioridade, p.criado_em, a.status AS atendimento_status
         FROM atendimentos a
         JOIN preventivas_rede p ON a.preventiva_id = p.id
         WHERE (a.tecnico_analise_id = :tecnico_id OR a.tecnico_execucao_id = :tecnico_id2)
           AND a.status IN ('analise', 'execucao', 'revisao')
         ORDER BY p.criado_em DESC"
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
      <h1 class="text-xl font-bold text-vivo-purple">Em Atendimento</h1>
      <p class="text-sm text-gray-500 mt-1">Preventivas em que você está envolvido.</p>
    </div>
    <a href="/dashboard" class="text-xs text-vivo-purple font-semibold">Voltar</a>
  </div>
</div>

<div class="grid gap-4 mb-16">
  <?php if (empty($tasks)): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500">
      Nenhuma preventiva em atendimento encontrada para o seu usuário.
    </div>
  <?php else: ?>
    <?php foreach ($tasks as $task): ?>
      <?php
        $statusLabel = $task['atendimento_status'];
        $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
        if ($statusLabel === 'revisao') {
            $badgeClass = 'bg-violet-50 text-violet-700 border-violet-200';
        } elseif ($statusLabel === 'concluido') {
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        }
      ?>
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="space-y-2">
          <h2 class="text-base font-bold text-gray-900"><?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?></h2>
          <p class="text-xs text-gray-500">#<?= htmlspecialchars($task['id']) ?> • <?= htmlspecialchars($task['localidade']) ?></p>
          <p class="text-[12px] text-gray-400">Prioridade: <?= htmlspecialchars($task['prioridade']) ?> • Aberta: <?= date('d/m/Y H:i', strtotime($task['criado_em'])) ?></p>
        </div>
        <div class="flex flex-col items-start sm:items-end gap-2">
          <span class="inline-flex items-center text-[10px] uppercase tracking-[0.24em] px-3 py-1 rounded-full font-bold border <?= $badgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
          <a href="/preventiva/<?= htmlspecialchars($task['id']) ?>" class="text-xs font-semibold text-vivo-purple hover:text-vivo-purpleDark">Ver detalhes</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
