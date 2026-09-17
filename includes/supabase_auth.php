<?php
require_once dirname(__DIR__) . '/config/env.php';
require_once __DIR__ . '/supabase_storage.php';

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', rtrim(getenv('SUPABASE_URL') ?: '', '/'));
}
if (!defined('SUPABASE_SERVICE_KEY')) {
    define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: (getenv('SUPABASE_SERVICE_KEY') ?: ''));
}

function supabase_auth_signup(string $email, string $password, array $metadata = []): array {
    $endpoint = rtrim(SUPABASE_URL, '/') . '/auth/v1/signup';

    $payload = [
        'email'    => $email,
        'password' => $password,
        'data'     => $metadata
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
        CURLOPT_TIMEOUT        => 15
    ]);

    $response  = curl_exec($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'error' => 'Connection error: ' . $curl_err];
    }

    $data = json_decode($response, true) ?? [];

    if ($status >= 200 && $status < 300) {
        return ['success' => true, 'data' => $data];
    }

    $errMsg = $data['msg'] 
           ?? $data['message'] 
           ?? $data['error_description'] 
           ?? (is_string($data['error'] ?? null) ? $data['error'] : null)
           ?? 'Registration failed. Please try again.';

    return ['success' => false, 'error' => $errMsg];
}

function supabase_auth_signin(string $email, string $password): array {
    $endpoint = rtrim(SUPABASE_URL, '/') . '/auth/v1/token?grant_type=password';

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
        CURLOPT_TIMEOUT        => 15
    ]);

    $response  = curl_exec($ch);
    $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'error' => 'Connection error: ' . $curl_err];
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