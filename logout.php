<?php
// config/db.php initializes the session for this tab's specific incoming sid
require_once __DIR__ . '/config/db.php';

// 1. Clear session variables for this tab
$_SESSION = [];

// 2. Destroy session storage on the server
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 3. Clear global auth identifier so templates or redirects don't leak it
$auth_sid = null;

// 4. Redirect cleanly to login WITHOUT passing any ?sid= parameter
if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

header('Location: ' . BASE_URL . 'login.php');
exit;