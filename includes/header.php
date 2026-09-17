<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/supabase_storage.php';

// 1. Resolve Session Directly from Secure Cookie Session
$auth_user = $_SESSION['auth_user'] ?? null;

// Helper to generate consistent absolute URLs (clean paths, no sid leakage)
function auth_url(string $path): string {
    return BASE_URL . ltrim($path, '/');
}

// Global redirect helper
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }
}

// 2. Authentication and Role Guards
function require_login(): void {
    global $auth_user;
    if (!$auth_user) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function require_role($allowed_roles = []): void {
    require_login();
    global $auth_user;
    $roles = (array)$allowed_roles;
    if (!in_array($auth_user['role'] ?? '', $roles, true)) {
        header('HTTP/1.1 403 Forbidden');
        echo '<div style="font-family:sans-serif; text-align:center; padding:50px;">';
        echo '<h2>403 - Access Denied</h2>';
        echo '<p>You do not have permission to view this page.</p>';
        echo '<a href="' . auth_url('index.php') . '">Return to Home</a>';
        echo '</div>';
        exit;
    }
}

// 3. Notification Count
$unread_count = 0;
if (!empty($auth_user['user_id'])) {
    try {
        $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND status = 'unread'");
        $notif_stmt->execute([$auth_user['user_id']]);
        $unread_count = (int)$notif_stmt->fetchColumn();
    } catch (PDOException $e) {
        $unread_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETSeeker - Lost & Found Pets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= auth_url('index.php') ?>">
      <i class="bi bi-search-heart me-1"></i> PETSeeker
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= auth_url('index.php') ?>">Home</a>
        </li>

        <?php if ($auth_user): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= auth_url('report_lost.php') ?>">Report Lost Pet</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= auth_url('report_found.php') ?>">Report Found Pet</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= auth_url('my_reports.php') ?>">My Reports</a>
          </li>

          <!-- Staff Links -->
          <?php if (in_array($auth_user['role'], ['Staff', 'Administrator'], true)): ?>
            <li class="nav-item">
              <a class="nav-link text-warning fw-semibold" href="<?= auth_url('staff_dashboard.php') ?>">
                Monitor Posting
              </a>
            </li>
          <?php endif; ?>

          <!-- Admin Links -->
          <?php if ($auth_user['role'] === 'Administrator'): ?>
            <li class="nav-item">
              <a class="nav-link text-info fw-semibold" href="<?= auth_url('admin_dashboard.php') ?>">
                Admin Panel
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <?php if ($auth_user): ?>
          <li class="nav-item me-3">
            <a class="nav-link position-relative" href="<?= auth_url('notifications.php') ?>" title="Notifications">
              <i class="bi bi-bell-fill"></i>
              <?php if ($unread_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  <?= $unread_count ?>
                </span>
              <?php endif; ?>
            </a>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($auth_user['user_name']) ?>
              <span class="badge bg-secondary ms-1"><?= htmlspecialchars($auth_user['role']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item text-danger" href="<?= auth_url('logout.php') ?>"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-outline-light ms-lg-2" href="<?= BASE_URL ?>register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-4 flex-grow-1">