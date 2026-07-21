<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$statusSlug = $_GET['status'] ?? '';
$statusMap = [
    'pendente' => 'Pendente',
    'execucao' => 'Em Execução',
    'analise' => 'Em Análise',
    'revisao' => 'Revisão',
    'concluida' => 'Concluído',
];

$statusFilter = $statusMap[$statusSlug] ?? '';
$pageTitle = $statusFilter ? "Preventivas: $statusFilter" : 'Todas as Preventivas';
$whereSql = '';
$params = [];

if ($statusFilter) {
    $whereSql = 'WHERE status = :status';
    $params[':status'] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT id, titulo, gpon, splitter, uf, localidade, status, prioridade, criado_em FROM preventivas_rede $whereSql ORDER BY FIELD(status,'Pendente','Em Execução','Em Análise','Revisão','Concluído'), criado_em DESC");
$stmt->execute($params);
$tasks = $stmt->fetchAll();
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Fluxo de Preventivas</h1>
      <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($pageTitle) ?></p>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
      <a href="/preventivas" class="text-xs text-center px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 transition">Todas</a>
      <a href="/preventivas?status=pendente" class="text-xs text-center px-3 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 transition">Pendente</a>
      <a href="/preventivas?status=execucao" class="text-xs text-center px-3 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 transition">Execução</a>
      <a href="/preventivas?status=analise" class="text-xs text-center px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 transition">Análise</a>
      <a href="/preventivas?status=revisao" class="text-xs text-center px-3 py-2 rounded-xl bg-violet-50 hover:bg-violet-100 transition">Revisão</a>
    </div>
  </div>
</div>

<div class="grid gap-4 mb-16">
  <?php if (empty($tasks)): ?>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500">
      Nenhuma preventiva encontrada para o filtro selecionado.
    </div>
  <?php else: ?>
    <?php foreach ($tasks as $task): ?>
      <?php
        $badgeClass = 'bg-gray-100 text-gray-700 border-gray-200';
        switch ($task['status']) {
          case 'Pendente':
            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
            break;
          case 'Em Execução':
            $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
            break;
          case 'Em Análise':
            $badgeClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
            break;
          case 'Revisão':
            $badgeClass = 'bg-violet-50 text-violet-700 border-violet-200';
            break;
          case 'Concluído':
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            break;
        }
      ?>
      <a href="/preventiva/<?= htmlspecialchars($task['id']) ?>" class="block bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-vivo-purple transition">
        <div class="flex items-start justify-between gap-4">
          <div class="space-y-2">
            <h2 class="text-base font-bold text-gray-900"><?= htmlspecialchars($task['titulo'] ?? 'Preventiva') ?></h2>
            <p class="text-xs text-gray-500">#<?= htmlspecialchars($task['id']) ?> • <?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?> • <?= htmlspecialchars($task['localidade']) ?></p>
          </div>
          <span class="inline-flex items-center text-[10px] uppercase tracking-[0.24em] px-3 py-1 rounded-full font-bold border <?= $badgeClass ?>"><?= htmlspecialchars($task['status']) ?></span>
        </div>
        <div class="mt-4 text-xs text-gray-500 flex items-center justify-between">
          <span>Aberta em <?= date('d/m/Y', strtotime($task['criado_em'])) ?></span>
          <span class="text-vivo-purple font-semibold">Ver detalhes</span>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php';
