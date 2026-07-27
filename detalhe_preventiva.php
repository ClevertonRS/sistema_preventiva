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

$stmt = $pdo->prepare("SELECT * FROM preventivas_rede WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /preventivas');
    exit;
}

$userLevel = $_SESSION['user_nivel'] ?? 1;
$userId = $_SESSION['user_id'];
$isSupervisor = $userLevel >= 2;

$stmtAtend = $pdo->prepare(
    "SELECT a.*, ua.nome AS nome_analista, ue.nome AS nome_executor
     FROM atendimentos a
     LEFT JOIN usuarios ua ON ua.id = a.tecnico_analise_id
     LEFT JOIN usuarios ue ON ue.id = a.tecnico_execucao_id
     WHERE a.preventiva_id = :preventiva_id
     ORDER BY a.criado_em DESC
     LIMIT 1"
);
$stmtAtend->execute([':preventiva_id' => $id]);
$atendimento = $stmtAtend->fetch();

$isAnalista = $atendimento && (int)$atendimento['tecnico_analise_id'] === $userId;
$isExecutor = $atendimento && (int)$atendimento['tecnico_execucao_id'] === $userId;
$isEnvolvido = $isAnalista || $isExecutor;

$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE preventiva_id = :id ORDER BY id DESC");
$stmtArquivos->execute([':id' => $id]);
$arquivos = $stmtArquivos->fetchAll();

$stmtArquivosAtendimento = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE atendimento_id = :atendimento_id ORDER BY criado_em ASC");
if ($atendimento) {
    $stmtArquivosAtendimento->execute([':atendimento_id' => $atendimento['id']]);
    $arquivosAtendimento = $stmtArquivosAtendimento->fetchAll();
} else {
    $arquivosAtendimento = [];
}

$erroLocalizacao = ($_GET['erro'] ?? '') === 'localizacao';
?>

<div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-xl font-bold text-vivo-purple">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h1>
      <p class="text-sm text-gray-500 mt-1">Detalhes da preventiva</p>
    </div>
  </div>
</div>

<?php if ($erroLocalizacao): ?>
<div class="max-w-lg mx-auto mb-4 px-4">
  <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-xl flex items-center gap-3">
    <i data-lucide="map-pin-off" class="w-5 h-5 shrink-0"></i>
    <span><strong>Localização não registrada.</strong> Ative a localização no dispositivo e clique em "Atualizar Localização" antes de enviar.</span>
  </div>
</div>
<?php endif; ?>

<?php if (($_GET['sucesso'] ?? '') === '1'): ?>
<div class="max-w-lg mx-auto mb-4 px-4">
  <div id="toast-sucesso" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-4 rounded-xl flex items-center gap-3">
    <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
    <span><strong>Registro salvo com sucesso!</strong></span>
  </div>
</div>
<script>
setTimeout(function() {
  var t = document.getElementById('toast-sucesso');
  if (t) { t.style.transition = 'opacity 0.5s'; t.style.opacity = '0'; setTimeout(function(){ t.remove(); }, 500); }
}, 4000);
</script>
<?php endif; ?>

