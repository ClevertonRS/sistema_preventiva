<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera/retorna token CSRF para a sessão atual
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF (usa hash_equals para timing-safe)
 */
function csrf_validate(?string $token): bool {
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting simples baseado em sessão + IP
 * @param string $action identificador da ação (ex: 'login', 'upload')
 * @param int $maxAttempts máximo de tentativas
 * @param int $windowSeconds janela de tempo em segundos
 */
function rate_limit(string $action, int $maxAttempts, int $windowSeconds): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "ratelimit_{$action}_{$ip}";
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    // Remove tentativas fora da janela
    $_SESSION[$key] = array_filter($_SESSION[$key], fn($ts) => $now - $ts < $windowSeconds);

    if (count($_SESSION[$key]) >= $maxAttempts) {
        return false;
    }

    $_SESSION[$key][] = $now;
    return true;
}

/**
 * Valida e sanitiza upload de arquivo
 * @param array $file $_FILES['campo']
 * @param array $allowedMimes MIME types permitidos
 * @param int $maxSizeBytes tamanho máximo (default 5MB)
 * @return array ['ok'=>bool, 'error'=>string|null, 'tmp_name'=>string|null]
 */
function validate_upload(array $file, array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'], int $maxSizeBytes = 5_242_880): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Erro no upload (código ' . $file['error'] . ')', 'tmp_name' => null];
    }
    if ($file['size'] > $maxSizeBytes) {
        return ['ok' => false, 'error' => 'Arquivo muito grande (máx. ' . ($maxSizeBytes / 1024 / 1024) . 'MB)', 'tmp_name' => null];
    }

    // Verifica MIME real com finfo (não confia em $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'Tipo de arquivo não permitido', 'tmp_name' => null];
    }

    // Extensão segura baseada no MIME real
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext = $extMap[$mime] ?? 'jpg';

    return ['ok' => true, 'error' => null, 'tmp_name' => $file['tmp_name'], 'ext' => $ext, 'mime' => $mime];
}

/**
 * Gera nome de arquivo seguro
 */
function safe_filename(string $prefix, string $ext): string {
    return $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
}

/**
 * Redirecionamento seguro (evita open redirect)
 * @param string $url URL relativa (deve começar com /)
 * @param array $allowedPaths caminhos permitidos (ex: ['/dashboard', '/preventivas'])
 */
function safe_redirect(string $url, array $allowedPaths = []): void {
    if (strpos($url, '/') !== 0) {
        $url = '/dashboard';
    }
    if ($allowedPaths && !in_array($url, $allowedPaths, true)) {
        $url = '/dashboard';
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Configuração segura de sessão (chamar antes de session_start)
 */
function secure_session_config(): void {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '7200'); // 2h
    session_set_cookie_params([
        'lifetime' => 7200,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Regenera ID da sessão (previne fixation)
 */
function regenerate_session(): void {
    session_regenerate_id(true);
}

/**
 * Log seguro (não vaza dados sensíveis)
 */
function security_log(string $message, array $context = []): void {
    $ctx = [];
    foreach ($context as $k => $v) {
        // Mascara campos sensíveis
        if (in_array($k, ['senha', 'password', 'token', 'csrf'], true)) {
            $ctx[$k] = '***';
        } else {
            $ctx[$k] = $v;
        }
    }
    error_log('[SECURITY] ' . $message . ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE));
}