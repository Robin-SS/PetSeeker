<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/supabase_storage.php';

// Users must be authenticated to propose a match
require_login();

/** @var array $auth_user */
/** @var PDO $pdo */
/** @var string $auth_sid */

// Accept IDs from either GET or POST so form submission reloads keep state
$lost_id  = (int)($_GET['lost_id'] ?? $_POST['lost_id'] ?? 0);
$found_id = (int)($_GET['found_id'] ?? $_POST['found_id'] ?? 0);

$error   = '';
$success = '';

// ==========================================
// 1. POST SUBMISSION HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_match') {
    $p_lost_id  = (int)($_POST['lost_id'] ?? 0);
    $p_found_id = (int)($_POST['found_id'] ?? 0);

    if ($p_lost_id <= 0 || $p_found_id <= 0) {
        $error = 'Both a Lost Pet and a Found Pet record are required to propose a match.';
    } else {
        try {
            // Check if this pair is already proposed
            $check_stmt = $pdo->prepare('SELECT match_id FROM "match" WHERE lost_id = ? AND found_id = ?');
            $check_stmt->execute([$p_lost_id, $p_found_id]);

            if ($check_stmt->fetch()) {
                $error = 'A match proposal between these two reports has already been submitted and is pending review.';
            } else {
                // Calculate baseline confidence score based on category match
                $score_stmt = $pdo->prepare('
                    SELECT 
                        lp.category_id AS lost_cat, 
                        fp.category_id AS found_cat,
                        l.location_id AS lost_loc,
                        f.location_id AS found_loc
                    FROM lost_pet l
                    JOIN pet lp ON l.pet_id = lp.pet_id
                    JOIN found_pet f ON f.found_id = ?
                    JOIN pet fp ON f.pet_id = fp.pet_id
                    WHERE l.lost_id = ?
                ');
                $score_stmt->execute([$p_found_id, $p_lost_id]);
                $pair = $score_stmt->fetch();

                $confidence = 50.00;
                if ($pair) {
                    if ($pair['lost_cat'] === $pair['found_cat']) {
                        $confidence += 25.00;
                    }
                    if ($pair['lost_loc'] === $pair['found_loc']) {
                        $confidence += 15.00;
                    }
                }

                // Insert into PostgreSQL "match" table using escaped quotes
                $ins_stmt = $pdo->prepare('
                    INSERT INTO "match" (lost_id, found_id, confidence_score, status, proposed_by_user_id) 
                    VALUES (?, ?, ?, \'Pending\', ?)
                ');
                $ins_stmt->execute([$p_lost_id, $p_found_id, $confidence, $auth_user['user_id']]);

                header('Location: ' . auth_url('index.php?msg=match_submitted'));
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Failed to submit match proposal: ' . $e->getMessage();
        }
    }
}

// ==========================================
// 2. RETRIEVE SOURCE AND CANDIDATE REPORTS
// ==========================================
$source_type = null;
$source_report = null;
$candidates = [];

// Determine source context
$source_is_lost = isset($_GET['lost_id']) || (!isset($_GET['found_id']) && isset($_POST['lost_id']));

if ($source_is_lost && $lost_id > 0) {
    $source_type = 'lost';
    $stmt = $pdo->prepare('
        SELECT l.lost_id, l.date_lost, p.name AS pet_name, p.photo, p.color, c.category_id, c.species, c.breed, loc.location_name
        FROM lost_pet l
        JOIN pet p ON l.pet_id = p.pet_id
        JOIN category c ON p.category_id = c.category_id
        JOIN location loc ON l.location_id = loc.location_id
        WHERE l.lost_id = ?
    ');
    $stmt->execute([$lost_id]);
    $source_report = $stmt->fetch();

    if ($source_report) {
        $cand_stmt = $pdo->prepare('
            SELECT f.found_id, f.date_found, p.photo, p.color, c.species, c.breed, loc.location_name
            FROM found_pet f
            JOIN pet p ON f.pet_id = p.pet_id
            JOIN category c ON p.category_id = c.category_id
            JOIN location loc ON f.location_id = loc.location_id
            WHERE c.species = ? AND f.status = \'Approved\'
            ORDER BY f.created_at DESC
        ');
        $cand_stmt->execute([$source_report['species']]);
        $candidates = $cand_stmt->fetchAll();
    }
} elseif ($found_id > 0) {
    $source_type = 'found';
    $stmt = $pdo->prepare('
        SELECT f.found_id, f.date_found, p.name AS pet_name, p.photo, p.color, c.category_id, c.species, c.breed, loc.location_name
        FROM found_pet f
        JOIN pet p ON f.pet_id = p.pet_id
        JOIN category c ON p.category_id = c.category_id
        JOIN location loc ON f.location_id = loc.location_id
        WHERE f.found_id = ?
    ');
    $stmt->execute([$found_id]);
    $source_report = $stmt->fetch();

    if ($source_report) {
        $cand_stmt = $pdo->prepare('
            SELECT l.lost_id, l.date_lost, p.name AS pet_name, p.photo, p.color, c.species, c.breed, loc.location_name
            FROM lost_pet l
            JOIN pet p ON l.pet_id = p.pet_id
            JOIN category c ON p.category_id = c.category_id
            JOIN location loc ON l.location_id = loc.location_id
            WHERE c.species = ? AND l.status = \'Approved\'
            ORDER BY l.created_at DESC
        ');
        $cand_stmt->execute([$source_report['species']]);
        $candidates = $cand_stmt->fetchAll();
    }
}

if (!$source_report) {
    echo '<div class="alert alert-danger">Valid source report not found. <a href="' . auth_url('index.php') . '">Back to Feed</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$source_img = get_pet_photo_url($source_report['photo'] ?? null);
?>

<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="<?= auth_url('view_report.php?type=' . $source_type . '&id=' . ($source_type === 'lost' ? $lost_id : $found_id)) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Report
      </a>
      <span class="badge bg-warning text-dark px-3 py-2 fs-6">Proposing Match</span>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Source Report Reference Card -->
    <div class="card shadow-sm border-0 mb-4 border-start border-4 <?= $source_type === 'lost' ? 'border-danger' : 'border-success' ?>">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-auto">
            <?php if ($source_img): ?>
              <img src="<?= htmlspecialchars($source_img) ?>" class="rounded object-fit-cover match-source-thumb" alt="Source Pet">
            <?php else: ?>
              <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted match-source-thumb">
                <i class="bi bi-camera fs-3"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="col">
            <span class="badge <?= $source_type === 'lost' ? 'bg-danger' : 'bg-success' ?> mb-1">
              Source <?= ucfirst($source_type) ?> Report #<?= $source_type === 'lost' ? $lost_id : $found_id ?>
            </span>
            <h5 class="fw-bold mb-1"><?= $source_report['pet_name'] ? htmlspecialchars($source_report['pet_name']) : 'Unnamed' ?></h5>
            <div class="text-muted small">
              <?= htmlspecialchars($source_report['species'] . ' (' . $source_report['breed'] . ')') ?> &bull; 
              <?= htmlspecialchars($source_report['color']) ?> &bull; 
              <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($source_report['location_name']) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Candidate Matching List -->
    <h5 class="fw-bold mb-3">
      Select a Matching <?= $source_type === 'lost' ? 'Found' : 'Lost' ?> <?= htmlspecialchars($source_report['species']) ?> Report
    </h5>

    <?php if (empty($candidates)): ?>
      <div class="alert alert-light border text-center py-5">
        <i class="bi bi-search fs-2 text-muted d-block mb-2"></i>
        No active <?= $source_type === 'lost' ? 'found' : 'lost' ?> reports match this species right now.
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($candidates as $cand): ?>
          <?php $cand_img = get_pet_photo_url($cand['photo'] ?? null); ?>
          <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                  <?php if ($cand_img): ?>
                    <img src="<?= htmlspecialchars($cand_img) ?>" class="rounded object-fit-cover flex-shrink-0 match-candidate-thumb" alt="Candidate Pet">
                  <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted flex-shrink-0 match-candidate-thumb">
                      <i class="bi bi-camera fs-4"></i>
                    </div>
                  <?php endif; ?>

                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                      <h6 class="fw-bold mb-1">
                        <?= !empty($cand['pet_name']) ? htmlspecialchars($cand['pet_name']) : 'ID #' . ($source_type === 'lost' ? $cand['found_id'] : $cand['lost_id']) ?>
                      </h6>
                      <span class="badge bg-light text-dark border">
                        <?= htmlspecialchars($cand['breed']) ?>
                      </span>
                    </div>
                    <div class="small text-muted mb-1">Color: <?= htmlspecialchars($cand['color']) ?></div>
                    <div class="small text-muted mb-3"><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($cand['location_name']) ?></div>

                    <form action="<?= auth_url('match_submit.php?' . ($source_type === 'lost' ? 'lost_id=' . $lost_id : 'found_id=' . $found_id)) ?>" method="POST" data-confirm="Propose this match? A notification will be sent to the other party.">
                      <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid ?? '') ?>">
                      <input type="hidden" name="action" value="submit_match">
                      <input type="hidden" name="lost_id" value="<?= $source_type === 'lost' ? $lost_id : $cand['lost_id'] ?>">
                      <input type="hidden" name="found_id" value="<?= $source_type === 'lost' ? $cand['found_id'] : $found_id ?>">
                      
                      <button type="submit" class="btn btn-warning btn-sm w-100 fw-semibold">
                        <i class="bi bi-link-45deg"></i> Propose as Match
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>