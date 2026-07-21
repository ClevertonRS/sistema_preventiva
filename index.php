<?php
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/':
    case '/login':
        require __DIR__ . '/login.php';
        break;

    case '/dashboard':
        require __DIR__ . '/dashboard.php';
        break;

    case '/detalhe-preventiva':
        require __DIR__ . '/detalhe-preventiva.php';
        break;

    case '/salvar-preventiva':
        require __DIR__ . '/salvar-preventiva.php';
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