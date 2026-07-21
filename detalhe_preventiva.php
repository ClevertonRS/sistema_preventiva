<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /dashboard');
    exit;
}

// 1. Busca os dados da preventiva
$stmt = $pdo->prepare("SELECT * FROM ocorrencias WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /dashboard');
    exit;
}

// 2. Busca as fotos/arquivos salvos na tabela preventivas_arquivos
$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE preventiva_id = :id ORDER BY id DESC");
$stmtArquivos->execute([':id' => $id]);
$arquivos = $stmtArquivos->fetchAll();
?>

<!-- Header -->
<header class="bg-vivo-purple text-white p-4 shadow-md flex items-center gap-3 sticky top-0 z-10">
    <a href="/dashboard" class="text-xl font-bold p-1">&larr;</a>
    <h1 class="text-base font-bold">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h1>
</header>

<main class="p-4 max-w-lg mx-auto w-full space-y-4 flex-grow">

    <!-- Dados da OS -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-2">
        <h2 class="font-extrabold text-vivo-dark text-lg"><?= htmlspecialchars($p['titulo']) ?></h2>
        <div class="text-xs text-gray-600 space-y-1 border-t border-gray-100 pt-3">
            <p><strong>Equipamento:</strong> <?= htmlspecialchars($p['equipamento']) ?></p>
            <p><strong>Localização:</strong> <?= htmlspecialchars($p['local']) ?></p>
            <p><strong>Status:</strong> 
                <span class="font-bold <?= $p['status'] === 'Pendente' ? 'text-amber-600' : 'text-emerald-600' ?>">
                    <?= htmlspecialchars($p['status']) ?>
                </span>
            </p>
        </div>
    </div>

    <?php if ($p['status'] === 'Pendente'): ?>
        <!-- Form de Preenchimento -->
        <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Observações / Descrição</label>
                <textarea name="descricao" rows="4" required placeholder="Procedimentos realizados..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Foto da Evidência (Câmera)</label>
                <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-vivo-purple/30 rounded-2xl cursor-pointer bg-vivo-bg hover:bg-purple-50 transition-colors">
                    <div class="flex flex-col items-center justify-center text-center p-4">
                        <span class="text-3xl mb-1">📸</span>
                        <p class="text-xs font-bold text-vivo-purple" id="fotoLabel">Tirar Foto do Equipamento</p>
                        <p class="text-[10px] text-gray-400 mt-1">Toque para acionar a câmera</p>
                    </div>
                    <input type="file" name="foto" accept="image/*" capture="environment" required class="hidden" onchange="document.getElementById('fotoLabel').innerText = 'Foto Selecionada ✅'">
                </label>
            </div>

            <button type="submit" class="w-full bg-vivo-coral hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-md transition-all text-sm uppercase tracking-wider">
                Finalizar Preventiva
            </button>
        </form>

    <?php else: ?>
        <!-- Exibição do Relatório Concluído + Imagens salvas -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="text-xs font-bold text-vivo-dark uppercase tracking-wider">Relatório do Técnico</h3>
            
            <div>
                <p class="text-xs font-semibold text-gray-400">Descrição:</p>
                <p class="text-sm text-gray-800 bg-gray-50 p-3 rounded-xl mt-1 border border-gray-100">
                    <?= nl2br(htmlspecialchars($p['descricao'])) ?>
                </p>
            </div>

            <!-- Exibe as fotos cadastradas na preventivas_arquivos -->
            <?php if (!empty($arquivos)): ?>
                <div>
                    <p class="text-xs font-semibold text-gray-400 mb-2">Evidências Registradas:</p>
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
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>