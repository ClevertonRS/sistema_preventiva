<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Busca todas as ocorrências/preventivas
try {
    $stmt = $pdo->query("SELECT * FROM ocorrencias ORDER BY id DESC");
    $ocorrencias = $stmt->fetchAll();
} catch (PDOException $e) {
    $ocorrencias = [];
}

// Separa em pendentes e concluídas
$pendentes = array_filter($ocorrencias, fn($item) => $item['status'] === 'Pendente');
$concluidas = array_filter($ocorrencias, fn($item) => $item['status'] === 'Concluída');
?>

<!-- Topo do Dashboard -->
<header class="bg-vivo-purple text-white p-4 shadow-md sticky top-0 z-10 flex justify-between items-center">
    <div>
        <h1 class="text-base font-bold">Preventivas Vivo</h1>
        <p class="text-xs text-purple-200">Técnico: <?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Conectado') ?></p>
    </div>
    <a href="/logout" class="text-xs bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg transition-colors">
        Sair
    </a>
</header>

<main class="p-4 max-w-lg mx-auto w-full space-y-5 flex-grow">

    <!-- Resumo em Cards -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-2xl flex items-center gap-3">
            <span class="text-2xl">⏳</span>
            <div>
                <p class="text-[10px] uppercase font-bold text-amber-700 tracking-wider">Pendentes</p>
                <p class="text-xl font-extrabold text-amber-900"><?= count($pendentes) ?></p>
            </div>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 p-3.5 rounded-2xl flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="text-[10px] uppercase font-bold text-emerald-700 tracking-wider">Concluídas</p>
                <p class="text-xl font-extrabold text-emerald-900"><?= count($concluidas) ?></p>
            </div>
        </div>
    </div>

    <!-- Lista de Pendentes -->
    <section class="space-y-3">
        <h2 class="text-xs font-bold text-vivo-dark uppercase tracking-wider flex items-center gap-2">
            <span>🔴</span> Manutenções Pendentes
        </h2>

        <?php if (empty($pendentes)): ?>
            <div class="bg-white p-6 rounded-2xl border border-gray-100 text-center space-y-1">
                <p class="text-2xl">🎉</p>
                <p class="text-xs font-bold text-gray-600">Nenhuma preventiva pendente!</p>
                <p class="text-[11px] text-gray-400">Todas as ordens de serviço foram finalizadas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pendentes as $p): ?>
                <a href="/detalhe-preventiva?id=<?= $p['id'] ?>" class="block bg-white p-4 rounded-2xl shadow-sm border border-gray-100 hover:border-vivo-purple transition-all">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        <span class="text-xs font-extrabold text-vivo-purple">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">Pendente</span>
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm mb-1"><?= htmlspecialchars($p['titulo'] ?? $p['equipamento'] ?? 'Preventiva sem título') ?></h3>
                    <p class="text-xs text-gray-500">📍 <?= htmlspecialchars($p['local'] ?? 'Local não informado') ?></p>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Lista de Concluídas -->
    <?php if (!empty($concluidas)): ?>
    <section class="space-y-3 pt-2">
        <h2 class="text-xs font-bold text-vivo-dark uppercase tracking-wider flex items-center gap-2">
            <span>🟢</span> Histórico de Concluídas
        </h2>

        <?php foreach ($concluidas as $p): ?>
            <a href="/detalhe-preventiva?id=<?= $p['id'] ?>" class="block bg-white p-4 rounded-2xl shadow-sm border border-gray-100 opacity-80 hover:opacity-100 transition-all">
                <div class="flex justify-between items-start gap-2 mb-2">
                    <span class="text-xs font-bold text-gray-500">OS #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Concluída</span>
                </div>
                <h3 class="font-bold text-gray-700 text-sm mb-1"><?= htmlspecialchars($p['titulo'] ?? $p['equipamento'] ?? 'Preventiva') ?></h3>
                <p class="text-xs text-gray-400">📍 <?= htmlspecialchars($p['local'] ?? 'Local não informado') ?></p>
            </a>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>