<?php
require_once 'includes/header.php';
require_once 'includes/supabase_storage.php';

// Handle Staff / Administrator Report Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_report') {
    $del_type   = $_POST['report_type'] ?? '';
    $del_id     = (int)($_POST['report_id'] ?? 0);
    $del_pet_id = (int)($_POST['pet_id'] ?? 0);

    $allowed_roles = ['Staff', 'Administrator'];
    $current_role  = $auth_user['role'] ?? '';

    if (in_array($current_role, $allowed_roles, true) && $del_id > 0) {
        try {
            // 1. Fetch the photo URL before database deletion
            $photo_to_delete = null;
            if ($del_pet_id > 0) {
                $p_stmt = $pdo->prepare('SELECT photo FROM pet WHERE pet_id = ?');
                $p_stmt->execute([$del_pet_id]);
                $photo_to_delete = $p_stmt->fetchColumn();
            }

            $pdo->beginTransaction();

            if ($del_type === 'lost') {
                $del_stmt = $pdo->prepare('DELETE FROM lost_pet WHERE lost_id = ?');
                $del_stmt->execute([$del_id]);
            } else {
                $del_stmt = $pdo->prepare('DELETE FROM found_pet WHERE found_id = ?');
                $del_stmt->execute([$del_id]);
            }

            if ($del_pet_id > 0) {
                $del_pet = $pdo->prepare('DELETE FROM pet WHERE pet_id = ?');
                $del_pet->execute([$del_pet_id]);
            }

            $pdo->commit();

            // 2. Remove remote image from Supabase Storage
            if (!empty($photo_to_delete) && str_starts_with($photo_to_delete, 'http')) {
                delete_pet_image($photo_to_delete);
            }

            header('Location: ' . auth_url('index.php?msg=report_deleted'));
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $delete_error = 'Failed to delete report: ' . $e->getMessage();
        }
    }
}

$type = strtolower($_GET['type'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['lost', 'found']) || $id <= 0) {
    header('Location: ' . auth_url('index.php'));
    exit;
}

if ($type === 'lost') {
    $stmt = $pdo->prepare('
        SELECT l.*, p.*, c.species, c.breed, loc.location_name, 
               u.name AS reporter_name, u.email AS reporter_email, u.phone_number AS reporter_phone
        FROM lost_pet l
        JOIN pet p ON l.pet_id = p.pet_id
        JOIN category c ON p.category_id = c.category_id
        JOIN location loc ON l.location_id = loc.location_id
        JOIN "user" u ON l.user_id = u.user_id
        WHERE l.lost_id = ?
    ');
} else {
    $stmt = $pdo->prepare('
        SELECT f.*, p.*, c.species, c.breed, loc.location_name, 
               u.name AS reporter_name, u.email AS reporter_email, u.phone_number AS reporter_phone
        FROM found_pet f
        JOIN pet p ON f.pet_id = p.pet_id
        JOIN category c ON p.category_id = c.category_id
        JOIN location loc ON f.location_id = loc.location_id
        JOIN "user" u ON f.user_id = u.user_id
        WHERE f.found_id = ?
    ');
}

$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    echo '<div class="alert alert-danger">Report not found. <a href="' . auth_url('index.php') . '">Back to home</a></div>';
    require_once 'includes/footer.php';
    exit;
}

$img_src = get_pet_photo_url($report['photo'] ?? null);
?>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <a href="<?= auth_url('index.php') ?>" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Feed</a>
    
    <?php if (!empty($delete_error)): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($delete_error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
      <div class="row g-0">
        <div class="col-md-5 bg-light d-flex align-items-center justify-content-center border-end">
          <?php if ($img_src): ?>
            <img src="<?= htmlspecialchars($img_src) ?>" class="img-fluid rounded-start w-100 h-100 object-fit-cover report-detail-img" alt="Pet Image">
          <?php else: ?>
            <div class="p-5 text-muted text-center">
              <i class="bi bi-camera fs-1"></i>
              <p class="mt-2 mb-0">No image provided</p>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-md-7">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge <?= $type === 'lost' ? 'bg-danger' : 'bg-success' ?> fs-6">
                <?= ucfirst($type) ?> Pet Report
              </span>
              <span class="badge bg-secondary"><?= htmlspecialchars($report['status']) ?></span>
            </div>

            <h2 class="card-title fw-bold mb-3">
              <?= $report['name'] ? htmlspecialchars($report['name']) : 'Unnamed / Stray' ?>
            </h2>

            <ul class="list-group list-group-flush mb-4">
              <li class="list-group-item px-0"><strong>Species & Breed:</strong> <?= htmlspecialchars($report['species'] . ' (' . $report['breed'] . ')') ?></li>
              <li class="list-group-item px-0"><strong>Color / Pattern:</strong> <?= htmlspecialchars($report['color']) ?></li>
              <?php if (!empty($report['age'])): ?>
                <li class="list-group-item px-0"><strong>Age:</strong> <?= htmlspecialchars($report['age']) ?> year(s) old</li>
              <?php endif; ?>
              <li class="list-group-item px-0"><strong>Distinctive Marks:</strong> <?= htmlspecialchars($report['distinctive_marks'] ?? 'None specified') ?></li>
              <li class="list-group-item px-0"><strong>Location:</strong> <?= htmlspecialchars($report['location_name']) ?></li>
              <li class="list-group-item px-0"><strong>Date <?= $type === 'lost' ? 'Lost' : 'Found' ?>:</strong> <?= htmlspecialchars($type === 'lost' ? $report['date_lost'] : $report['date_found']) ?></li>
              <li class="list-group-item px-0"><strong>Reported By:</strong> <?= htmlspecialchars($report['reporter_name']) ?> (<?= htmlspecialchars($report['reporter_phone'] ?? $report['reporter_email']) ?>)</li>
            </ul>

            <?php if (!empty($report['additional_notes'])): ?>
              <div class="bg-light p-3 rounded mb-3">
                <h6 class="fw-bold mb-1">Additional Notes:</h6>
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($report['additional_notes'])) ?></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($auth_user)): ?>
              <a href="<?= auth_url('match_submit.php?' . ($type === 'lost' ? 'lost_id=' . $id : 'found_id=' . $id)) ?>" class="btn btn-warning w-100 fw-bold">
                <i class="bi bi-link-45deg"></i> Propose a Match for this Pet
              </a>
            <?php else: ?>
              <div class="alert alert-secondary text-center mb-0">
                <a href="<?= auth_url('login.php') ?>">Log in</a> to propose a match for this report.
              </div>
            <?php endif; ?>

            <!-- Staff Moderation Takedown Control -->
            <?php if (!empty($auth_user) && in_array($auth_user['role'], ['Staff', 'Administrator'], true)): ?>
              <hr class="my-3">
              <form action="<?= auth_url('view_report.php?type=' . $type . '&id=' . $id) ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this report? This cannot be undone.');">
                <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid ?? '') ?>">
                <input type="hidden" name="action" value="delete_report">
                <input type="hidden" name="report_type" value="<?= htmlspecialchars($type) ?>">
                <input type="hidden" name="report_id" value="<?= (int)$id ?>">
                <input type="hidden" name="pet_id" value="<?= (int)($report['pet_id'] ?? 0) ?>">
                <button type="submit" class="btn btn-outline-danger w-100">
                  <i class="bi bi-trash3-fill me-1"></i> Delete Post
                </button>
              </form>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
require_once 'includes/footer.php';
?>