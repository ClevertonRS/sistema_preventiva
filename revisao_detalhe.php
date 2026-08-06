<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /revisao');
    exit;
}

$tecnicoId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT p.*, a.id AS atendimento_id, a.status AS atendimento_status,
            a.tecnico_analise_id, a.tecnico_execucao_id,
            a.descricao_analise, a.descricao_execucao,
            a.latitude_analise, a.longitude_analise,
            a.latitude_execucao, a.longitude_execucao,
            ua.nome AS nome_analista, ue.nome AS nome_executor
     FROM preventivas_rede p
     JOIN atendimentos a ON a.preventiva_id = p.id
     LEFT JOIN usuarios ua ON ua.id = a.tecnico_analise_id
     LEFT JOIN usuarios ue ON ue.id = a.tecnico_execucao_id
     WHERE p.id = :id
       AND a.status = 'revisao'
       AND (a.tecnico_analise_id = :tecnico_id OR a.tecnico_execucao_id = :tecnico_id2)
     LIMIT 1"
);
$stmt->execute([':id' => $id, ':tecnico_id' => $tecnicoId, ':tecnico_id2' => $tecnicoId]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /revisao');
    exit;
}

$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE atendimento_id = :atendimento_id ORDER BY criado_em ASC");
$stmtArquivos->execute([':atendimento_id' => $p['atendimento_id']]);
$arquivos = $stmtArquivos->fetchAll();

// Fase 1 (análise) = somente leitura
$fotosAnalise = array_values(array_filter($arquivos, fn($f) => $f['tipo'] === 'analise'));
// Fase 2 (execução) = editável (pode excluir e adicionar novas)
$fotosExecucao = array_values(array_filter($arquivos, fn($f) => in_array($f['tipo'], ['execucao', 'revisao'], true)));

$supervisorDescricao = $p['descricao_supervisor'] ?? $p['observacao_supervisor'] ?? $p['supervisor_descricao'] ?? $p['supervisor_obs'] ?? $p['revisor_observacao'] ?? $p['motivo_revisao'] ?? 'Descrição do supervisor não registrada.';

$descricaoExecucaoAtual = $p['descricao_execucao'] ?? '';
$fotosExecucaoIds = array_column($fotosExecucao, 'id');
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Revisão da Preventiva</h1>
      <p class="text-sm text-gray-500 mt-1">Todos os dados registrados são exibidos abaixo. Edite a fase de execução e reenvie.</p>
    </div>
    <a href="/revisao" class="text-xs text-vivo-purple font-semibold">Voltar</a>
  </div>
</div>

