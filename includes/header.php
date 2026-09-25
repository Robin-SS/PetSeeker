<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/supabase_storage.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/PetSeeker/');
}

// Current file helper to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);

// Helpers
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header('Location: ' . auth_url($url));
        exit;
    }
}

function require_login(): void {
    global $auth_user;
    if (!$auth_user) {
        header('Location: ' . auth_url('login.php'));
        exit;
    }
}

function require_role($allowed_roles = []): void {
    require_login();
    global $auth_user;
    $roles = (array)$allowed_roles;
    if (!in_array($auth_user['role'] ?? '', $roles, true)) {
        header('HTTP/1.1 403 Forbidden');
        echo '<div class="text-center py-5">';
        echo '<h2 class="fw-bold text-danger">403 - Access Denied</h2>';
        echo '<p class="text-muted">You do not have permission to view this page.</p>';
        echo '<a href="' . auth_url('index.php') . '" class="btn btn-outline-primary">Return to Home</a>';
        echo '</div>';
        exit;
    }
}

// Notifications: Count unread notifications using the is_read column
$unread_count = 0;
if (!empty($auth_user['user_id'])) {
    try {
        $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = FALSE");
        $notif_stmt->execute([(int)$auth_user['user_id']]);
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

    <!-- Tab-Isolated Session Bridge -->
    <script>
      (function() {
        // 1. Maintain isolated per-tab identity in sessionStorage
        let tabId = sessionStorage.getItem('petseeker_tab_id');
        if (!tabId) {
          tabId = 'tab_' + Math.random().toString(36).substring(2, 9) + Date.now().toString(36);
          sessionStorage.setItem('petseeker_tab_id', tabId);
        }

        // 2. Attach tab_id parameter if missing from current URL
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('tab_id')) {
          urlParams.set('tab_id', tabId);
          window.location.replace(window.location.pathname + '?' + urlParams.toString() + window.location.hash);
          return;
        }

        // 3. Keep tab_id traveling with link clicks and form submits inside this tab
        document.addEventListener('DOMContentLoaded', () => {
          document.querySelectorAll('a[href]').forEach(a => {
            const href = a.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !href.startsWith('http')) {
              try {
                const u = new URL(a.href, window.location.origin);
                u.searchParams.set('tab_id', tabId);
                a.href = u.pathname + u.search + u.hash;
              } catch(e) {}
            }
          });

          document.querySelectorAll('form').forEach(form => {
            if (!form.querySelector('input[name="tab_id"]')) {
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'tab_id';
              input.value = tabId;
              form.appendChild(input);
            }
          });
        });
      })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
  <!-- Mobile Overlay Backdrop -->
  <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

  <!-- Collapsible App Sidebar -->
  <aside id="appSidebar" class="app-sidebar">
    <a href="<?= auth_url('index.php') ?>" class="sidebar-brand">
      <i class="bi bi-search-heart me-2"></i> PETSeeker
    </a>

    <nav class="sidebar-nav">
      <div class="nav-header">Discover</div>
      <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= auth_url('index.php') ?>">
        <i class="bi bi-grid-1x2"></i> Home Feed
      </a>

      <?php if ($auth_user): ?>
        <div class="nav-header">Reports</div>
        <a class="nav-link <?= $current_page === 'report_lost.php' ? 'active' : '' ?>" href="<?= auth_url('report_lost.php') ?>">
          <i class="bi bi-exclamation-octagon text-danger"></i> Report Lost Pet
        </a>
        <a class="nav-link <?= $current_page === 'report_found.php' ? 'active' : '' ?>" href="<?= auth_url('report_found.php') ?>">
          <i class="bi bi-check-circle text-success"></i> Report Found Pet
        </a>
        <a class="nav-link <?= $current_page === 'my_reports.php' ? 'active' : '' ?>" href="<?= auth_url('my_reports.php') ?>">
          <i class="bi bi-folder2-open"></i> My Reports
        </a>

        <?php if (in_array($auth_user['role'], ['Staff', 'Administrator'], true)): ?>
          <div class="nav-header">Staff Workspace</div>
          <a class="nav-link <?= $current_page === 'staff_dashboard.php' ? 'active' : '' ?>" href="<?= auth_url('staff_dashboard.php') ?>">
            <i class="bi bi-shield-check text-warning"></i> Monitor Postings
          </a>
        <?php endif; ?>

        <?php if ($auth_user['role'] === 'Administrator'): ?>
          <div class="nav-header">Administration</div>
          <a class="nav-link <?= $current_page === 'admin_dashboard.php' ? 'active' : '' ?>" href="<?= auth_url('admin_dashboard.php') ?>">
            <i class="bi bi-sliders text-info"></i> Admin Panel
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </aside>

  <!-- Main Content Shell -->
  <div class="app-main">
    <!-- Top Bar -->
    <header class="app-topbar">
      <div class="d-flex align-items-center gap-2">
        <button id="sidebarToggle" class="sidebar-toggle-btn" type="button" aria-label="Toggle Navigation">
          <i class="bi bi-list fs-5"></i>
        </button>
      </div>

      <div class="d-flex align-items-center gap-3">
        <?php if ($auth_user): ?>
          <!-- Offcanvas Notification Trigger Button -->
          <button class="btn btn-link text-secondary position-relative p-1 border-0 shadow-none text-decoration-none" 
                  type="button" 
                  data-bs-toggle="offcanvas" 
                  data-bs-target="#notificationDrawer" 
                  aria-controls="notificationDrawer" 
                  id="notifBellBtn"
                  title="Notifications">
            <i class="bi bi-bell-fill fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $unread_count > 0 ? '' : 'd-none' ?>" 
                  id="notifBadge" 
                  style="font-size: 0.65rem;">
              <?= $unread_count > 99 ? '99+' : $unread_count ?>
            </span>
          </button>

          <div class="dropdown">
            <a class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <span class="fw-semibold small"><?= htmlspecialchars($auth_user['user_name']) ?></span>
              <span class="badge bg-secondary"><?= htmlspecialchars($auth_user['role']) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
              <li>
                <a class="dropdown-item text-danger small" href="<?= auth_url('logout.php') ?>">
                  <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
              </li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?= auth_url('login.php') ?>">Login</a>
          <a class="btn btn-sm btn-primary" href="<?= auth_url('register.php') ?>">Register</a>
        <?php endif; ?>
      </div>
    </header>

    <!-- Content Body -->
    <main class="container-fluid px-4 py-4 flex-grow-1">