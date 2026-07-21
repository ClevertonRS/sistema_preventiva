<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /preventivas');
    exit;
}

// 1. Busca os dados da preventiva
$stmt = $pdo->prepare("SELECT * FROM preventivas_rede WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

if (!$p) {
    header('Location: /preventivas');
    exit;
}

// 2. Busca as fotos/arquivos salvos na tabela preventivas_arquivos
$stmtArquivos = $pdo->prepare("SELECT * FROM preventivas_arquivos WHERE preventiva_id = :id ORDER BY id DESC");
$stmtArquivos->execute([':id' => $id]);
$arquivos = $stmtArquivos->fetchAll();
?>

<!-- Header -->
<header class="bg-vivo-purple text-white p-4 shadow-md flex items-center gap-3 sticky top-0 z-10">
    <a href="/preventivas" class="text-xl font-bold p-1">&larr;</a>
    <h1 class="text-base font-bold">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></h1>
</header>

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
            <p><strong>Status:</strong>
                <span class="font-bold <?= $p['status'] === 'Pendente' ? 'text-amber-600' : 'text-emerald-600' ?>">
                    <?= htmlspecialchars($p['status']) ?>
                </span>
            </p>
        </div>
    </div>

    <?php if ($p['status'] === 'Pendente'): ?>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <p class="text-sm text-gray-600">Esta preventiva ainda não foi aceita. Ao aceitar, ela passará para <strong>Em Execução</strong> e poderá ser finalizada.</p>
            <form action="/salvar-preventiva" method="POST" class="space-y-4">
                <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="acao" value="aceitar">
                <button type="submit" class="w-full bg-vivo-purple hover:bg-vivo-purpleDark text-white font-bold py-3.5 rounded-xl shadow-md transition-all text-sm uppercase tracking-wider">
                    Aceitar Preventiva
                </button>
            </form>
        </div>
    <?php elseif ($p['status'] === 'Em Execução'): ?>
        <form action="/salvar-preventiva" method="POST" enctype="multipart/form-data" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <input type="hidden" name="preventiva_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="acao" value="finalizar">

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Descrição do Serviço</label>
                <textarea name="descricao" rows="4" required placeholder="Escreva o que foi realizado..." class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-2">Fotos da Execução</label>
                <input type="file" name="foto[]" accept="image/*" capture="environment" multiple class="w-full text-xs text-gray-500" />
                <p class="text-[10px] text-gray-400 mt-2">Envie uma ou mais imagens do equipamento e do serviço finalizado.</p>
            </div>

            <button type="submit" class="w-full bg-vivo-coral hover:bg-red-700 text-white font-bold py-3.5 rounded-xl shadow-md transition-all text-sm uppercase tracking-wider">
                Enviar para Análise
            </button>
        </form>
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