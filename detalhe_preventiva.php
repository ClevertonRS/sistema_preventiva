<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/security.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /preventivas');
    exit;
}

// Busca a preventiva
$stmt = $pdo->prepare("SELECT * FROM preventivas_rede WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /preventivas');
    exit;
}

// IDOR protection: controle de acesso por status e nível
$userLevel = $_SESSION['user_nivel'] ?? 1; // 1=técnico, 2=supervisor, 3=admin
$isOwner = ($p['tecnico_id'] ?? 0) === $_SESSION['user_id'];
$isSupervisor = $userLevel >= 2;

// Regras de acesso:
// - Triagem: todos veem (para aceitar)
// - Em Execução: só dono ou supervisor/admin
// - Em Análise/Revisão/Concluída: dono ou supervisor/admin
if ($p['status'] === 'Em Execução' && !$isOwner && !$isSupervisor) {
    header('Location: /preventivas');
    exit;
}
if (in_array($p['status'], ['Em Análise', 'Revisão', 'Concluída'], true) && !$isOwner && !$isSupervisor) {
    header('Location: /preventivas');
    exit;
}

// Arquivos
$stmtArquivos = $pdo->prepare("SELECT caminho_arquivo, criado_em FROM preventivas_arquivos WHERE preventiva_id = :id ORDER BY id DESC");
$stmtArquivos->execute([':id' => $id]);
$arquivos = $stmtArquivos->fetchAll();
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h1>
      <p class="text-sm text-gray-500 mt-1">Detalhes da preventiva</p>
    </div>
  </div>
</div>

