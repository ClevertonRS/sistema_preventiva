<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /revisao');
    exit;
}

$tecnicoId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM preventivas_rede WHERE id = :id AND tecnico_id = :tecnico_id AND status = 'revisao' LIMIT 1");
$stmt->execute([':id' => $id, ':tecnico_id' => $tecnicoId]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /revisao');
    exit;
}

$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE preventiva_id = :id ORDER BY criado_em DESC");
$stmtArquivos->execute([':id' => $id]);
$arquivos = $stmtArquivos->fetchAll();

$supervisorDescricao = $p['descricao_supervisor'] ?? $p['observacao_supervisor'] ?? $p['supervisor_descricao'] ?? $p['supervisor_obs'] ?? $p['revisor_observacao'] ?? $p['motivo_revisao'] ?? 'Descrição do supervisor não registrada.';
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">Revisão da Preventiva</h1>
      <p class="text-sm text-gray-500 mt-1">Envie novamente a descrição e as imagens para análise do supervisor.</p>
    </div>
    <a href="/revisao" class="text-xs text-vivo-purple font-semibold">Voltar</a>
  </div>
</div>

<main class="p-4 max-w-3xl mx-auto w-full space-y-4 flex-grow">
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
                <?php if (!empty($p['latitude']) && !empty($p['longitude'])): ?>
                    <p><strong class="text-gray-700">Localização:</strong>
                        <a href="https://www.google.com/maps?q=<?= $p['latitude'] ?>,<?= $p['longitude'] ?>" target="_blank" class="text-vivo-purple underline">
                            <?= $p['latitude'] ?>, <?= $p['longitude'] ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="bg-vivo-grayLight p-3 rounded-xl text-xs text-gray-500">
                <p><strong class="text-gray-700">Prioridade:</strong> <?= htmlspecialchars($p['prioridade']) ?></p>
                <p><strong class="text-gray-700">Chave Combinação:</strong> <?= htmlspecialchars($p['chave_combinacao']) ?></p>
                <p><strong class="text-gray-700">Aberta em:</strong> <?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
        <h3 class="text-sm font-bold text-vivo-purple">Comentário do supervisor</h3>
        <p class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
            <?= nl2br(htmlspecialchars($supervisorDescricao)) ?>
        </p>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
        <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização</h3>
        <p class="text-xs text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-100">
            <span id="location-text">Obtendo localização...</span>
        </p>
    </div>

    <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4 confirm-finalizar">
        <input type="hidden" name="preventiva_id" value="<?= htmlspecialchars($p['id']) ?>">
        <input type="hidden" name="acao" value="finalizar">
        <input type="hidden" name="return_url" value="/revisao-detalhe/<?= htmlspecialchars($p['id']) ?>">
        <input type="hidden" name="latitude" id="latitude" value="">
        <input type="hidden" name="longitude" id="longitude" value="">

        <div>
            <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição da revisão</label>
            <textarea name="descricao" rows="4" required placeholder="Escreva a nova descrição para envio ao supervisor..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos de revisão</label>
            <input id="foto-input" type="file" name="foto[]" accept="image/*" multiple class="w-full text-xs text-gray-500" />
            <p class="text-[10px] text-gray-400 mt-2">Envie uma ou mais imagens que ajudem o supervisor a analisar a revisão.</p>
            <div id="foto-preview" class="grid grid-cols-2 gap-3 mt-4"></div>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider flex items-center justify-center gap-2">
            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
            Reenviar para análise
        </button>
    </form>

    <?php if (!empty($arquivos)): ?>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="text-sm font-bold text-vivo-purple">Fotos já enviadas</h3>
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($arquivos as $arq): ?>
                    <div class="space-y-1">
                        <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" alt="Foto da preventiva" class="w-full h-[200px] object-cover rounded-xl border border-gray-200 shadow-sm">
                        <p class="text-[10px] text-gray-400"><?= htmlspecialchars($arq['nome_original'] ?? basename($arq['caminho_arquivo'])) ?></p>
                        <p class="text-[10px] text-gray-400 text-right">Enviado em: <?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const fotoInput = document.getElementById('foto-input');
    const preview = document.getElementById('foto-preview');
    let selectedFiles = [];

    const updateInputFiles = () => {
      const dataTransfer = new DataTransfer();
      selectedFiles.forEach((file) => dataTransfer.items.add(file));
      fotoInput.files = dataTransfer.files;
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

    fotoInput.addEventListener('change', function (event) {
      const files = Array.from(event.target.files || []);
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