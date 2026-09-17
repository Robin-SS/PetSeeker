<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/supabase_storage.php';

// Must be logged in to view personal reports
require_login();

/** @var array $auth_user */
/** @var PDO $pdo */

$user_id    = (int)$auth_user['user_id'];
$active_tab = $_GET['tab'] ?? 'lost';
$message    = '';
$error      = '';

// ==========================================
// 1. POST ACTION ROUTER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action      = $_POST['action'];
    $report_type = $_POST['report_type'] ?? '';
    $report_id   = (int)($_POST['report_id'] ?? 0);
    $pet_id      = (int)($_POST['pet_id'] ?? 0);

    // --- Action: Accept Incoming Match Proposal ---
    if ($action === 'accept_match') {
        $active_tab = 'history';
        $match_id   = (int)($_POST['match_id'] ?? 0);

        if ($match_id > 0) {
            try {
                $pdo->beginTransaction();

                $verify_stmt = $pdo->prepare('
                    SELECT m.match_id, m.lost_id, m.found_id, l.user_id AS lost_owner, f.user_id AS found_finder
                    FROM "match" m
                    JOIN lost_pet l ON m.lost_id = l.lost_id
                    JOIN found_pet f ON m.found_id = f.found_id
                    WHERE m.match_id = ? AND m.status = \'Pending\'
                      AND (
                          (l.user_id = ? AND m.proposed_by_user_id != ?) 
                          OR 
                          (f.user_id = ? AND m.proposed_by_user_id != ?)
                      )
                ');
                $verify_stmt->execute([$match_id, $user_id, $user_id, $user_id, $user_id]);
                $match = $verify_stmt->fetch();

                if ($match) {
                    $pdo->prepare('UPDATE "match" SET status = \'Confirmed\', resolved_at = NOW() WHERE match_id = ?')
                        ->execute([$match_id]);

                    $pdo->prepare("UPDATE lost_pet SET status = 'Resolved' WHERE lost_id = ?")->execute([$match['lost_id']]);
                    $pdo->prepare("UPDATE found_pet SET status = 'Resolved' WHERE found_id = ?")->execute([$match['found_id']]);

                    $dismiss = $pdo->prepare('
                        UPDATE "match" 
                        SET status = \'Dismissed\' 
                        WHERE match_id != ? 
                          AND (lost_id = ? OR found_id = ?) 
                          AND status = \'Pending\'
                    ');
                    $dismiss->execute([$match_id, $match['lost_id'], $match['found_id']]);

                    $pdo->commit();
                    $message = "Match confirmed! The listing has been cleared from active reports and archived in your History.";
                } else {
                    $pdo->rollBack();
                    $error = "Match proposal not found or you lack authorization to confirm it.";
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Failed to confirm match: " . $e->getMessage();
            }
        }
    }

    // --- Action: Decline Match Proposal ---
    if ($action === 'decline_match') {
        $active_tab = 'matches';
        $match_id   = (int)($_POST['match_id'] ?? 0);

        if ($match_id > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE "match" SET status = \'Dismissed\' WHERE match_id = ? AND status = \'Pending\'');
                $stmt->execute([$match_id]);
                $message = "Match proposal #{$match_id} declined.";
            } catch (PDOException $e) {
                $error = "Failed to decline match: " . $e->getMessage();
            }
        }
    }

    // --- Action: Manual Resolve Report ---
    if ($action === 'resolve_report' && in_array($report_type, ['lost', 'found'], true) && $report_id > 0) {
        $active_tab = ($report_type === 'found') ? 'found' : 'lost';
        $table      = ($report_type === 'lost') ? 'lost_pet' : 'found_pet';
        $pk         = ($report_type === 'lost') ? 'lost_id' : 'found_id';

        try {
            $stmt = $pdo->prepare("UPDATE {$table} SET status = 'Resolved' WHERE {$pk} = ? AND user_id = ?");
            $stmt->execute([$report_id, $user_id]);

            if ($stmt->rowCount() > 0) {
                $message = "Report #{$report_id} has been marked as Resolved and moved to History.";
            } else {
                $error = "Unable to update report. You might not have permission to modify this entry.";
            }
        } catch (PDOException $e) {
            $error = "Failed to update report status: " . $e->getMessage();
        }
    }

    // --- Action: Delete Personal Report (With Supabase Storage Sync) ---
    if ($action === 'delete_report' && in_array($report_type, ['lost', 'found'], true) && $report_id > 0) {
        $active_tab = ($report_type === 'found') ? 'found' : 'lost';
        $table      = ($report_type === 'lost') ? 'lost_pet' : 'found_pet';
        $pk         = ($report_type === 'lost') ? 'lost_id' : 'found_id';

        try {
            // 1. Fetch the pet photo URL before removing the record
            $fetch_stmt = $pdo->prepare("
                SELECT r.pet_id, p.photo 
                FROM {$table} r
                JOIN pet p ON r.pet_id = p.pet_id
                WHERE r.{$pk} = ? AND r.user_id = ?
            ");
            $fetch_stmt->execute([$report_id, $user_id]);
            $record = $fetch_stmt->fetch();

            if ($record) {
                $pdo->beginTransaction();

                // 2. Delete report record
                $del_stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$pk} = ? AND user_id = ?");
                $del_stmt->execute([$report_id, $user_id]);

                // 3. Delete linked pet record
                $del_pet = $pdo->prepare("DELETE FROM pet WHERE pet_id = ?");
                $del_pet->execute([$record['pet_id']]);

                $pdo->commit();

                // 4. Delete image from Supabase Storage if it exists
                if (!empty($record['photo']) && str_starts_with($record['photo'], 'http')) {
                    delete_pet_image($record['photo']);
                }

                $message = "Your " . ucfirst($report_type) . " report #{$report_id} and its associated image were removed.";
            } else {
                $error = "Report not found or you lack authorization to delete it.";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to delete report: " . $e->getMessage();
        }
    }
}

// ==========================================
// 2. FETCH ACTIVE REPORTS
// ==========================================
$lost_stmt = $pdo->prepare("
    SELECT l.lost_id, l.date_lost, l.status::text AS status, l.created_at,
           p.pet_id, p.name AS pet_name, p.color, p.photo,
           c.species, c.breed, loc.location_name
    FROM lost_pet l
    JOIN pet p ON l.pet_id = p.pet_id
    JOIN category c ON p.category_id = c.category_id
    JOIN location loc ON l.location_id = loc.location_id
    WHERE l.user_id = ? AND l.status != 'Resolved'
    ORDER BY l.created_at DESC
");
$lost_stmt->execute([$user_id]);
$my_lost_reports = $lost_stmt->fetchAll();

$found_stmt = $pdo->prepare("
    SELECT f.found_id, f.date_found, f.status::text AS status, f.created_at,
           p.pet_id, p.name AS pet_name, p.color, p.photo,
           c.species, c.breed, loc.location_name
    FROM found_pet f
    JOIN pet p ON f.pet_id = p.pet_id
    JOIN category c ON p.category_id = c.category_id
    JOIN location loc ON f.location_id = loc.location_id
    WHERE f.user_id = ? AND f.status != 'Resolved'
    ORDER BY f.created_at DESC
");
$found_stmt->execute([$user_id]);
$my_found_reports = $found_stmt->fetchAll();

// ==========================================
// 3. FETCH PENDING PROPOSALS
// ==========================================
$pending_stmt = $pdo->prepare('
    SELECT 
        m.match_id, m.confidence_score, m.created_at, m.proposed_by_user_id,
        proposer.name AS proposer_name,
        l.lost_id, l.user_id AS lost_owner_id,
        lp.name AS lost_name, lp.photo AS lost_photo, lp.color AS lost_color,
        lc.species AS lost_species, lc.breed AS lost_breed,
        f.found_id, f.user_id AS found_finder_id,
        fp.photo AS found_photo, fp.color AS found_color,
        fc.breed AS found_breed
    FROM "match" m
    JOIN lost_pet l ON m.lost_id = l.lost_id
    JOIN pet lp ON l.pet_id = lp.pet_id
    JOIN category lc ON lp.category_id = lc.category_id
    JOIN found_pet f ON m.found_id = f.found_id
    JOIN pet fp ON f.pet_id = fp.pet_id
    JOIN category fc ON fp.category_id = fc.category_id
    LEFT JOIN "user" proposer ON m.proposed_by_user_id = proposer.user_id
    WHERE (l.user_id = ? OR f.user_id = ?) AND m.status = \'Pending\'
    ORDER BY m.created_at DESC
');
$pending_stmt->execute([$user_id, $user_id]);
$pending_matches = $pending_stmt->fetchAll();

$pending_incoming_count = 0;
foreach ($pending_matches as $m) {
    if ((int)$m['proposed_by_user_id'] !== $user_id) {
        $pending_incoming_count++;
    }
}

// ==========================================
// 4. FETCH HISTORY (Confirmed Matches)
// ==========================================
$history_stmt = $pdo->prepare('
    SELECT 
        m.match_id, m.confidence_score,
        COALESCE(m.resolved_at, m.created_at) AS resolved_date,
        owner.user_id AS owner_id, owner.name AS owner_name,
        owner.phone_number AS owner_phone, owner.email AS owner_email,
        finder.user_id AS finder_id, finder.name AS finder_name,
        finder.phone_number AS finder_phone, finder.email AS finder_email,
        l.lost_id, lp.name AS lost_name, lp.photo AS lost_photo,
        lc.species, lc.breed AS lost_breed,
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
    WHERE (l.user_id = ? OR f.user_id = ?) AND m.status = \'Confirmed\'
    ORDER BY resolved_date DESC
');
$history_stmt->execute([$user_id, $user_id]);
$history_matches = $history_stmt->fetchAll();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">My Pet Reports</h2>
    <p class="text-muted mb-0">Track active listings, verify incoming matches, or view past reunited records.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= auth_url('report_lost.php') ?>" class="btn btn-outline-danger btn-sm">
      <i class="bi bi-plus-lg me-1"></i> Report Lost
    </a>
    <a href="<?= auth_url('report_found.php') ?>" class="btn btn-outline-success btn-sm">
      <i class="bi bi-plus-lg me-1"></i> Report Found
    </a>
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
<ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="myReportTabs" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'lost' ? 'active' : '' ?>" href="<?= auth_url('my_reports.php?tab=lost') ?>">
      <i class="bi bi-search me-1"></i> My Lost Pets <span class="badge bg-danger ms-1"><?= count($my_lost_reports) ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'found' ? 'active' : '' ?>" href="<?= auth_url('my_reports.php?tab=found') ?>">
      <i class="bi bi-geo-alt me-1"></i> My Found Reports <span class="badge bg-success ms-1"><?= count($my_found_reports) ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'matches' ? 'active' : '' ?>" href="<?= auth_url('my_reports.php?tab=matches') ?>">
      <i class="bi bi-link-45deg me-1"></i> Proposed Matches 
      <?php if ($pending_incoming_count > 0): ?>
        <span class="badge bg-warning text-dark ms-1"><?= $pending_incoming_count ?> new</span>
      <?php else: ?>
        <span class="badge bg-secondary ms-1"><?= count($pending_matches) ?></span>
      <?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab === 'history' ? 'active' : '' ?>" href="<?= auth_url('my_reports.php?tab=history') ?>">
      <i class="bi bi-clock-history me-1"></i> History <span class="badge bg-primary ms-1"><?= count($history_matches) ?></span>
    </a>
  </li>
</ul>

<!-- Tab Dynamic Render -->
<div class="tab-content" id="myReportTabsContent">
  <?php
    switch ($active_tab) {
        case 'found':
            include __DIR__ . '/views/my_reports/tab_found.php';
            break;
        case 'matches':
            include __DIR__ . '/views/my_reports/tab_matches.php';
            break;
        case 'history':
            include __DIR__ . '/views/my_reports/tab_history.php';
            break;
        case 'lost':
        default:
            include __DIR__ . '/views/my_reports/tab_lost.php';
            break;
    }
  ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>