<main class="p-4 max-w-lg mx-auto w-full space-y-4 flex-grow">

    <!-- Dados da OS -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
        <h2 class="font-extrabold text-vivo-dark text-lg"><?= htmlspecialchars($p['titulo']) ?></h2>
        <div class="text-xs text-gray-600 space-y-1 border-t border-gray-100 pt-3">
            <p><strong>GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
            <p><strong>Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
            <p><strong>Chave Combinação:</strong> <?= htmlspecialchars($p['chave_combinacao']) ?></p>
            <p><strong>UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
            <p><strong>Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
            <p><strong>Prioridade:</strong> <?= htmlspecialchars($p['prioridade']) ?></p>
            <?php if (!empty($p['latitude']) && !empty($p['longitude'])): ?>
                <p><strong>Localização:</strong>
                    <a href="https://www.google.com/maps?q=<?= $p['latitude'] ?>,<?= $p['longitude'] ?>" target="_blank" class="text-vivo-purple underline text-xs">
                        <?= $p['latitude'] ?>, <?= $p['longitude'] ?>
                    </a>
                    <a href="https://www.google.com/maps?q=<?= $p['latitude'] ?>,<?= $p['longitude'] ?>" target="_blank" class="ml-3 inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-vivo-purple rounded-lg hover:bg-vivo-purpleDark transition">
                        <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i>
                        Abrir no Mapa
                    </a>
                </p>
            <?php endif; ?>
            <p><strong>Status:</strong>
                <span class="font-bold <?= $p['status'] === 'Pendente' ? 'text-amber-600' : 'text-emerald-600' ?>">
                    <?= htmlspecialchars($p['status']) ?>
                </span>
            </p>
        </div>
    </div>

    <?php if (!empty($arquivos) and $p['status'] == "triagem"): ?>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Fotos já enviadas</h3>
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($arquivos as $arq): ?>
                    <div class="space-y-1 text-center">
                        <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Evidência" class="mx-auto block w-auto max-h-[300px] rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                    </div>
                <?php endforeach; ?> 
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array($p['status'], ['Pendente', 'Triagem'], true)): ?>
        <div id="location-status" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização</h3>
            <p class="text-xs text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span id="location-text">Obtendo localização...</span>
            </p>
        </div>

        <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4 confirm-finalizar">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="acao" value="aceitar_finalizar">
            <input type="hidden" name="latitude" id="latitude" value="">
            <input type="hidden" name="longitude" id="longitude" value="">

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição do Serviço</label>
                <textarea name="descricao" rows="4" required placeholder="Escreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos da Execução</label>
                <div class="flex gap-2 mb-2">
                    <button type="button" id="btn-camera" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-input-camera').click()">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        Câmera
                    </button>
                    <button type="button" id="btn-galeria" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-input-galeria').click()">
                        <i data-lucide="image" class="w-4 h-4"></i>
                        Galeria
                    </button>
                </div>
                <input type="file" id="foto-input-camera" name="foto[]" accept="image/*" capture="environment" multiple class="hidden" onchange="handleFiles(this)" />
                <input type="file" id="foto-input-galeria" name="foto[]" accept="image/*" multiple class="hidden" onchange="handleFiles(this)" />
                <p class="text-[10px] text-gray-400 mt-2">Envie uma ou mais imagens do equipamento e do serviço finalizado.</p>
                <div id="foto-preview" class="grid grid-cols-2 gap-3 mt-4"></div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-vivo-purple to-purple-700 hover:from-vivo-purpleDark hover:to-purple-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-5 h-5"></i>
                Aceitar e Finalizar
            </button>
        </form>

        <?php if (!empty($arquivos)): ?>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Fotos já enviadas</h3>
                <div class="grid grid-cols-1 gap-3">
                    <?php foreach ($arquivos as $arq): ?>
                        <div class="space-y-1">
                            <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Evidência" class="w-full h-auto rounded-xl border border-gray-200 shadow-sm">
                            <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($p['status'] === 'Em Execução'): ?>
        <div id="location-status" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização</h3>
            <p class="text-xs text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-100">
                <span id="location-text">Obtendo localização...</span>
            </p>
        </div>

        <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4 confirm-finalizar">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="acao" value="finalizar">
            <input type="hidden" name="latitude" id="latitude" value="">
            <input type="hidden" name="longitude" id="longitude" value="">

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição do Serviço</label>
                <textarea name="descricao" rows="4" required placeholder="Escreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos da Execução</label>
                <div class="flex gap-2 mb-2">
                    <button type="button" id="btn-camera-exec" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-input-camera-exec').click()">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        Câmera
                    </button>
                    <button type="button" id="btn-galeria-exec" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-input-galeria-exec').click()">
                        <i data-lucide="image" class="w-4 h-4"></i>
                        Galeria
                    </button>
                </div>
                <input type="file" id="foto-input-camera-exec" name="foto[]" accept="image/*" capture="environment" multiple class="hidden" onchange="handleFilesExec(this)" />
                <input type="file" id="foto-input-galeria-exec" name="foto[]" accept="image/*" multiple class="hidden" onchange="handleFilesExec(this)" />
                <p class="text-[10px] text-gray-400 mt-2">Envie uma ou mais imagens do equipamento e do serviço finalizado.</p>
                <div id="foto-preview-exec" class="grid grid-cols-2 gap-3 mt-4"></div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-vivo-purple to-purple-700 hover:from-vivo-purpleDark hover:to-purple-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-5 h-5"></i>
                Enviar
            </button>
        </form>

        <?php if (!empty($arquivos)): ?>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Fotos já enviadas</h3>
                <div class="grid grid-cols-1 gap-3">
                    <?php foreach ($arquivos as $arq): ?>
                        <div class="space-y-1">
                            <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Evidência" class="w-full h-auto rounded-xl border border-gray-200 shadow-sm">
                            <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Exibição do Relatório Concluído + Imagens salvas -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Relatório do Técnico</h3>
            
            <div>
                <p class="text-xs font-semibold text-gray-400">Descrição:</p>
                <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded-xl mt-1 border border-gray-100">
                    <?= nl2br(htmlspecialchars($p['observacao_abertura'] ?? $p['descricao'] ?? 'Nenhuma descrição registrada.')) ?>
                </p>
            </div>

            <!-- Exibe as fotos cadastradas na preventivas_arquivos -->
            <?php if (!empty($arquivos)): ?>
                <div>
                    <p class="text-xs font-semibold text-gray-400 mb-2">Evidências Registradas:</p>
                    <div class="grid grid-cols-1 gap-3">
                        <?php foreach ($arquivos as $arq): ?>
                            <div class="space-y-1 text-center">
                                <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Evidência" class="mx-auto block w-auto max-h-[300px] rounded-xl border border-gray-200 shadow-sm">
                                <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const cameraInput = document.getElementById('foto-input-camera');
    const galeriaInput = document.getElementById('foto-input-galeria');
    const preview = document.getElementById('foto-preview');
    let selectedFiles = [];

    const updateInputFiles = () => {
      const dataTransfer = new DataTransfer();
      selectedFiles.forEach((file) => dataTransfer.items.add(file));
      // Update both inputs with the combined files
      if (cameraInput) cameraInput.files = dataTransfer.files;
      if (galeriaInput) galeriaInput.files = dataTransfer.files;
    };

    const renderPreview = () => {
      preview.innerHTML = '';

      if (selectedFiles.length === 0) {
        return;
      }

      selectedFiles.forEach((file, index) => {
        const card = document.createElement('div');
        card.className = 'relative rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white';

        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        image.className = 'w-full h-32 object-cover';

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'absolute top-2 right-2 bg-white/90 text-red-600 rounded-full p-1 border border-red-100 hover:bg-white';
        deleteButton.innerHTML = '<span class="text-xs font-bold">×</span>';
        deleteButton.addEventListener('click', function () {
          selectedFiles.splice(index, 1);
          updateInputFiles();
          renderPreview();
        });

        const info = document.createElement('div');
        info.className = 'p-2';
        info.innerHTML = `<p class="text-[11px] text-gray-500 truncate">${file.name}</p>`;

        card.appendChild(image);
        card.appendChild(deleteButton);
        card.appendChild(info);
        preview.appendChild(card);
      });
    };

    const handleFiles = (input) => {
      const files = Array.from(input.files || []);

      files.forEach((file) => {
        const exists = selectedFiles.some(
          (current) => current.name === file.name && current.size === file.size && current.lastModified === file.lastModified
        );
        if (!exists) {
          selectedFiles.push(file);
        }
      });

      updateInputFiles();
      renderPreview();
    };

    if (cameraInput) cameraInput.addEventListener('change', function (event) {
      handleFiles(event.target);
    });
    if (galeriaInput) galeriaInput.addEventListener('change', function (event) {
      handleFiles(event.target);
    });

    // Handler para o formulário "Em Execução"
    const cameraInputExec = document.getElementById('foto-input-camera-exec');
    const galeriaInputExec = document.getElementById('foto-input-galeria-exec');
    const previewExec = document.getElementById('foto-preview-exec');
    let selectedFilesExec = [];

    const handleFilesExec = (input) => {
      const files = Array.from(input.files || []);
      files.forEach((file) => {
        const exists = selectedFilesExec.some(
          (current) => current.name === file.name && current.size === file.size && current.lastModified === file.lastModified
        );
        if (!exists) {
          selectedFilesExec.push(file);
        }
      });
      renderPreviewExec();
    };

    const renderPreviewExec = () => {
      previewExec.innerHTML = '';
      if (selectedFilesExec.length === 0) return;

      selectedFilesExec.forEach((file, index) => {
        const card = document.createElement('div');
        card.className = 'relative rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white';

        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        image.className = 'w-full h-32 object-cover';

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'absolute top-2 right-2 bg-white/90 text-red-600 rounded-full p-1 border border-red-100 hover:bg-white';
        deleteButton.innerHTML = '<span class="text-xs font-bold">×</span>';
        deleteButton.addEventListener('click', function () {
          selectedFilesExec.splice(index, 1);
          renderPreviewExec();
        });

        const info = document.createElement('div');
        info.className = 'p-2';
        info.innerHTML = `<p class="text-[11px] text-gray-500 truncate">${file.name}</p>`;

        card.appendChild(image);
        card.appendChild(deleteButton);
        card.appendChild(info);
        previewExec.appendChild(card);
      });
    };

    if (cameraInputExec) cameraInputExec.addEventListener('change', function (event) {
      handleFilesExec(event.target);
    });
    if (galeriaInputExec) galeriaInputExec.addEventListener('change', function (event) {
      handleFilesExec(event.target);
    });

    // Geolocalização
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const locationText = document.getElementById('location-text');

    function updateLocationText(lat, lng) {
      if (locationText) {
        locationText.innerHTML = `📍 <strong>${lat}, ${lng}</strong>
          <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="text-vivo-purple underline ml-2">Abrir no Maps</a>`;
      }
    }

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const lat = position.coords.latitude.toFixed(6);
          const lng = position.coords.longitude.toFixed(6);
          if (latInput) latInput.value = lat;
          if (lngInput) lngInput.value = lng;
          updateLocationText(lat, lng);
        },
        function (error) {
          if (locationText) {
            let msg = 'Erro ao obter localização';
            if (error.code === 1) msg = 'Permissão de localização negada.';
            else if (error.code === 2) msg = 'Localização indisponível.';
            else if (error.code === 3) msg = 'Tempo de obtenção de localização esgotado.';
            locationText.textContent = '⚠️ ' + msg + ' A localização não será registrada.';
          }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    } else {
      if (locationText) {
        locationText.textContent = '⚠️ Geolocalização não suportada pelo navegador.';
      }
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>