<?php
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/preventiva/([0-9]+)$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    require __DIR__ . '/detalhe_preventiva.php';
    return;
}

if (preg_match('#^/execucao-detalhe/([0-9]+)$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    require __DIR__ . '/execucao_detalhe.php';
    return;
}

switch ($uri) {
    case '/':
    case '/login':
        require __DIR__ . '/login.php';
        break;

    case '/dashboard':
        require __DIR__ . '/dashboard.php';
        break;

    case '/preventivas':
        require __DIR__ . '/preventivas.php';
        break;

    case '/preventiva':
        require __DIR__ . '/detalhe_preventiva.php';
        break;

    case '/triagem':
        require __DIR__ . '/triagem.php';
        break;

    case '/execucao':
        require __DIR__ . '/execucao.php';
        break;

    case '/execucao-detalhe':
        require __DIR__ . '/execucao_detalhe.php';
        break;

    case '/salvar-preventiva':
        require __DIR__ . '/salvar_preventiva.php';
        break;

    case '/logout':
        session_destroy();
        header('Location: /login');
        exit;

    default:
        http_response_code(404);
        echo "<h1 style='font-family: sans-serif; text-align: center; margin-top: 50px;'>404 - Página não encontrada</h1>";
        break;
}