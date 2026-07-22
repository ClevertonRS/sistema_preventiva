<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel Vivo - Home</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#660099" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Painel Vivo" />

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
      ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }
      ::-webkit-scrollbar-track {
        background: #f1f1f1;
      }
      ::-webkit-scrollbar-thumb {
        background: #c5a3e8;
        border-radius: 10px;
      }
      ::-webkit-scrollbar-thumb:hover {
        background: #660099;
      }
    </style>
  </head>

  <body class="bg-vivo-grayLight text-vivo-textDark font-sans min-h-screen flex flex-col pb-16 md:pb-0">

    <header class="bg-white border-b border-vivo-grayBorder sticky top-0 z-50 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center space-x-3">
            <div class="text-white p-2 rounded-xl flex items-center justify-center shadow-md shadow-purple-200 bg-vivo-purple">
              <span class="font-black text-xl">V</span>
            </div>
            <span class="text-xl font-extrabold tracking-tight text-vivo-purple">vivo</span>
          </div>

          <nav class="hidden md:flex space-x-1">
            <a href="/dashboard" class="px-4 py-2 rounded-lg text-sm font-semibold bg-vivo-purple text-white shadow-sm transition-all">Início</a>
            <a href="/triagem" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Triagem</a>
            <a href="/preventivas?status=execucao" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Em Execução</a>
            <a href="/preventivas?status=analise" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Em Análise</a>
            <a href="/preventivas?status=revisao" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Revisão</a>
            <a href="/preventivas?status=concluido" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-vivo-purple hover:bg-vivo-purpleLight transition-all">Executados</a>
          </nav> 

          <div class="flex items-center space-x-3">
            <button id="install-pwa" type="button" class="flex items-center gap-2 bg-vivo-accent hover:bg-pink-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-md transition-all">
              <i data-lucide="download" class="w-3.5 h-3.5"></i>
              <span>Baixar App</span>
            </button>
            <a href="/logout" class="w-9 h-9 rounded-full bg-vivo-purple text-white flex items-center justify-center font-bold text-sm border-2 border-vivo-purpleLight">C</a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

