<?php
// Load environment helper
require_once __DIR__ . '/env.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

// -----------------------------------------------------------------------------
// Explicit Absolute Environment Loading
// -----------------------------------------------------------------------------
$env_path = dirname(__DIR__) . '/.env';
if (file_exists($env_path)) {
    if (function_exists('load_env')) {
        load_env($env_path);
    } elseif (function_exists('loadEnv')) {
        loadEnv($env_path);
    }
}

// -----------------------------------------------------------------------------
// Standard Cookie Session Bootstrap (Master Storage Container)
// -----------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// -----------------------------------------------------------------------------
// Per-Tab User Resolver
// -----------------------------------------------------------------------------
$active_tab_id = $_SERVER['HTTP_X_TAB_ID'] ?? $_POST['tab_id'] ?? $_GET['tab_id'] ?? null;

// Fallback: If only one tab session exists, default to it
if (empty($active_tab_id) && !empty($_SESSION['tabs'])) {
    if (count($_SESSION['tabs']) === 1) {
        $active_tab_id = array_key_first($_SESSION['tabs']);
    }
}

// Resolve authenticated user profile dedicated exclusively to THIS tab
if (empty($active_tab_id) && !empty($_SESSION['auth_user'])) {
    $auth_user = $_SESSION['auth_user'];
} else {
    $auth_user = (!empty($active_tab_id) && isset($_SESSION['tabs'][$active_tab_id]['auth_user']))
        ? $_SESSION['tabs'][$active_tab_id]['auth_user']
        : null;
}

// -----------------------------------------------------------------------------
// URL Routing Helper
// -----------------------------------------------------------------------------
if (!function_exists('auth_url')) {
    function auth_url(string $url): string {
        global $active_tab_id;

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '#')) {
            return $url;
        }

        $base = defined('BASE_URL') ? BASE_URL : '/PetSeeker/';
        if (!str_starts_with($url, '/') && !str_starts_with($url, $base)) {
            $url = $base . ltrim($url, '/');
        }

        if (!empty($active_tab_id)) {
            $url_parts = parse_url($url);
            $path = $url_parts['path'] ?? '';
            $query_params = [];

            if (!empty($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
            }

            $query_params['tab_id'] = $active_tab_id;
            $built_query = http_build_query($query_params);
            $fragment = !empty($url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

            return $path . ($built_query ? '?' . $built_query : '') . $fragment;
        }

        return $url;
    }
}

// -----------------------------------------------------------------------------
// Resilient Environment Variable Lookup
// -----------------------------------------------------------------------------
$getEnvVar = function (string $key, ?string $default = null): ?string {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (!empty($_ENV[$key])) {
        return $_ENV[$key];
    }
    if (!empty($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    return $default;
};

// -----------------------------------------------------------------------------
// Database Connection (PDO -> Supabase PostgreSQL)
// -----------------------------------------------------------------------------
$host     = $getEnvVar('DB_HOST');
$port     = $getEnvVar('DB_PORT', '6543');
$dbname   = $getEnvVar('DB_NAME', 'postgres');
$username = $getEnvVar('DB_USER');
$password = $getEnvVar('DB_PASS');

if (empty($host)) {
    die("Database configuration error: DB_HOST is missing or empty. Looking for .env at: " . htmlspecialchars($env_path));
}

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

// -----------------------------------------------------------------------------
// Notification System Helper
// -----------------------------------------------------------------------------
if (!function_exists('create_notification')) {
    /**
     * Inserts a persistent notification for a specific user.
     *
     * @param PDO $pdo Active PDO database connection
     * @param int $user_id Target recipient user_id
     * @param string $title Short summary title (e.g. 'New Match Proposal')
     * @param string $message Detailed body text
     * @param string|null $link_url Destination URL or view route
     * @return bool True if inserted successfully, false on error
     */
    function create_notification(PDO $pdo, int $user_id, string $title, string $message, ?string $link_url = null): bool {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO notification (user_id, title, message, link_url) 
                VALUES (?, ?, ?, ?)
            ');
            return $stmt->execute([$user_id, $title, $message, $link_url]);
        } catch (PDOException $e) {
            error_log('Notification insert error: ' . $e->getMessage());
            return false;
        }
    }
}