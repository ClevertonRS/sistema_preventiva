<?php
session_start();
require_once 'config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        $erro = 'Preencha o usuário e a senha.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND status = 1");
            $stmt->execute([':usuario' => $usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_usuario'] = $user['usuario'];
                $_SESSION['user_nivel'] = $user['nivel'];

                header("Location: index.php");
                exit;
            } else {
                $erro = 'Usuário ou senha inválidos, ou conta inativa.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Vivo - Login</title>

    <meta name="theme-color" content="#660099" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              vivo: {
                purple: "#660099",
                purpleDark: "#4d0073",
                purpleLight: "#f4ebff",
                accent: "#ff007f",
                grayLight: "#f6f6f9",
                grayBorder: "#e2e8f0",
                textDark: "#333333",
              },
            },
          },
        },
      };
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-vivo-grayLight text-vivo-textDark font-sans min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-vivo-grayBorder overflow-hidden">
        
        <!-- Topo com Identidade Vivo -->
        <div class="bg-gradient-to-r from-vivo-purple to-vivo-purpleDark p-8 text-white text-center relative">
            <div class="flex justify-center mb-3">
                <div class="bg-white text-vivo-purple p-3 rounded-2xl shadow-md">
                    <i data-lucide="shield" class="w-8 h-8 text-vivo-purple"></i>
                </div>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight">vivo</h1>
            <p class="text-purple-100 text-xs mt-1">Painel Operacional PWA</p>
        </div>

        <!-- Formulário -->
        <form method="POST" action="" class="p-6 space-y-4">

            <?php if (!empty($erro)): ?>
                <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span><?= htmlspecialchars($erro) ?></span>
                </div>
            <?php endif; ?>

            <div>
                <label for="usuario" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Usuário</label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" id="usuario" name="usuario" required placeholder="Digite seu usuário"
                           class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-vivo-purple focus:bg-white transition-all">
                </div>
            </div>

            <div>
                <label for="senha" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Senha</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-3 text-gray-400"></i>
                    <input type="password" id="senha" name="senha" required placeholder="••••••••"
                           class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-vivo-purple focus:bg-white transition-all">
                </div>
            </div>

            <button type="submit" class="w-full bg-vivo-purple hover:bg-vivo-purpleDark text-white py-2.5 rounded-xl font-bold text-sm shadow-md transition-all flex items-center justify-center space-x-2 mt-2">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Acessar Painel</span>
            </button>
        </form>

        <div class="px-6 py-4 bg-gray-50 border-t border-vivo-grayBorder text-center">
            <span class="text-xs text-gray-400">&copy; 2026 Vivo Operational Panel</span>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>