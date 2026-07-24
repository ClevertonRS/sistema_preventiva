<?php
// Carrega variáveis de .env sem depender de Composer
function loadEnv(string $path = __DIR__ . '/../.env'): void {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            $key = $m[1];
            $val = $m[2];
            // Remove aspas se houver
            if (preg_match('/^"(.*)"$/', $val, $m2) || preg_match("/^'(.*)'$/", $val, $m2)) {
                $val = $m2[1];
            }
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}

loadEnv();