<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/supabase_storage.php';

// Only Staff and Admins can access this page
require_role(['Staff', 'Administrator']);

/** @var array $auth_user */
/** @var PDO $pdo */

$message    = '';
$error      = '';
$active_tab = $_GET['tab'] ?? 'reports';

// ==========================================
// 1. POST ACTION ROUTER (Post Moderation Only)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'takedown_post') {
        $active_tab  = 'reports';
        $report_type = $_POST['report_type'] ?? '';
        $report_id   = (int)($_POST['report_id'] ?? 0);
        $pet_id      = (int)($_POST['pet_id'] ?? 0);

        if (in_array($report_type, ['lost', 'found'], true) && $report_id > 0) {
            try {
                $table = ($report_type === 'lost') ? 'lost_pet' : 'found_pet';
                $pk    = ($report_type === 'lost') ? 'lost_id' : 'found_id';

                // Guard: Verify report is not already resolved/reunited
                $chk_stmt = $pdo->prepare("SELECT status::text AS status FROM {$table} WHERE {$pk} = ?");
                $chk_stmt->execute([$report_id]);
                $curr = $chk_stmt->fetch();

                if ($curr && $curr['status'] === 'Resolved') {
                    $error = 'This report has already been resolved and is preserved in user reunion history. It cannot be deleted.';
                } else {
                    // Fetch photo URL before removing records
                    $photo_to_delete = null;
                    if ($pet_id > 0) {
                        $p_stmt = $pdo->prepare('SELECT photo FROM pet WHERE pet_id = ?');
                        $p_stmt->execute([$pet_id]);
                        $photo_to_delete = $p_stmt->fetchColumn();
                    }

                    $pdo->beginTransaction();

                    $del_stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$pk} = ?");
                    $del_stmt->execute([$report_id]);

                    if ($pet_id > 0) {
                        $del_pet = $pdo->prepare('DELETE FROM pet WHERE pet_id = ?');
                        $del_pet->execute([$pet_id]);
                    }

                    $pdo->commit();

                    // Remove remote image from Supabase Storage
                    if (!empty($photo_to_delete) && str_starts_with($photo_to_delete, 'http')) {
                        delete_pet_image($photo_to_delete);
                    }

                    $message = ucfirst($report_type) . " report #{$report_id} and its associated files were permanently deleted.";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to delete report: ' . $e->getMessage();
            }
        }
    }
}

// ==========================================
// 2. FETCH ACTIVE REPORTS (Tab 1)
// ==========================================
$active_reports_query = "
    SELECT 
        'lost' AS report_type, l.lost_id AS report_id, p.pet_id, p.name AS pet_name,
        c.species, c.breed, p.color, p.photo, loc.location_name,
        l.date_lost AS event_date, l.status::text AS status, l.created_at, u.name AS reporter_name
    FROM lost_pet l
    JOIN pet p ON l.pet_id = p.pet_id
    JOIN category c ON p.category_id = c.category_id
    JOIN location loc ON l.location_id = loc.location_id
    JOIN \"user\" u ON l.user_id = u.user_id
    WHERE l.status != 'Resolved'

    UNION ALL

    SELECT 
        'found' AS report_type, f.found_id AS report_id, p.pet_id, p.name AS pet_name,
        c.species, c.breed, p.color, p.photo, loc.location_name,
        f.date_found AS event_date, f.status::text AS status, f.created_at, u.name AS reporter_name
    FROM found_pet f
    JOIN pet p ON f.pet_id = p.pet_id
    JOIN category c ON p.category_id = c.category_id
    JOIN location loc ON f.location_id = loc.location_id
    JOIN \"user\" u ON f.user_id = u.user_id
    WHERE f.status != 'Resolved'

    ORDER BY created_at DESC
";
$active_reports = $pdo->query($active_reports_query)->fetchAll();

// ==========================================
// 3. FETCH RESOLVED MATCHES (Tab 2)
// ==========================================
$resolved_matches_query = '
    SELECT 
        m.match_id, m.confidence_score, COALESCE(m.resolved_at, m.created_at) AS resolved_date,
        owner.name AS owner_name, owner.phone_number AS owner_phone, owner.email AS owner_email,
        finder.name AS finder_name, finder.phone_number AS finder_phone, finder.email AS finder_email,
        l.lost_id, lp.name AS lost_name, lp.photo AS lost_photo, lc.species, lc.breed AS lost_breed, lp.color AS lost_color,
        f.found_id, fp.photo AS found_photo, fc.breed AS found_breed, fp.color AS found_color
    FROM "match" m
    JOIN lost_pet l ON m.lost_id = l.lost_id
    JOIN pet lp ON l.pet_id = lp.pet_id
    JOIN category lc ON lp.category_id = lc.category_id
    JOIN "user" owner ON l.user_id = owner.user_id
    JOIN found_pet f ON m.found_id = f.found_id
    JOIN pet fp ON f.pet_id = fp.pet_id
    JOIN category fc ON fp.category_id = fc.category_id
    JOIN "user" finder ON f.user_id = finder.user_id
    WHERE m.status = \'Confirmed\'
    ORDER BY resolved_date DESC
';

try {
    $resolved_matches = $pdo->query($resolved_matches_query)->fetchAll();
} catch (PDOException $e) {
    $resolved_matches = [];
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Staff Desk</h2>
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

<!-- Tabs -->
<ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="staffDeskTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'reports' ? 'active' : '' ?>" href="<?= auth_url('staff_dashboard.php?tab=reports') ?>">
      <i class="bi bi-collection me-1"></i> Active Community Reports <span class="badge bg-secondary ms-1"><?= count($active_reports) ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'resolved' ? 'active' : '' ?>" href="<?= auth_url('staff_dashboard.php?tab=resolved') ?>">
      <i class="bi bi-check2-circle me-1"></i> Resolved Matches <span class="badge bg-success ms-1"><?= count($resolved_matches) ?></span>
    </a>
  </li>
</ul>

<!-- Tab Dynamic Render -->
<div class="tab-content" id="staffDeskTabsContent">
  <?php
    if ($active_tab === 'resolved') {
        include __DIR__ . '/views/staff/tab_resolved.php';
    } else {
        include __DIR__ . '/views/staff/tab_active_reports.php';
    }
  ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>