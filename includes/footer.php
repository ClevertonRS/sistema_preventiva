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
        <span class="text-[9px] mt-1 <?= $currentBase === 'execucao' ? 'font-bold' : 'font-medium' ?>">Em Execução</span>
      </a>
      <a href="/revisao" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'revisao' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="edit-3" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'revisao' ? 'font-bold' : 'font-medium' ?>">Revisão</span>
      </a>
      <a href="/concluidas" class="flex flex-col items-center justify-center w-16 shrink-0 <?= $currentBase === 'concluidas' ? 'text-vivo-purple' : 'text-gray-400 hover:text-vivo-purple' ?>">
        <i data-lucide="check-circle" class="w-5 h-5"></i>
        <span class="text-[9px] mt-1 <?= $currentBase === 'concluidas' ? 'font-bold' : 'font-medium' ?>">Concluído</span>
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

      const installBtn = document.getElementById('install-pwa');
      let deferredPrompt = null;

      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        if (installBtn) {
          installBtn.classList.remove('hidden');
        }
      });

      if (installBtn) {
        installBtn.addEventListener('click', async () => {
          if (!deferredPrompt) {
            return;
          }

          deferredPrompt.prompt();
          await deferredPrompt.userChoice;
          deferredPrompt = null;
          installBtn.classList.add('hidden');
        });
      }

      function confirmSubmit(form, config) {
        const idInput = form.querySelector('input[name="preventiva_id"]');
        const id = idInput ? idInput.value : '';

        Swal.fire({
          title: config.title || 'Confirmar',
          text: config.text || 'Deseja continuar?',
          icon: config.icon || 'question',
          showCancelButton: true,
          confirmButtonText: config.confirmText || 'Confirmar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          confirmButtonColor: config.confirmColor || '#660099'
        }).then((result) => {
          if (result.isConfirmed) {
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
