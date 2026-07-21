<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver autenticado, redireciona para o Dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: /dashboard');
    exit;
}

require_once __DIR__ . '/config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    if (!empty($usuario) && !empty($senha)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['usuario_id']   = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];

                header('Location: /dashboard');
                exit;
            } else {
                $erro = 'Usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro na autenticação. Tente novamente.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 space-y-6">
        
        <!-- Logo VIVO -->
        <div class="text-center">
            <div class="w-16 h-16 bg-vivo-purple rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-purple-200">
                <span class="text-white font-extrabold text-2xl tracking-tighter">V</span>
            </div>
            <h1 class="text-2xl font-bold text-vivo-dark">TechOps Mobile</h1>
            <p class="text-xs text-gray-400 mt-1">Gestão de Preventivas Vivo</p>
        </div>

        <!-- Mensagem de Erro -->
        <?php if (!empty($erro)): ?>
            <div class="bg-red-50 text-red-600 text-xs p-3 rounded-xl border border-red-200 text-center font-semibold">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <form action="/login" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-1">Usuário</label>
                <input type="text" name="usuario" required placeholder="ex: tecnico.teste"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50">
            </div>

            <div>
                <label class="block text-xs font-bold text-vivo-dark uppercase tracking-wider mb-1">Senha</label>
                <input type="password" name="senha" required placeholder="••••••••"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vivo-purple text-sm bg-gray-50">
            </div>

            <button type="submit" 
                    class="w-full bg-vivo-purple hover:bg-vivo-dark text-white font-bold py-3.5 rounded-xl shadow-lg shadow-purple-200 transition-all text-sm uppercase tracking-wider">
                Entrar no Sistema
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>