<main class="p-4 max-w-3xl mx-auto w-full space-y-4 flex-grow">

  <!-- Dados da OS -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-extrabold text-vivo-dark">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h2>
        <p class="text-xs text-gray-500 mt-1">Status: <span class="font-semibold text-gray-700"><?= htmlspecialchars($p['status']) ?></span></p>
      </div>
      <span class="inline-flex items-center text-[10px] uppercase tracking-[0.22em] px-3 py-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200">Revisão</span>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
      <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500">
        <p><strong class="text-gray-700">GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
        <p><strong class="text-gray-700">Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
        <p><strong class="text-gray-700">Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
        <p><strong class="text-gray-700">UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
        <?php if (!empty($p['latitude_analise']) && !empty($p['longitude_analise'])): ?>
          <p><strong class="text-gray-700">Local (Análise):</strong>
            <a href="https://www.google.com/maps?q=<?= $p['latitude_analise'] ?>,<?= $p['longitude_analise'] ?>" target="_blank" class="text-vivo-purple underline">Abrir no Mapa</a>
          </p>
        <?php endif; ?>
        <?php if (!empty($p['latitude_execucao']) && !empty($p['longitude_execucao'])): ?>
          <p><strong class="text-gray-700">Local (Execução):</strong>
            <a href="https://www.google.com/maps?q=<?= $p['latitude_execucao'] ?>,<?= $p['longitude_execucao'] ?>" target="_blank" class="text-vivo-purple underline">Abrir no Mapa</a>
          </p>
        <?php endif; ?>
      </div>
      <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500">
        <p><strong class="text-gray-700">Prioridade:</strong> <?= htmlspecialchars($p['prioridade']) ?></p>
        <p><strong class="text-gray-700">Chave Combinação:</strong> <?= htmlspecialchars($p['chave_combinacao']) ?></p>
        <p><strong class="text-gray-700">Aberta em:</strong> <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></p>
        <p><strong class="text-gray-700">Analista:</strong> <?= htmlspecialchars($p['nome_analista'] ?? 'N/A') ?></p>
        <p><strong class="text-gray-700">Executor:</strong> <?= htmlspecialchars($p['nome_executor'] ?? 'N/A') ?></p>
      </div>
    </div>
  </div>

  <!-- Comentário do supervisor -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <h3 class="text-sm font-bold text-vivo-purple">Comentário do supervisor</h3>
    <p class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
      <?= nl2br(htmlspecialchars($supervisorDescricao)) ?>
    </p>
  </div>

  <!-- FASE 1: Análise (somente leitura) -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <h3 class="text-sm font-bold text-vivo-purple flex items-center gap-2">
      <i data-lucide="search" class="w-4 h-4"></i> Fase 1 - Análise
    </h3>
    <?php if (!empty($p['descricao_analise'])): ?>
      <p class="text-sm text-gray-800 bg-amber-50 p-3 rounded-xl border border-amber-200">
        <?= nl2br(htmlspecialchars($p['descricao_analise'])) ?>
      </p>
    <?php else: ?>
      <p class="text-xs text-gray-400">Nenhuma descrição de análise registrada.</p>
    <?php endif; ?>

    <?php if (!empty($fotosAnalise)): ?>
      <div>
        <p class="text-xs font-semibold text-gray-400 mb-2">Fotos da Análise:</p>
        <div class="grid grid-cols-2 gap-3">
          <?php foreach ($fotosAnalise as $arq): ?>
            <div class="space-y-1">
              <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Foto da análise" class="w-full h-40 object-cover rounded-xl border border-amber-200 shadow-sm">
              <p class="text-[10px] text-gray-400 text-right"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- FASE 2: Execução (editável) -->
  <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
    <h3 class="text-sm font-bold text-vivo-purple flex items-center gap-2">
      <i data-lucide="wrench" class="w-4 h-4"></i> Fase 2 - Execução <span class="text-[10px] font-semibold uppercase text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">Editável</span>
    </h3>

    <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500">
      <strong class="text-gray-700">Técnico executor:</strong> <?= htmlspecialchars($p['nome_executor'] ?? 'Aguardando') ?>
    </div>

    <!-- Sua Localização -->
    <div id="location-status" class="bg-gray-50 p-3 rounded-xl border border-gray-100 space-y-2">
      <h4 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização <span class="text-red-500">*</span></h4>
      <p class="text-xs text-gray-500" id="location-text">Obtendo localização...</p>
      <button type="button" id="btn-atualizar-loc" class="text-xs font-semibold text-vivo-purple hover:underline">
        <i data-lucide="refresh-cw" class="w-3 h-3 inline"></i> Atualizar Localização
      </button>
    </div>

    <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="space-y-4" id="form-revisao">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="preventiva_id" value="<?= htmlspecialchars($p['id']) ?>">
      <input type="hidden" name="atendimento_id" value="<?= htmlspecialchars($p['atendimento_id']) ?>">
      <input type="hidden" name="acao" value="finalizar_revisao">
      <input type="hidden" name="return_url" value="/dashboard">
      <input type="hidden" name="latitude" id="latitude" value="">
      <input type="hidden" name="longitude" id="longitude" value="">
      <!-- IDs das fotos de execução que devem ser mantidas (não excluídas) -->
      <input type="hidden" name="fotos_revisao_keep" id="fotos-revisao-keep" value="<?= htmlspecialchars(implode(',', $fotosExecucaoIds)) ?>">

      <div>
        <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição da Execução <span class="text-red-500">*</span></label>
        <textarea name="descricao" rows="5" required placeholder="Edite e descreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"><?= htmlspecialchars($descricaoExecucaoAtual) ?></textarea>
      </div>

      <!-- Fotos atuais da execução (com botão de excluir) -->
      <?php if (!empty($fotosExecucao)): ?>
        <div class="fotos-execucao-section">
          <p class="text-xs font-semibold text-gray-400 mb-2">Fotos atuais da execução (clique no X para excluir):</p>
          <div class="grid grid-cols-2 gap-3" id="fotos-execucao-grid">
            <?php foreach ($fotosExecucao as $arq): ?>
              <div class="space-y-1 relative group" data-arquivo-id="<?= (int)$arq['id'] ?>">
                <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Foto da execução" class="w-full h-40 object-cover rounded-xl border border-gray-200 shadow-sm">
                <p class="text-[10px] text-gray-400 text-right"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                <button type="button" class="btn-delete-foto absolute top-2 right-2 bg-red-600 text-white rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-700" data-id="<?= (int)$arq['id'] ?>" title="Excluir foto">
                  <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Novas fotos -->
      <div>
        <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Novas Fotos da Execução <span class="text-gray-400 font-normal">(opcional)</span></label>
        <div class="flex gap-2 mb-2">
          <button type="button" id="btn-camera-rev" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="openFileInputRev('camera')">
            <i data-lucide="camera" class="w-4 h-4"></i>Câmera
          </button>
          <button type="button" id="btn-galeria-rev" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="openFileInputRev('galeria')">
            <i data-lucide="image" class="w-4 h-4"></i>Galeria
          </button>
        </div>
        <input type="file" id="foto-input-camera-rev" accept="image/*" capture="environment" multiple class="hidden">
        <input type="file" id="foto-input-galeria-rev" accept="image/*" multiple class="hidden">
        <input type="file" id="foto-input-submit-rev" name="foto_depois[]" multiple class="hidden">
        <div id="foto-preview-rev" class="grid grid-cols-2 gap-3 mt-4"></div>
      </div>

      <div id="erro-localizacao" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-xl flex items-center gap-3">
        <i data-lucide="map-pin-off" class="w-5 h-5 shrink-0"></i>
        <span>Ative a localização e clique em "Atualizar Localização" antes de enviar.</span>
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-vivo-purple to-purple-700 hover:from-vivo-purpleDark hover:to-purple-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider flex items-center justify-center gap-2">
        <i data-lucide="refresh-cw" class="w-5 h-5"></i>
        Reenviar para análise
      </button>
    </form>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // ===== Geolocalização =====
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var locationText = document.getElementById('location-text');

    function updateLocationText(lat, lng) {
      if (locationText) {
        locationText.innerHTML = '<strong>' + lat + ', ' + lng + '</strong>' +
          ' <a href="https://www.google.com/maps?q=' + lat + ',' + lng + '" target="_blank" class="text-vivo-purple underline ml-2">Abrir no Maps</a>';
      }
    }

    function obterLocalizacao() {
      if (!navigator.geolocation) {
        if (locationText) locationText.textContent = 'Geolocalização não suportada.';
        return;
      }
      navigator.geolocation.getCurrentPosition(
        function (position) {
          var lat = position.coords.latitude.toFixed(6);
          var lng = position.coords.longitude.toFixed(6);
          if (latInput) latInput.value = lat;
          if (lngInput) lngInput.value = lng;
          updateLocationText(lat, lng);
        },
        function (error) {
          if (!locationText) return;
          var msg = 'Erro ao obter localização';
          if (error.code === 1) msg = 'Permissão negada. Ative a localização nas configurações.';
          else if (error.code === 2) msg = 'Localização indisponível.';
          else if (error.code === 3) msg = 'Tempo esgotado.';
          locationText.textContent = msg;
          if (latInput) latInput.value = '';
          if (lngInput) lngInput.value = '';
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
      );
    }

    var btnLoc = document.getElementById('btn-atualizar-loc');
    if (btnLoc) btnLoc.addEventListener('click', function (e) {
      e.preventDefault();
      obterLocalizacao();
    });
    obterLocalizacao();

    // ===== Envio do formulário: confirmação + sucesso =====
    var revisaoForm = document.getElementById('form-revisao');
    if (revisaoForm) {
      revisaoForm.addEventListener('submit', function (e) {
        var lat = document.getElementById('latitude') ? document.getElementById('latitude').value : '';
        var lng = document.getElementById('longitude') ? document.getElementById('longitude').value : '';
        var erroEl = document.getElementById('erro-localizacao');

        if (!lat || !lng) {
          e.preventDefault();
          if (erroEl) erroEl.classList.remove('hidden');
          return false;
        }

        e.preventDefault();

        // Confirmação do envio via SweetAlert2
        Swal.fire({
          title: 'Confirmar envio',
          text: 'Deseja reenviar a revisão com os dados informados?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sim, enviar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          confirmButtonColor: '#660099',
          allowOutsideClick: false,
          allowEscapeKey: false
        }).then(function (result) {
          if (!result.isConfirmed) return;

          Swal.fire({
            title: 'Enviando...',
            text: 'Aguarde enquanto salvamos os dados',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
          });

          var formData = new FormData(revisaoForm);

          fetch(revisaoForm.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            redirect: 'follow'
          }).then(function (res) {
            return res.url;
          }).then(function (finalUrl) {
            Swal.close();
            if (finalUrl && finalUrl.indexOf('erro') !== -1) {
              Swal.fire({
                icon: 'error',
                title: 'Não foi possível enviar',
                text: 'Verifique os dados e tente novamente.',
                confirmButtonColor: '#660099'
              });
            } else {
              Swal.fire({
                icon: 'success',
                title: 'Revisão enviada!',
                text: 'Dados salvos com sucesso.',
                confirmButtonText: 'Ir para a página inicial',
                confirmButtonColor: '#660099',
                allowOutsideClick: false,
                allowEscapeKey: false
              }).then(function () {
                window.location.href = '/dashboard';
              });
            }
          }).catch(function () {
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: 'Erro de conexão',
              text: 'Não foi possível contatar o servidor. Tente novamente.',
              confirmButtonColor: '#660099'
            });
          });
        });
      });
    }

    // ===== Fotos novas da execução =====
    var cameraInput = document.getElementById('foto-input-camera-rev');
    var galeriaInput = document.getElementById('foto-input-galeria-rev');
    var submitInput = document.getElementById('foto-input-submit-rev');
    var preview = document.getElementById('foto-preview-rev');
    var keepInput = document.getElementById('fotos-revisao-keep');
    var selectedFiles = [];
    var keepFotosIds = <?php echo json_encode(array_map('intval', $fotosExecucaoIds)); ?>;

    function updateKeepInput() {
      if (keepInput) keepInput.value = keepFotosIds.join(',');
    }

    function updateSubmitInput() {
      var dataTransfer = new DataTransfer();
      selectedFiles.forEach(function (file) { dataTransfer.items.add(file); });
      if (submitInput) submitInput.files = dataTransfer.files;
      updateKeepInput();
    }

    window.openFileInputRev = function (source) {
      if (source === 'camera') {
        if (cameraInput) cameraInput.click();
      } else {
        if (galeriaInput) galeriaInput.click();
      }
    };

    function handleFilesRev(input) {
      var files = Array.from(input.files || []);
      files.forEach(function (file) {
        var exists = selectedFiles.some(function (current) {
          return current.name === file.name && current.size === file.size && current.lastModified === file.lastModified;
        });
        if (!exists) selectedFiles.push(file);
      });
      input.value = '';
      updateSubmitInput();
      renderPreview();
    }

    function renderPreview() {
      if (!preview) return;
      preview.innerHTML = '';
      selectedFiles.forEach(function (file, index) {
        var card = document.createElement('div');
        card.className = 'relative rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white';

        var image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        image.className = 'w-full h-32 object-cover';

        var deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'absolute top-2 right-2 bg-white/90 text-red-600 rounded-full p-1 border border-red-100 hover:bg-white';
        deleteButton.innerHTML = '<span class="text-xs font-bold">&times;</span>';
        deleteButton.addEventListener('click', function () {
          selectedFiles.splice(index, 1);
          updateSubmitInput();
          renderPreview();
        });

        var info = document.createElement('div');
        info.className = 'p-2';
        info.innerHTML = '<p class="text-[11px] text-gray-500 truncate">' + file.name + '</p>';

        card.appendChild(image);
        card.appendChild(deleteButton);
        card.appendChild(info);
        preview.appendChild(card);
      });
    }

    if (cameraInput) cameraInput.addEventListener('change', function (event) { handleFilesRev(event.target); });
    if (galeriaInput) galeriaInput.addEventListener('change', function (event) { handleFilesRev(event.target); });

    // ===== Excluir fotos de execução existentes =====
    document.querySelectorAll('.btn-delete-foto').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var fotoId = btn.dataset.id;

        if (typeof Swal === 'undefined') { return; }

        Swal.fire({
          title: 'Excluir foto',
          text: 'Tem certeza que deseja excluir esta foto? A imagem será removida do sistema.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sim, excluir',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          confirmButtonColor: '#d33',
          allowOutsideClick: false,
          allowEscapeKey: false
        }).then(function (result) {
          if (!result.isConfirmed) return;

          Swal.fire({
            title: 'Excluindo...',
            text: 'Removendo a imagem do sistema.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
          });

          var csrfEl = document.querySelector('input[name="csrf_token"]');
          var formData = new FormData();
          formData.append('acao', 'deletar_foto');
          formData.append('foto_id', fotoId);
          formData.append('csrf_token', csrfEl ? csrfEl.value : '');

          fetch('/salvar-preventiva', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            redirect: 'follow'
          }).then(function (resp) {
            if (!resp.ok) throw new Error('Servidor respondeu com status ' + resp.status);
            return resp.json();
          }).then(function (result) {
            Swal.close();
            if (result.ok) {
              keepFotosIds = keepFotosIds.filter(function (id) { return String(id) !== String(fotoId); });
              updateKeepInput();
              var card = btn.closest('[data-arquivo-id]');
              if (card) card.remove();
              var grid = document.getElementById('fotos-execucao-grid');
              if (grid && grid.children.length === 0) {
                var section = grid.closest('.fotos-execucao-section');
                if (section) section.classList.add('hidden');
              }
              Swal.fire({
                icon: 'success',
                title: 'Foto excluída',
                text: 'A imagem foi removida com sucesso.',
                confirmButtonColor: '#660099'
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Erro ao excluir',
                text: result.error || 'Erro desconhecido.',
                confirmButtonColor: '#660099'
              });
            }
          }).catch(function (err) {
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: 'Erro na requisição',
              text: err.message,
              confirmButtonColor: '#660099'
            });
          });
        });
      });
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
