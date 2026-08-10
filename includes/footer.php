    </main>

    <?php
      $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      $currentBase = explode('/', trim($currentPath, '/'))[0] ?: 'dashboard';
      $navMap = [
        'dashboard' => 'dashboard', 'triagem' => 'triagem',
        'execucao' => 'execucao', 'revisao' => 'revisao',
        'concluidas' => 'concluidas',
        'preventivas' => 'dashboard', 'preventiva' => 'dashboard',
        'execucao-detalhe' => 'execucao',
        'revisao-detalhe' => 'revisao',
        'concluidas-detalhe' => 'concluidas',
      ];
      $currentBase = $navMap[$currentBase] ?? '';
    ?>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-vivo-grayBorder shadow-lg z-50 flex justify-between items-center h-16 px-1 overflow-x-auto">
      <a href="/dashboard" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'dashboard' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'dashboard' ? 'font-bold' : 'font-medium' ?>">Início</span>
      </a>
      <a href="/triagem" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'triagem' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="list" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'triagem' ? 'font-bold' : 'font-medium' ?>">Triagem</span>
      </a>
      <a href="/execucao" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'execucao' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="play-circle" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'execucao' ? 'font-bold' : 'font-medium' ?>">Em Andamento</span>
      </a>
      <a href="/revisao" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'revisao' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="edit-3" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'revisao' ? 'font-bold' : 'font-medium' ?>">Revisão</span>
      </a>
      <a href="/concluidas" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'concluidas' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'concluidas' ? 'font-bold' : 'font-medium' ?>">Concluídas</span>
      </a>
    </nav>

    <footer class="hidden md:block bg-white border-t border-vivo-grayBorder py-6 mt-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between">
        <div class="text-xs text-gray-400">&copy; 2026 Painel Operacional. Desenvolvido para agilidade em Data Science.</div>
        <div class="flex space-x-4 mt-3 sm:mt-0">
          <a href="#" class="text-xs text-gray-400 hover:text-vivo-purple">Termos de Uso</a>
          <a href="#" class="text-xs text-gray-400 hover:text-vivo-purple">Privacidade</a>
          <a href="#" class="text-xs text-gray-400 hover:text-vivo-purple">Ajuda</a>
        </div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      lucide.createIcons();

      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js')
            .catch((err) => console.error('SW registration falhou:', err));
        });
      }

      const installBtn = document.getElementById('install-pwa');
      let deferredPrompt = null;

      const isInstalled = () => {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.startsWith('android-app://');
      };

      const showInstallBtn = () => {
        if (installBtn && !isInstalled()) {
          installBtn.classList.remove('hidden');
          installBtn.classList.add('flex');
        }
      };

      const hideInstallBtn = () => {
        if (installBtn) {
          installBtn.classList.add('hidden');
          installBtn.classList.remove('flex');
        }
      };

      const showInstallHelp = () => {
        const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const steps = isIOS
          ? [
              'Toque no botão Compartilhar (seta para cima).',
              'Role a lista e toque em "Adicionar à Tela de Início".',
              'Toque em "Adicionar" para confirmar.'
            ]
          : [
              'Toque no menu ⋮ (três pontos) do navegador.',
              'Toque em "Instalar app" ou "Adicionar à tela inicial".',
              'Confirme a instalação.'
            ];

        Swal.fire({
          title: 'Instalar o aplicativo',
          html: '<ol class="text-left text-sm space-y-2" style="list-style:decimal inside">' +
                steps.map((s) => `<li>${s}</li>`).join('') +
                '</ol>',
          icon: 'info',
          confirmButtonText: 'Entendi',
          confirmButtonColor: '#660099'
        });
      };

      if (installBtn && !isInstalled()) {
        window.addEventListener('beforeinstallprompt', (event) => {
          event.preventDefault();
          deferredPrompt = event;
          showInstallBtn();
        });

        // Fallback: se o navegador não disparar o evento (ex.: logo após
        // desinstalar, iOS Safari, etc.), ainda exibe o botão com instruções.
        setTimeout(() => {
          if (!deferredPrompt && !isInstalled()) {
            showInstallBtn();
          }
        }, 3000);
      }

      window.addEventListener('appinstalled', () => {
        hideInstallBtn();
      });

      if (installBtn) {
        installBtn.addEventListener('click', async () => {
          if (!deferredPrompt) {
            showInstallHelp();
            return;
          }

          deferredPrompt.prompt();
          await deferredPrompt.userChoice;
          deferredPrompt = null;
          hideInstallBtn();
        });
      }

      function confirmSubmit(form, config) {
        const idInput = form.querySelector('input[name="preventiva_id"]');
        const id = idInput ? idInput.value : '';
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

        Swal.fire({
          title: config.title || 'Confirmar',
          text: config.text || 'Deseja continuar?',
          icon: config.icon || 'question',
          showCancelButton: true,
          confirmButtonText: config.confirmText || 'Confirmar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          confirmButtonColor: config.confirmColor || '#660099',
          allowOutsideClick: false,
          allowEscapeKey: false
        }).then((result) => {
          if (result.isConfirmed) {
            // Loading state
            if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                  <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  <span>Enviando...</span>
                </div>`;
            }
            // Barra de progresso simples
            Swal.fire({
              title: 'Processando...',
              text: 'Aguarde enquanto salvamos os dados',
              allowOutsideClick: false,
              allowEscapeKey: false,
              showConfirmButton: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });
            form.submit();
          }
        });
      }

      document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || !form.classList) return;

        if (form.classList.contains('confirm-aceitar')) {
          e.preventDefault();
          const idInput = form.querySelector('input[name="preventiva_id"]');
          const id = idInput ? idInput.value : '';
          confirmSubmit(form, {
            title: `Atender preventiva #${id}`,
            text: 'Deseja registrar que você irá atender esta preventiva agora?',
            icon: 'question',
            confirmText: 'Sim, atender',
            confirmColor: '#660099'
          });
          return;
        }

        if (form.classList.contains('confirm-finalizar')) {
          e.preventDefault();
          const idInput = form.querySelector('input[name="preventiva_id"]');
          const id = idInput ? idInput.value : '';
          confirmSubmit(form, {
            title: `Finalizar OS #${id}`,
            text: 'Confirma o envio do relatório para análise do supervisor?',
            icon: 'warning',
            confirmText: 'Sim, finalizar',
            confirmColor: '#660099'
          });
          return;
        }
      });
    </script>
  </body>
</html>
