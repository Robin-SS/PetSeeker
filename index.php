<?php
require_once 'includes/header.php';

// Fetch filter options
$categories = $pdo->query("SELECT DISTINCT species FROM category ORDER BY species")->fetchAll();
$locations  = $pdo->query("SELECT * FROM location ORDER BY location_name")->fetchAll();

// Read query filters
$report_type = $_GET['type'] ?? 'all'; // 'lost', 'found', or 'all'
$species     = $_GET['species'] ?? '';
$location_id = (int)($_GET['location_id'] ?? 0);
$keyword     = trim($_GET['keyword'] ?? '');

$lost_sql = "
    SELECT 
        'Lost' AS report_type,
        l.lost_id AS report_id,
        p.name AS pet_name,
        c.species,
        c.breed,
        p.color,
        p.photo,
        loc.location_name,
        l.date_lost AS event_date,
        l.status::text AS status,
        l.created_at
    FROM lost_pet l
    JOIN pet p ON l.pet_id = p.pet_id
    JOIN category c ON p.category_id = c.category_id
    JOIN location loc ON l.location_id = loc.location_id
    WHERE l.status = 'Approved'
";

$found_sql = "
    SELECT 
        'Found' AS report_type,
        f.found_id AS report_id,
        p.name AS pet_name,
        c.species,
        c.breed,
        p.color,
        p.photo,
        loc.location_name,
        f.date_found AS event_date,
        f.status::text AS status,
        f.created_at
    FROM found_pet f
    JOIN pet p ON f.pet_id = p.pet_id
    JOIN category c ON p.category_id = c.category_id
    JOIN location loc ON f.location_id = loc.location_id
    WHERE f.status = 'Approved'
";

$params = [];

// Apply species filter
if (!empty($species)) {
    $lost_sql  .= " AND c.species = :species_l";
    $found_sql .= " AND c.species = :species_f";
    $params[':species_l'] = $species;
    $params[':species_f'] = $species;
}

// Apply location filter
if (!empty($location_id)) {
    $lost_sql  .= " AND l.location_id = :loc_l";
    $found_sql .= " AND f.location_id = :loc_f";
    $params[':loc_l'] = $location_id;
    $params[':loc_f'] = $location_id;
}

// Apply search term (breed, color, or pet name)
if (!empty($keyword)) {
    $lost_sql  .= " AND (c.breed ILIKE :kw_l1 OR p.color ILIKE :kw_l2 OR p.name ILIKE :kw_l3)";
    $found_sql .= " AND (c.breed ILIKE :kw_f1 OR p.color ILIKE :kw_f2)";
    $params[':kw_l1'] = "%$keyword%";
    $params[':kw_l2'] = "%$keyword%";
    $params[':kw_l3'] = "%$keyword%";
    $params[':kw_f1'] = "%$keyword%";
    $params[':kw_f2'] = "%$keyword%";
}

// Combine or isolate based on selected type
if ($report_type === 'lost') {
    $final_query = $lost_sql . " ORDER BY created_at DESC";
} elseif ($report_type === 'found') {
    $final_query = $found_sql . " ORDER BY created_at DESC";
} else {
    $final_query = "($lost_sql) UNION ALL ($found_sql) ORDER BY created_at DESC";
}

$stmt = $pdo->prepare($final_query);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<!-- Search and Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-4">
    <form action="<?= auth_url('index.php') ?>" method="GET" class="row g-3">
      <?php if (!empty($auth_sid)): ?>
        <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid) ?>">
      <?php endif; ?>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Report Type</label>
        <select name="type" class="form-select">
          <option value="all" <?= $report_type === 'all' ? 'selected' : '' ?>>All Active Reports</option>
          <option value="lost" <?= $report_type === 'lost' ? 'selected' : '' ?>>Lost Pets</option>
          <option value="found" <?= $report_type === 'found' ? 'selected' : '' ?>>Found Pets</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Species</label>
        <select name="species" class="form-select">
          <option value="">All Species</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['species']) ?>" <?= $species === $cat['species'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['species']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Location</label>
        <select name="location_id" class="form-select">
          <option value="0">All Locations</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?= $loc['location_id'] ?>" <?= $location_id === (int)$loc['location_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($loc['location_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">Search</label>
        <input type="text" name="keyword" id="petSearchInput" class="form-control" placeholder="Breed, color, name..." value="<?= htmlspecialchars($keyword) ?>">
      </div>

      <div class="col-12 d-flex justify-content-end gap-2 mt-3">
        <a href="<?= auth_url('index.php') ?>" class="btn btn-outline-secondary">Clear Filters</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-search me-1"></i> Filter</button>
      </div>
    </form>
  </div>
</div>

<!-- Results Grid -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
  <?php if (empty($reports)): ?>
    <div class="col-12">
      <div class="alert alert-info text-center py-4">
        No active pet reports found matching your criteria.
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($reports as $item): ?>
      <?php $img_src = get_pet_photo_url($item['photo'] ?? null); ?>
      <div class="col pet-card-item">
        <div class="card h-100 shadow-sm border-0 position-relative pet-card">
          
          <!-- Image -->
          <div class="ratio ratio-4x3 bg-secondary bg-opacity-25 rounded-top">
            <?php if ($img_src): ?>
              <img src="<?= htmlspecialchars($img_src) ?>" class="card-img-top object-fit-cover pet-card-img" alt="Pet Image">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center text-muted">
                <i class="bi bi-camera fs-1"></i>
              </div>
            <?php endif; ?>
          </div>

          <!-- Badges -->
          <div class="position-absolute top-0 start-0 m-2">
            <span class="badge <?= $item['report_type'] === 'Lost' ? 'bg-danger' : 'bg-success' ?> fs-6 shadow-sm">
              <?= $item['report_type'] ?>
            </span>
          </div>

          <div class="card-body">
            <h5 class="card-title text-truncate">
              <?= $item['pet_name'] ? htmlspecialchars($item['pet_name']) : '<span class="text-muted fst-italic">Unknown Name</span>' ?>
            </h5>
            <p class="card-text mb-1 text-muted">
              <strong>Type:</strong> <?= htmlspecialchars($item['species'] . ' - ' . $item['breed']) ?>
            </p>
            <p class="card-text mb-1 text-muted">
              <strong>Color:</strong> <?= htmlspecialchars($item['color']) ?>
            </p>
            <p class="card-text mb-1 text-muted">
              <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($item['location_name']) ?>
            </p>
            <p class="card-text text-muted small">
              <i class="bi bi-calendar-event me-1"></i> <?= $item['report_type'] === 'Lost' ? 'Lost on:' : 'Found on:' ?> <?= htmlspecialchars($item['event_date']) ?>
            </p>
          </div>

          <div class="card-footer bg-white border-0 pt-0 pb-3">
            <a href="<?= auth_url('view_report.php?type=' . strtolower($item['report_type']) . '&id=' . $item['report_id']) ?>" class="btn btn-outline-primary w-100">
              View Details
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>