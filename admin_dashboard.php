<?php
require_once __DIR__ . '/includes/header.php';

// Only Administrators can access this page
require_role('Administrator');

/** @var array $auth_user */
/** @var PDO $pdo */

$message    = '';
$error      = '';
$active_tab = $_GET['tab'] ?? 'users';

// ==========================================
// 1. POST ACTION ROUTER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Delete User
    if ($action === 'delete_user') {
        $active_tab     = 'users';
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);

        if ($target_user_id === (int)$auth_user['user_id']) {
            $error = 'You cannot delete your own administrative account.';
        } elseif ($target_user_id > 0) {
            try {
                // Deleting here triggers handle_delete_auth_user() in PostgreSQL
                // which automatically deletes the account from Supabase auth.users as well
                $stmt = $pdo->prepare('DELETE FROM "user" WHERE user_id = ?');
                $stmt->execute([$target_user_id]);
                $message = "User #{$target_user_id} was successfully deleted.";
            } catch (PDOException $e) {
                // Foreign key constraint violations if the user owns active reports or logs
                $error = 'Cannot delete user: This account has active reports or linked system records.';
            }
        }
    }

    // Add New Category
    if ($action === 'add_category') {
        $active_tab = 'management';
        $species    = trim($_POST['species'] ?? '');
        $breed      = trim($_POST['breed'] ?? '');

        if (!empty($species) && !empty($breed)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO category (species, breed) VALUES (?, ?)');
                $stmt->execute([$species, $breed]);
                $message = "Category '{$species} - {$breed}' created successfully.";
            } catch (PDOException $e) {
                $error = 'Failed to add category: ' . $e->getMessage();
            }
        } else {
            $error = 'Both species and breed are required.';
        }
    }

    // Add New Location
    if ($action === 'add_location') {
        $active_tab    = 'management';
        $location_name = trim($_POST['location_name'] ?? '');

        if (!empty($location_name)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO location (location_name) VALUES (?)');
                $stmt->execute([$location_name]);
                $message = "Location '{$location_name}' added successfully.";
            } catch (PDOException $e) {
                $error = 'Failed to add location: ' . $e->getMessage();
            }
        } else {
            $error = 'Location name cannot be empty.';
        }
    }
}

// ==========================================
// 2. FETCH DIRECTORY & REFERENCE DATA
// ==========================================
$users_query = '
    SELECT u.user_id, u.name, u.email, u.phone_number, u.role_id, u.created_at,
           COALESCE(r.role_name, \'User\') AS role
    FROM "user" u
    LEFT JOIN role r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
';
$users      = $pdo->query($users_query)->fetchAll();
$categories = $pdo->query('SELECT category_id, species, breed FROM category ORDER BY species, breed')->fetchAll();
$locations  = $pdo->query('SELECT location_id, location_name FROM location ORDER BY location_name')->fetchAll();
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Admin Panel</h2>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'users' ? 'active' : '' ?>" href="<?= auth_url('admin_dashboard.php?tab=users') ?>">
      <i class="bi bi-people me-1"></i> User Directory <span class="badge bg-secondary ms-1"><?= count($users) ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'management' ? 'active' : '' ?>" href="<?= auth_url('admin_dashboard.php?tab=management') ?>">
      <i class="bi bi-sliders me-1"></i> System Settings
    </a>
  </li>
</ul>

<!-- Tab Dynamic Render -->
<div class="tab-content">
  <?php
    if ($active_tab === 'management') {
        include __DIR__ . '/views/admin/tab_management.php';
    } else {
        include __DIR__ . '/views/admin/tab_users.php';
    }
  ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>