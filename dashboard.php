<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

$nomeUsuario = $_SESSION['user_nome'] ?? 'Operador';
$inicialNome = strtoupper(substr($nomeUsuario, 0, 1));

$statusCounts = [
    'aberta' => 0,
    'em_atendimento' => 0,
    'concluida' => 0,
];

try {
    $stmtCounts = $pdo->query("SELECT status, COUNT(*) AS total FROM preventivas_rede GROUP BY status");
    foreach ($stmtCounts->fetchAll() as $row) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = $row['total'];
        }
    }

    $stmtTasks = $pdo->query(
        "SELECT p.id, p.gpon, p.splitter, p.uf, p.localidade, p.status, p.prioridade, p.criado_em, a.status AS atendimento_status
         FROM preventivas_rede p
         JOIN atendimentos a ON a.preventiva_id = p.id
         WHERE a.status = 'revisao'
         ORDER BY p.criado_em DESC"
    );
    $tasks = $stmtTasks->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prev Tec - Home</title>
    <meta name="theme-color" content="#660099" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Painel Vivo" />
    <meta name="application-name" content="Preventivas Vivo" />
    <link rel="icon" href="assets/icons/vivo-icon.png" />
    <link rel="manifest" href="assets/icons/manifest.json" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              vivo: {
                purple: "#660099",
                purpleDark: "#4d0073",
                purpleLight: "#f4ebff",
                accent: "#ff007f",
                grayLight: "#f6f6f9",
                grayBorder: "#e2e8f0",
                textDark: "#333333",
              },
            },
          },
        },
      };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
      ::-webkit-scrollbar { width: 6px; height: 6px; }
      ::-webkit-scrollbar-track { background: #f1f1f1; }
      ::-webkit-scrollbar-thumb { background: #c5a3e8; border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: #660099; }
    </style>
</head>
<body class="bg-vivo-grayLight text-vivo-textDark font-sans min-h-screen flex flex-col pb-16 md:pb-0">

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
      
      <div class="bg-gradient-to-r from-vivo-purple to-vivo-purpleDark text-white rounded-2xl p-6 sm:p-8 shadow-xl mb-6 relative overflow-hidden">
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-10 translate-y-10">
          <i data-lucide="activity" class="w-64 h-64"></i>
        </div>
        <div class="relative z-10 max-w-xl">
          <span class="bg-vivo-accent text-white text-[11px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded-full shadow-sm">
            Painel Operacional
          </span>
          <h1 class="text-2xl sm:text-3xl font-extrabold mt-3 tracking-tight">
            Olá, <?= htmlspecialchars($nomeUsuario) ?>!
          </h1>
          <p class="text-purple-100 mt-2 text-sm sm:text-base">
            Bem-vindo ao seu painel progressivo. Monitore suas revisões e tarefas em tempo real.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-amber-50 text-amber-600 rounded-lg"><i data-lucide="clock" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Abertas</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['aberta'] ?></span>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="play-circle" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Em Atendimento</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['em_atendimento'] ?></span>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Concluídas</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['concluida'] ?></span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-vivo-purple flex items-center space-x-2">
              <i data-lucide="kanban" class="w-5 h-5 text-vivo-purple"></i>
              <span>Acompanhamento de Revisão</span>
            </h2>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-vivo-grayBorder divide-y divide-gray-100">
            <?php if (empty($tasks)): ?>
              <div class="p-4 text-center text-sm text-gray-500">Nenhuma preventiva em revisão.</div>
            <?php else: ?>
              <?php foreach ($tasks as $task): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                  <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-sm font-bold text-gray-800">OS #<?= str_pad($task['id'], 4, '0', STR_PAD_LEFT) ?></span>
                      <span class="text-[10px] uppercase tracking-[0.22em] px-2 py-1 rounded-full border bg-violet-50 text-violet-700 border-violet-200">Revisão</span>
                    </div>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?> • <?= htmlspecialchars($task['localidade']) ?></p>
                  </div>
                  <a href="/preventiva/<?= htmlspecialchars($task['id']) ?>" class="text-xs uppercase font-bold text-vivo-purple hover:text-vivo-purpleDark">Ver detalhes</a>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white p-5 rounded-xl shadow-sm border border-vivo-grayBorder">
            <h3 class="text-sm font-bold text-gray-800">A Vivo</h3>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">
              A Vivo busca constantemente a digitalização das empresas brasileiras com tecnologia robusta e análise inteligente de dados.
            </p>
          </div>
        </div>
      </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
