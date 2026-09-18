<?php
// Load environment variables from .env
require_once __DIR__ . '/env.php';

// Base URL definition
if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

// -----------------------------------------------------------------------------
// Session Bootstrap (Pure Per-Tab Scoping - Zero Cookie Overwrite)
// -----------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // 1. Disable session cookies completely to stop cross-tab cookie collision
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies', '0');
    ini_set('session.use_trans_sid', '0');

    // 2. Intercept sid parameter passed via GET or POST
    $incoming_sid = $_GET['sid'] ?? $_POST['sid'] ?? null;

    if (!empty($incoming_sid) && preg_match('/^[a-zA-Z0-9,-]{16,64}$/', $incoming_sid)) {
        session_id($incoming_sid);
    }

    session_start();
}

// Global active session ID available to all controllers and views
$auth_sid = session_id();

// -----------------------------------------------------------------------------
// Robust URL Routing Helper Function
// -----------------------------------------------------------------------------
if (!function_exists('auth_url')) {
    /**
     * Appends or updates the active session ID in internal paths.
     */
    function auth_url(string $url): string {
        global $auth_sid;

        if (empty($auth_sid)) {
            $auth_sid = session_id();
        }

        // Avoid touching external links or anchor jumps
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '#')) {
            return $url;
        }

        // Prepend BASE_URL if relative path without leading slash
        $base = defined('BASE_URL') ? BASE_URL : '/PetSeeker/';
        if (!str_starts_with($url, '/') && !str_starts_with($url, $base)) {
            $url = $base . ltrim($url, '/');
        }

        // Parse URL components to cleanly set or overwrite 'sid'
        $url_parts = parse_url($url);
        $path = $url_parts['path'] ?? '';
        $query_params = [];

        if (!empty($url_parts['query'])) {
            parse_str($url_parts['query'], $query_params);
        }

        // Bind active session
        $query_params['sid'] = $auth_sid;

        $built_query = http_build_query($query_params);
        $fragment = !empty($url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

        return $path . ($built_query ? '?' . $built_query : '') . $fragment;
    }
}

// -----------------------------------------------------------------------------
// Database Connection (PDO -> Supabase PostgreSQL)
// -----------------------------------------------------------------------------
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