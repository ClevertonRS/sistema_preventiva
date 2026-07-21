<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Proteção da página: se não estiver logado, redireciona para login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nomeUsuario = $_SESSION['user_nome'] ?? 'Operador';
$inicialNome = strtoupper(substr($nomeUsuario, 0, 1));

$statusCounts = [
    'triagem' => 0,
    'Em Execução' => 0,
    'Em Análise' => 0,
    'Revisão' => 0,
    'Concluído' => 0,
];

try {
    $stmtCounts = $pdo->query("SELECT status, COUNT(*) AS total FROM preventivas_rede GROUP BY status");
    foreach ($stmtCounts->fetchAll() as $row) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = $row['total'];
        }
    }

    $stmtTasks = $pdo->query(
        "SELECT id, gpon, splitter, uf, localidade, status, prioridade, criado_em FROM preventivas_rede ORDER BY FIELD(status,'triagem','Em Execução','Em Análise','Revisão','Concluído'), criado_em DESC"
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
    <title>Painel Vivo - Home</title>

    <!-- Meta Tags PWA -->
    <meta name="theme-color" content="#660099" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Painel Vivo" />
    <meta name="application-name" content="Preventivas Vivo" />

    <link rel="icon" href="assets/icons/vivo-icon.png" />
    <link rel="manifest" href="assets/icons/manifest.json" />

    <!-- Tailwind CSS -->
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

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
      ::-webkit-scrollbar { width: 6px; height: 6px; }
      ::-webkit-scrollbar-track { background: #f1f1f1; }
      ::-webkit-scrollbar-thumb { background: #c5a3e8; border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: #660099; }
    </style>
</head>
<body class="bg-vivo-grayLight text-vivo-textDark font-sans min-h-screen flex flex-col pb-16 md:pb-0">

    <!-- HEADER / NAVEGAÇÃO -->
    <header class="bg-white border-b border-vivo-grayBorder sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          
          <!-- Logo Vivo -->
          <div class="flex items-center space-x-3">
            <div class="bg-vivo-purple text-white p-2 rounded-xl flex items-center justify-center shadow-md shadow-purple-200">
              <i data-lucide="activity" class="w-5 h-5"></i>
            </div>
            <span class="text-xl font-extrabold tracking-tight text-vivo-purple">vivo</span>
          </div>

          <!-- Navegação Desktop -->
          <nav class="hidden md:flex space-x-1">
            <a href="/dashboard" class="px-4 py-2 rounded-lg text-sm font-semibold bg-vivo-purple text-white shadow-sm transition-all">Início</a>
            <a href="/preventivas?status=execucao" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Em Andamento</a>
            <a href="/preventivas?status=analise" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Em Análise</a>
            <a href="/preventivas?status=revisao" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Revisão</a>
            <a href="/preventivas?status=concluido" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Executados</a>
          </nav>

          <!-- Perfil e Logout -->
          <div class="flex items-center space-x-3">
            <div class="flex flex-col items-end">
              <button id="install-pwa" type="button" class="inline-flex items-center gap-2 bg-vivo-accent hover:bg-pink-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-md transition-all animate-pulse">
                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                <span>Instalar App</span>
              </button>
              <p id="install-feedback" class="hidden mt-1 text-[10px] text-vivo-purple font-medium"></p>
            </div>

            <a href="logout.php" title="Sair do sistema" class="w-9 h-9 rounded-full bg-vivo-purple text-white flex items-center justify-center font-bold text-sm border-2 border-vivo-purpleLight hover:bg-vivo-purpleDark transition-all">
              <?= htmlspecialchars($inicialNome) ?>
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
      
      <!-- Banner Hero de Boas-Vindas -->
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
            Bem-vindo ao seu novo painel progressivo. Monitore suas revisões e tarefas executadas em tempo real.
          </p>
        </div>
      </div>

      <!-- Métricas Rápidas -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-amber-50 text-amber-600 rounded-lg"><i data-lucide="clock" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Pendente</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['triagem'] ?></span>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-blue-50 text-blue-600 rounded-lg"><i data-lucide="play-circle" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Em Execução</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['Em Execução'] ?></span>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg"><i data-lucide="search" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Em Análise</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['Em Análise'] ?></span>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-vivo-grayBorder flex items-center space-x-3">
          <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
          <div>
            <span class="text-xs text-gray-500 block font-medium">Revisão</span>
            <span class="text-xl font-bold text-gray-800"><?= $statusCounts['Revisão'] ?></span>
          </div>
        </div>
      </div>

      <!-- Lista de Fluxos -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-vivo-purple flex items-center space-x-2">
              <i data-lucide="kanban" class="w-5 h-5 text-vivo-purple"></i>
              <span>Acompanhamento Recente</span>
            </h2>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-vivo-grayBorder divide-y divide-gray-100">
            <?php if (empty($tasks)): ?>
              <div class="p-4 text-center text-sm text-gray-500">Nenhuma preventiva encontrada.</div>
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
                <div class="p-4 hover:bg-gray-50 transition-colors flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                  <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($task['titulo']) ?></span>
                      <span class="text-[10px] uppercase tracking-[0.2em] px-2 py-1 rounded-full border <?= $badgeClass ?>"><?= htmlspecialchars($task['status']) ?></span>
                    </div>
                    <p class="text-xs text-gray-500">ID: #<?= htmlspecialchars($task['id']) ?> • <?= htmlspecialchars($task['gpon']) ?> / <?= htmlspecialchars($task['splitter']) ?> • <?= htmlspecialchars($task['localidade']) ?></p>
                  </div>
                  <a href="/preventiva/<?= htmlspecialchars($task['id']) ?>" class="text-xs uppercase font-bold text-vivo-purple hover:text-vivo-purpleDark">Ver detalhes</a>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Sidebar / Informações -->
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

    <!-- NAVEGAÇÃO MOBILE (PWA Bar) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-vivo-grayBorder shadow-lg z-50 flex justify-around items-center h-16 px-2">
      <a href="/dashboard" class="flex flex-col items-center justify-center w-14 text-vivo-purple">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 font-bold">Início</span>
      </a>
      <a href="/logout" class="flex flex-col items-center justify-center w-14 text-gray-400 hover:text-vivo-purple">
        <i data-lucide="log-out" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 font-medium">Sair</span>
      </a>
    </nav>

    <!-- FOOTER DESKTOP -->
    <footer class="hidden md:block bg-white border-t border-vivo-grayBorder py-6 mt-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-400">
        &copy; 2026 Vivo Painel Operacional PWA.
      </div>
    </footer>

    <script>
      lucide.createIcons();

      const installBtn = document.getElementById('install-pwa');
      const installFeedback = document.getElementById('install-feedback');
      let deferredPrompt = null;

      const showFeedback = (message, isError = false) => {
        if (!installFeedback) return;
        installFeedback.textContent = message;
        installFeedback.className = isError
          ? 'mt-1 text-[10px] text-amber-700 font-medium'
          : 'mt-1 text-[10px] text-vivo-purple font-medium';
      };

      if ("serviceWorker" in navigator) {
        window.addEventListener("load", () => {
          navigator.serviceWorker.register("./sw.js").catch((err) => console.warn(err));
        });
      }

      const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
      if (installBtn && isStandalone) {
        installBtn.classList.add('hidden');
      }

      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        if (installBtn && !isStandalone) {
          installBtn.classList.remove('hidden');
          showFeedback('Pronto! Toque em Instalar para adicionar ao celular.');
        }
      });

      if (installBtn) {
        installBtn.addEventListener('click', async () => {
          if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (outcome === 'accepted') {
              showFeedback('Aplicativo instalado com sucesso.');
            } else {
              showFeedback('Instalação cancelada.', true);
            }
            return;
          }

          const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
          if (isStandalone) {
            showFeedback('O app já está instalado neste dispositivo.');
            return;
          }

          const isSecureContext = window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
          if (!isSecureContext) {
            showFeedback('Abra o painel em HTTPS ou em localhost para ver a instalação.', true);
            return;
          }

          showFeedback('No celular, o navegador pode não exibir o prompt automaticamente. Use o menu do Chrome/Edge para instalar o app.', true);
        });
      }

      window.addEventListener('appinstalled', () => {
        if (installBtn) {
          installBtn.classList.add('hidden');
        }
        showFeedback('Aplicativo instalado com sucesso.');
      });
    </script>
</body>
</html>