<main class="p-4 max-w-lg mx-auto w-full space-y-4 flex-grow" id="main-content">

    <div id="loading-overlay" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center" style="position:fixed;top:0;left:0;right:0;bottom:0;">
      <div class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-3 max-w-[260px]">
        <div class="w-10 h-10 border-4 border-vivo-purple border-t-transparent rounded-full animate-spin"></div>
        <p class="text-base font-bold text-gray-700">Salvando...</p>
        <p class="text-xs text-gray-400 text-center">Aguarde, não feche esta página.</p>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
        <div class="text-xs text-gray-600 space-y-1 border-t border-gray-100 pt-3">
            <p><strong>GPON:</strong> <?= htmlspecialchars($p['gpon']) ?></p>
            <p><strong>Splitter:</strong> <?= htmlspecialchars($p['splitter']) ?></p>
            <p><strong>Chave Combinação:</strong> <?= htmlspecialchars($p['chave_combinacao']) ?></p>
            <p><strong>UF:</strong> <?= htmlspecialchars($p['uf']) ?></p>
            <p><strong>Localidade:</strong> <?= htmlspecialchars($p['localidade']) ?></p>
            <p><strong>Prioridade:</strong> <?= htmlspecialchars($p['prioridade']) ?></p>

            <p><strong>Status:</strong>
                <span class="font-bold <?= $p['status'] === 'aberta' ? 'text-amber-600' : ($p['status'] === 'concluida' ? 'text-emerald-600' : 'text-blue-600') ?>">
                    <?= htmlspecialchars($p['status']) ?>
                </span>
            </p>
            <?php if ($atendimento): ?>
                <p><strong>Analista:</strong> <?= htmlspecialchars($atendimento['nome_analista'] ?? 'N/A') ?></p>
                <p><strong>Executor:</strong> <?= htmlspecialchars($atendimento['nome_executor'] ?? 'Aguardando') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($p['status'] === 'aberta'): ?>
        <div id="location-status" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização <span id="loc-obrigatorio" class="text-red-500">*</span></h3>
            <p class="text-xs text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-100" id="location-text">Obtendo localização...</p>
            <button type="button" id="btn-atualizar-localizacao" class="w-full text-xs font-semibold text-vivo-purple bg-vivo-purpleLight py-2 rounded-lg hover:bg-vivo-purple/10 transition flex items-center justify-center gap-1">
                <i data-lucide="refresh-cw" class="w-3 h-3"></i> Atualizar Localização
            </button>
        </div>

        <form id="form-aberta" action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="latitude" id="latitude" value="">
            <input type="hidden" name="longitude" id="longitude" value="">

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Tipo de Atendimento</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 p-3 border rounded-xl cursor-pointer has-[:checked]:bg-vivo-purpleLight has-[:checked]:border-vivo-purple bg-gray-50 border-gray-200">
                        <input type="radio" name="modo_atendimento" value="completo" checked class="accent-vivo-purple">
                        <div>
                            <span class="text-sm font-bold text-gray-800">Completo</span>
                            <p class="text-[10px] text-gray-500">Análise + Execução</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-2 p-3 border rounded-xl cursor-pointer has-[:checked]:bg-vivo-purpleLight has-[:checked]:border-vivo-purple bg-gray-50 border-gray-200">
                        <input type="radio" name="modo_atendimento" value="analise" class="accent-vivo-purple">
                        <div>
                            <span class="text-sm font-bold text-gray-800">Apenas Análise</span>
                            <p class="text-[10px] text-gray-500">Outro técnico executa</p>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição da Análise <span class="text-red-500">*</span></label>
                <textarea name="descricao_analise" rows="3" required placeholder="Descreva o problema identificado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
            </div>

            <div id="campo-descricao-execucao">
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição da Execução <span class="text-red-500">*</span></label>
                <textarea name="descricao_execucao" rows="3" required placeholder="Descreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
            </div>

            <div id="fotos-antes-section">
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos - Antes do Serviço <span class="text-gray-400 font-normal">(opcional)</span></label>
                <div class="flex gap-2 mb-2">
                    <button type="button" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-antes-camera').click()">
                        <i data-lucide="camera" class="w-4 h-4"></i>Câmera
                    </button>
                    <button type="button" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-antes-galeria').click()">
                        <i data-lucide="image" class="w-4 h-4"></i>Galeria
                    </button>
                </div>
                <input type="file" id="foto-antes-camera" accept="image/*" capture="environment" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                <input type="file" id="foto-antes-galeria" accept="image/*" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                <input type="file" id="foto-antes-submit" name="foto_antes[]" multiple class="hidden">
                <div id="foto-preview-antes" class="grid grid-cols-2 gap-3 mt-4"></div>
            </div>

            <div id="fotos-depois-section">
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos - Depois do Serviço <span class="text-gray-400 font-normal">(opcional)</span></label>
                <div class="flex gap-2 mb-2">
                    <button type="button" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-depois-camera').click()">
                        <i data-lucide="camera" class="w-4 h-4"></i>Câmera
                    </button>
                    <button type="button" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-depois-galeria').click()">
                        <i data-lucide="image" class="w-4 h-4"></i>Galeria
                    </button>
                </div>
                <input type="file" id="foto-depois-camera" accept="image/*" capture="environment" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                <input type="file" id="foto-depois-galeria" accept="image/*" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                <input type="file" id="foto-depois-submit" name="foto_depois[]" multiple class="hidden">
                <div id="foto-preview-depois" class="grid grid-cols-2 gap-3 mt-4"></div>
            </div>

            <div id="erro-localizacao-form" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-xl flex items-center gap-3">
                <i data-lucide="map-pin-off" class="w-5 h-5 shrink-0"></i>
                <span>Ative a localização e clique em "Atualizar Localização" antes de enviar.</span>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-vivo-purple to-purple-700 hover:from-vivo-purpleDark hover:to-purple-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider flex items-center justify-center gap-2">
                <i data-lucide="send" class="w-5 h-5"></i>
                <span id="btn-texto">Aceitar e Finalizar</span>
            </button>
        </form>

    <?php elseif ($p['status'] === 'em_atendimento' && $atendimento): ?>
        <?php if ($atendimento['status'] === 'analise' && $isAnalista): ?>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <h3 class="text-sm font-bold text-vivo-purple">Análise Realizada</h3>
                <div class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <?= nl2br(htmlspecialchars($atendimento['descricao_analise'] ?? 'Nenhuma descrição.')) ?>
                </div>

                <?php $fotosAnalise = array_filter($arquivosAtendimento, fn($f) => $f['tipo'] === 'analise'); ?>
                <?php if (!empty($fotosAnalise)): ?>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-2">Fotos da Análise:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($fotosAnalise as $arq): ?>
                                <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="rounded-xl border border-gray-200 h-32 object-cover w-full">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="location-status" class="bg-gray-50 p-3 rounded-xl border border-gray-100 space-y-2">
                    <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização <span class="text-red-500">*</span></h3>
                    <p class="text-xs text-gray-500" id="location-text2">Obtendo localização...</p>
                    <button type="button" class="btn-atualizar-loc text-xs font-semibold text-vivo-purple hover:underline" data-lat="latitude2" data-lng="longitude2" data-text="location-text2">
                        <i data-lucide="refresh-cw" class="w-3 h-3 inline"></i> Atualizar Localização
                    </button>
                </div>

                <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="space-y-4 form-com-localizacao">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="atendimento_id" value="<?= $atendimento['id'] ?>">
                    <input type="hidden" name="acao" value="assumir_execucao">
                    <input type="hidden" name="latitude" id="latitude2" value="">
                    <input type="hidden" name="longitude" id="longitude2" value="">

                    <div>
                        <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição da Execução <span class="text-red-500">*</span></label>
                        <textarea name="descricao_execucao" rows="4" required placeholder="Descreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos - Depois do Serviço <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <div class="flex gap-2 mb-2">
                            <button type="button" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-assumir-camera').click()">
                                <i data-lucide="camera" class="w-4 h-4"></i>Câmera
                            </button>
                            <button type="button" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-assumir-galeria').click()">
                                <i data-lucide="image" class="w-4 h-4"></i>Galeria
                            </button>
                        </div>
                        <input type="file" id="foto-assumir-camera" accept="image/*" capture="environment" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                        <input type="file" id="foto-assumir-galeria" accept="image/*" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                        <input type="file" id="foto-assumir-submit" name="foto_depois[]" multiple class="hidden">
                        <div id="foto-preview-assumir" class="grid grid-cols-2 gap-3 mt-4"></div>
                    </div>

                    <div id="erro-localizacao-assumir" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-xl flex items-center gap-3">
                        <i data-lucide="map-pin-off" class="w-5 h-5 shrink-0"></i>
                        <span>Ative a localização e clique em "Atualizar Localização" antes de enviar.</span>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-vivo-purple to-purple-700 hover:from-vivo-purpleDark hover:to-purple-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider">
                        <i data-lucide="send" class="w-5 h-5 inline"></i> Assumir e Finalizar Execução
                    </button>
                </form>
            </div>

        <?php elseif ($atendimento['status'] === 'analise' && !$isAnalista && !$isExecutor): ?>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <div class="bg-blue-50 p-4 rounded-xl text-sm text-blue-800 border border-blue-200">
                    <strong>Análise realizada por:</strong> <?= htmlspecialchars($atendimento['nome_analista']) ?>
                    <p class="mt-1"><?= nl2br(htmlspecialchars($atendimento['descricao_analise'] ?? '')) ?></p>
                </div>

                <?php $fotosAnalise2 = array_filter($arquivosAtendimento, fn($f) => $f['tipo'] === 'analise'); ?>
                <?php if (!empty($fotosAnalise2)): ?>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-2">Fotos da Análise:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($fotosAnalise2 as $arq): ?>
                                <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="rounded-xl border border-gray-200 h-32 object-cover w-full">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="location-status" class="bg-gray-50 p-3 rounded-xl border border-gray-100 space-y-2">
                    <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização <span class="text-red-500">*</span></h3>
                    <p class="text-xs text-gray-500" id="location-text5">Obtendo localização...</p>
                    <button type="button" class="btn-atualizar-loc text-xs font-semibold text-vivo-purple hover:underline" data-lat="latitude5" data-lng="longitude5" data-text="location-text5">
                        <i data-lucide="refresh-cw" class="w-3 h-3 inline"></i> Atualizar Localização
                    </button>
                </div>

                <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="space-y-4 form-com-localizacao">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="atendimento_id" value="<?= $atendimento['id'] ?>">
                    <input type="hidden" name="acao" value="assumir_execucao">
                    <input type="hidden" name="latitude" id="latitude5" value="">
                    <input type="hidden" name="longitude" id="longitude5" value="">

                    <div>
                        <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição da Execução <span class="text-red-500">*</span></label>
                        <textarea name="descricao_execucao" rows="4" required placeholder="Descreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos - Depois do Serviço <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <div class="flex gap-2 mb-2">
                            <button type="button" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-assumir2-camera').click()">
                                <i data-lucide="camera" class="w-4 h-4"></i>Câmera
                            </button>
                            <button type="button" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-assumir2-galeria').click()">
                                <i data-lucide="image" class="w-4 h-4"></i>Galeria
                            </button>
                        </div>
                        <input type="file" id="foto-assumir2-camera" accept="image/*" capture="environment" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                        <input type="file" id="foto-assumir2-galeria" accept="image/*" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                        <input type="file" id="foto-assumir2-submit" name="foto_depois[]" multiple class="hidden">
                        <div id="foto-preview-assumir2" class="grid grid-cols-2 gap-3 mt-4"></div>
                    </div>

                    <div id="erro-localizacao-assumir2" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-xl flex items-center gap-3">
                        <i data-lucide="map-pin-off" class="w-5 h-5 shrink-0"></i>
                        <span>Ative a localização e clique em "Atualizar Localização" antes de enviar.</span>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider">
                        <i data-lucide="send" class="w-5 h-5 inline"></i> Assumir Execução
                    </button>
                </form>
            </div>

        <?php elseif ($atendimento['status'] === 'revisao' && ($isAnalista || $isExecutor)): ?>
            <div id="location-status" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
                <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Sua Localização <span class="text-red-500">*</span></h3>
                <p class="text-xs text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-100" id="location-text4">Obtendo localização...</p>
                <button type="button" class="btn-atualizar-loc text-xs font-semibold text-vivo-purple bg-vivo-purpleLight py-2 rounded-lg hover:bg-vivo-purple/10 transition flex items-center justify-center gap-1" data-lat="latitude4" data-lng="longitude4" data-text="location-text4">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> Atualizar Localização
                </button>
            </div>

            <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4 form-com-localizacao">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="atendimento_id" value="<?= $atendimento['id'] ?>">
                <input type="hidden" name="acao" value="finalizar_revisao">
                <input type="hidden" name="latitude" id="latitude4" value="">
                <input type="hidden" name="longitude" id="longitude4" value="">

                <?php if (!empty($atendimento['descricao_execucao'])): ?>
                    <div class="bg-amber-50 p-3 rounded-xl text-xs text-amber-800 border border-amber-200">
                        <strong>Descrição anterior:</strong><br>
                        <?= nl2br(htmlspecialchars($atendimento['descricao_execucao'])) ?>
                    </div>
                <?php endif; ?>

                <?php $fotosRev = $arquivosAtendimento; ?>
                <?php if (!empty($fotosRev)): ?>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 mb-2">Fotos já enviadas:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($fotosRev as $arq): ?>
                                <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="rounded-xl border border-gray-200 h-32 object-cover w-full">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Nova Descrição (correção) <span class="text-red-500">*</span></label>
                    <textarea name="descricao" rows="4" required placeholder="Descreva as correções realizadas..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Novas Fotos - Depois do Serviço <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <div class="flex gap-2 mb-2">
                        <button type="button" class="flex-1 bg-vivo-purple text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-rev-camera').click()">
                            <i data-lucide="camera" class="w-4 h-4"></i>Câmera
                        </button>
                        <button type="button" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-1" onclick="document.getElementById('foto-rev-galeria').click()">
                            <i data-lucide="image" class="w-4 h-4"></i>Galeria
                        </button>
                    </div>
                    <input type="file" id="foto-rev-camera" accept="image/*" capture="environment" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                    <input type="file" id="foto-rev-galeria" accept="image/*" multiple style="position:fixed;top:-100px;left:-100px;opacity:0;width:1px;height:1px;pointer-events:none">
                    <input type="file" id="foto-rev-submit" name="foto_depois[]" multiple class="hidden">
                    <div id="foto-preview-rev" class="grid grid-cols-2 gap-3 mt-4"></div>
                </div>

                <div id="erro-localizacao-rev" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-xl flex items-center gap-3">
                    <i data-lucide="map-pin-off" class="w-5 h-5 shrink-0"></i>
                    <span>Ative a localização e clique em "Atualizar Localização" antes de enviar.</span>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-vivo-purple to-purple-700 hover:from-vivo-purpleDark hover:to-purple-900 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-200 hover:shadow-xl transition-all duration-300 text-sm uppercase tracking-wider">
                    <i data-lucide="refresh-cw" class="w-5 h-5 inline"></i> Reenviar para Análise
                </button>
            </form>

        <?php else: ?>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 text-center text-gray-500 text-sm">
                Esta preventiva está em atendimento por outro técnico.
            </div>
        <?php endif; ?>



    <?php elseif ($p['status'] === 'concluida' || ($atendimento && $atendimento['status'] === 'concluido')): ?>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Relatório do Serviço</h3>

            <?php if ($atendimento): ?>
                <?php
                    $fotosAnaliseConcluida = array_filter($arquivosAtendimento, fn($f) => $f['tipo'] === 'analise');
                    $fotosExecucaoConcluida = array_filter($arquivosAtendimento, fn($f) => $f['tipo'] === 'execucao');
                ?>

                <div class="border-l-4 border-amber-400 pl-4 space-y-2">
                    <h4 class="text-xs font-bold text-amber-700 uppercase tracking-wider">Análise</h4>
                    <p class="text-xs text-gray-500"><strong>Técnico:</strong> <?= htmlspecialchars($atendimento['nome_analista'] ?? 'N/A') ?></p>
                    <?php if (!empty($atendimento['descricao_analise'])): ?>
                        <p class="text-sm text-gray-800 bg-amber-50 p-3 rounded-xl border border-amber-200">
                            <?= nl2br(htmlspecialchars($atendimento['descricao_analise'])) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($atendimento['latitude_analise']) && !empty($atendimento['longitude_analise'])): ?>
                        <a href="https://www.google.com/maps?q=<?= $atendimento['latitude_analise'] ?>,<?= $atendimento['longitude_analise'] ?>" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition w-fit">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>Local
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($fotosAnaliseConcluida)): ?>
                        <p class="text-xs font-semibold text-amber-600">Fotos - Análise:</p>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($fotosAnaliseConcluida as $arq): ?>
                                <div class="space-y-1">
                                    <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="w-full h-32 object-cover rounded-xl border border-amber-200 shadow-sm">
                                    <p class="text-[10px] text-gray-400 text-right"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="border-l-4 border-emerald-400 pl-4 space-y-2">
                    <h4 class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Execução</h4>
                    <p class="text-xs text-gray-500"><strong>Técnico:</strong> <?= htmlspecialchars($atendimento['nome_executor'] ?? 'N/A') ?></p>
                    <?php if (!empty($atendimento['descricao_execucao'])): ?>
                        <p class="text-sm text-gray-800 bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                            <?= nl2br(htmlspecialchars($atendimento['descricao_execucao'])) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($atendimento['latitude_execucao']) && !empty($atendimento['longitude_execucao'])): ?>
                        <a href="https://www.google.com/maps?q=<?= $atendimento['latitude_execucao'] ?>,<?= $atendimento['longitude_execucao'] ?>" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition w-fit">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>Local
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($fotosExecucaoConcluida)): ?>
                        <p class="text-xs font-semibold text-emerald-600">Fotos - Execução:</p>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($fotosExecucaoConcluida as $arq): ?>
                                <div class="space-y-1">
                                    <img src="/<?= htmlspecialchars($arq['caminho_arquivo']) ?>" class="w-full h-32 object-cover rounded-xl border border-emerald-200 shadow-sm">
                                    <p class="text-[10px] text-gray-400 text-right"><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <?= nl2br(htmlspecialchars($p['observacao_abertura'] ?? 'Nenhuma descrição registrada.')) ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // ===== Toggle modo atendimento =====
    var modoRadios = document.querySelectorAll('input[name="modo_atendimento"]');
    var campoDescExec = document.getElementById('campo-descricao-execucao');
    var fotosDepoisSection = document.getElementById('fotos-depois-section');
    var btnTexto = document.getElementById('btn-texto');

    function toggleModo() {
      var isCompleto = document.querySelector('input[name="modo_atendimento"]:checked')?.value === 'completo';
      if (campoDescExec) {
        campoDescExec.classList.toggle('hidden', !isCompleto);
        var ta = campoDescExec.querySelector('textarea');
        if (ta) ta.required = isCompleto;
      }
      if (fotosDepoisSection) {
        fotosDepoisSection.classList.toggle('hidden', !isCompleto);
      }
      if (btnTexto) btnTexto.textContent = isCompleto ? 'Aceitar e Finalizar' : 'Iniciar Análise';
    }

    if (modoRadios.length) {
      modoRadios.forEach(function(r) { r.addEventListener('change', toggleModo); });
      toggleModo();
    }

    // ===== Geolocalização =====
    function obterLocalizacao(latId, lngId, textId) {
      return new Promise(function (resolve) {
        var latInput = document.getElementById(latId);
        var lngInput = document.getElementById(lngId);
        var locationText = document.getElementById(textId);
        if (!latInput || !lngInput || !locationText) { resolve(false); return; }

        function updateText(lat, lng) {
          locationText.innerHTML = '<strong>' + lat + ', ' + lng + '</strong> — ' +
            '<a href="https://www.google.com/maps?q=' + lat + ',' + lng + '" target="_blank" class="text-vivo-purple underline">Abrir no Maps</a>';
        }

        if (!navigator.geolocation) {
          locationText.textContent = 'Geolocalização não suportada.';
          resolve(false);
          return;
        }

        navigator.geolocation.getCurrentPosition(
          function (position) {
            var lat = position.coords.latitude.toFixed(6);
            var lng = position.coords.longitude.toFixed(6);
            latInput.value = lat;
            lngInput.value = lng;
            updateText(lat, lng);
            resolve(true);
          },
          function (error) {
            var msg = 'Erro ao obter localização';
            if (error.code === 1) msg = 'Permissão negada. Ative a localização nas configurações.';
            else if (error.code === 2) msg = 'Localização indisponível.';
            else if (error.code === 3) msg = 'Tempo esgotado.';
            locationText.textContent = msg;
            latInput.value = '';
            lngInput.value = '';
            resolve(false);
          },
          { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
      });
    }

    obterLocalizacao('latitude', 'longitude', 'location-text');
    obterLocalizacao('latitude2', 'longitude2', 'location-text2');
    obterLocalizacao('latitude3', 'longitude3', 'location-text3');
    obterLocalizacao('latitude4', 'longitude4', 'location-text4');
    obterLocalizacao('latitude5', 'longitude5', 'location-text5');

    document.querySelectorAll('.btn-atualizar-loc, #btn-atualizar-localizacao').forEach(function(btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var latId = this.dataset.lat || 'latitude';
        var lngId = this.dataset.lng || 'longitude';
        var textId = this.dataset.text || 'location-text';
        obterLocalizacao(latId, lngId, textId);
      });
    });

    // ===== Loading overlay no submit =====
    function showLoading() {
      var overlay = document.getElementById('loading-overlay');
      var btns = document.querySelectorAll('form button[type="submit"]');
      if (overlay) overlay.classList.remove('hidden');
      btns.forEach(function(b) { b.disabled = true; });
    }

    document.querySelectorAll('form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        setTimeout(function() {
          if (!e.defaultPrevented) showLoading();
        }, 0);
      });
    });

    // ===== Validação de localização no submit =====
    function validarLocalizacao(form, latId, erroId) {
      form.addEventListener('submit', function (e) {
        var lat = document.getElementById(latId)?.value;
        var lng = document.getElementById(latId.replace('latitude', 'longitude'))?.value;
        var erroEl = document.getElementById(erroId);
        if (!lat || !lng) {
          e.preventDefault();
          e.stopImmediatePropagation();
          if (erroEl) erroEl.classList.remove('hidden');
          return false;
        }
      });
    }

    var formAberta = document.getElementById('form-aberta');
    if (formAberta) validarLocalizacao(formAberta, 'latitude', 'erro-localizacao-form');

    document.querySelectorAll('.form-com-localizacao').forEach(function(form) {
      var latInput = form.querySelector('input[name="latitude"]');
      if (latInput) {
        var latId = latInput.id;
        var erroId = form.querySelector('[id^="erro-localizacao-"]')?.id;
        if (erroId) validarLocalizacao(form, latId, erroId);
      }
    });

    // ===== Upload de fotos com preview e deleção (Câmera + Galeria) =====
    function initFotoInput(cameraId, galeriaId, submitId, previewId) {
      var camera = document.getElementById(cameraId);
      var galeria = document.getElementById(galeriaId);
      var submit = document.getElementById(submitId);
      var prev = document.getElementById(previewId);
      if (!camera || !galeria || !submit || !prev) return;

      var files = [];

      function syncFiles() {
        var dt = new DataTransfer();
        files.forEach(function(f) { dt.items.add(f); });
        submit.files = dt.files;
      }

      function render() {
        prev.innerHTML = '';
        files.forEach(function (file, idx) {
          var card = document.createElement('div');
          card.className = 'relative rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white';
          var img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          img.className = 'w-full h-32 object-cover';
          var del = document.createElement('button');
          del.type = 'button';
          del.className = 'absolute top-2 right-2 bg-white/90 text-red-600 rounded-full p-1 border border-red-100 hover:bg-white';
          del.innerHTML = '<span class="text-xs font-bold">×</span>';
          del.addEventListener('click', function () { files.splice(idx, 1); syncFiles(); render(); });
          var info = document.createElement('div');
          info.className = 'p-2';
          info.innerHTML = '<p class="text-[11px] text-gray-500 truncate">' + file.name + '</p>';
          card.appendChild(img); card.appendChild(del); card.appendChild(info);
          prev.appendChild(card);
        });
      }

      function handleFiles(input) {
        var novos = Array.from(input.files || []);
        input.value = '';
        novos.forEach(function (f) {
          if (!files.some(function (c) { return c.name === f.name && c.size === f.size && c.lastModified === f.lastModified; })) {
            files.push(f);
          }
        });
        syncFiles();
        render();
      }

      camera.addEventListener('change', function () { handleFiles(this); });
      galeria.addEventListener('change', function () { handleFiles(this); });
    }

    initFotoInput('foto-antes-camera', 'foto-antes-galeria', 'foto-antes-submit', 'foto-preview-antes');
    initFotoInput('foto-depois-camera', 'foto-depois-galeria', 'foto-depois-submit', 'foto-preview-depois');
    initFotoInput('foto-assumir-camera', 'foto-assumir-galeria', 'foto-assumir-submit', 'foto-preview-assumir');
    initFotoInput('foto-assumir2-camera', 'foto-assumir2-galeria', 'foto-assumir2-submit', 'foto-preview-assumir2');
    initFotoInput('foto-rev-camera', 'foto-rev-galeria', 'foto-rev-submit', 'foto-preview-rev');
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
