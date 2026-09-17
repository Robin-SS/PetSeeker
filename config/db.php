<?php
// Load environment variables from .env
require_once __DIR__ . '/env.php';

if (session_status() === PHP_SESSION_NONE) {
    // Prevent JavaScript from accessing session cookies (XSS mitigation)
    ini_set('session.cookie_httponly', '1');

    // Prevent passing session IDs via URLs (mitigates session fixation & leakage)
    ini_set('session.use_only_cookies', '1');

    // Mitigate cross-site request forgery attacks
    ini_set('session.cookie_samesite', 'Lax');

    // Enable secure cookies if accessing over HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// Supabase Credentials pulled from .env
$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '6543';
$dbname   = getenv('DB_NAME') ?: 'postgres';
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}