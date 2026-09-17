<?php
require_once __DIR__ . '/config/db.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

// 1. Unset all session superglobals
$_SESSION = [];

// 2. Invalidate the session cookie on the client's browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect to login page
header('Location: ' . BASE_URL . 'login.php');
exit;