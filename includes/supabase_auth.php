<?php
require_once dirname(__DIR__) . '/config/env.php';
require_once __DIR__ . '/supabase_storage.php';

// Safe environment variable getter across $_ENV, $_SERVER, and getenv()
$get_auth_env = function (string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (!empty($_ENV[$key])) return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    return $default;
};

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', rtrim($get_auth_env('SUPABASE_URL'), '/'));
}
if (!defined('SUPABASE_SERVICE_KEY')) {
    $key = $get_auth_env('SUPABASE_SERVICE_ROLE_KEY') 
        ?: $get_auth_env('SUPABASE_KEY') 
        ?: $get_auth_env('SUPABASE_SERVICE_KEY');
    define('SUPABASE_SERVICE_KEY', $key);
}

function supabase_auth_signup(string $email, string $password, array $metadata = []): array {
    if (empty(SUPABASE_URL) || empty(SUPABASE_SERVICE_KEY)) {
        return ['success' => false, 'error' => 'Supabase configuration is missing (URL or Service Key).'];
    }

    $endpoint = SUPABASE_URL . '/auth/v1/signup';

    // Build the dynamic return URL for email confirmation
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = defined('BASE_URL') ? BASE_URL : '/';
    $redirect_url = $protocol . $host . $base . 'login.php';

    $payload = [
        'email'             => $email,
        'password'          => $password,
        'data'              => $metadata,
        'email_redirect_to' => $redirect_url
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response  = curl_exec($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'error' => 'Supabase connection error: ' . $curl_err];
    }

    $data = json_decode($response, true) ?? [];

    if ($status >= 200 && $status < 300) {
        return ['success' => true, 'data' => $data];
    }

    $errMsg = $data['msg'] 
           ?? $data['message'] 
           ?? $data['error_description'] 
           ?? (is_string($data['error'] ?? null) ? $data['error'] : null)
           ?? ('Registration failed (HTTP ' . $status . ').');

    return ['success' => false, 'error' => $errMsg];
}

function supabase_auth_signin(string $email, string $password): array {
    if (empty(SUPABASE_URL) || empty(SUPABASE_SERVICE_KEY)) {
        return ['success' => false, 'error' => 'Supabase configuration is missing (URL or Service Key).'];
    }

    $endpoint = SUPABASE_URL . '/auth/v1/token?grant_type=password';

    $payload = [
        'email'    => $email,
        'password' => $password
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . SUPABASE_SERVICE_KEY,
            'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response  = curl_exec($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'error' => 'Supabase connection error: ' . $curl_err];
    }

    $data = json_decode($response, true) ?? [];

    if ($status >= 200 && $status < 300) {
        return ['success' => true, 'data' => $data];
    }

    $errMsg = $data['error_description'] 
           ?? $data['msg'] 
           ?? $data['message'] 
           ?? (is_string($data['error'] ?? null) ? $data['error'] : null)
           ?? 'Invalid email or password.';

    return ['success' => false, 'error' => $errMsg];
}