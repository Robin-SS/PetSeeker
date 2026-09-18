<?php
/**
 * @var array $categories
 * @var array $locations
 */
?>
<div class="row g-4">
  <!-- Categories / Species & Breeds -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Pet Categories</h5>
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
          <?= count($categories) ?> Total
        </span>
      </div>
      <div class="card-body">
        <form action="<?= auth_url('admin_dashboard.php?tab=management') ?>" method="POST" class="row g-2 mb-3" data-validate novalidate>
          <input type="hidden" name="action" value="add_category">

          <div class="col-sm-5">
            <input type="text" name="species" class="form-control form-control-sm" placeholder="Species (e.g. Dog)" required>
          </div>
          <div class="col-sm-5">
            <input type="text" name="breed" class="form-control form-control-sm" placeholder="Breed (e.g. Beagle)" required>
          </div>
          <div class="col-sm-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i></button>
          </div>
        </form>

        <div class="admin-scroll-list">
          <ul class="list-group list-group-flush border-top">
            <?php if (empty($categories)): ?>
              <li class="list-group-item text-center text-muted py-3">No categories registered.</li>
            <?php else: ?>
              <?php foreach ($categories as $cat): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <div>
                    <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($cat['species']) ?></span>
                    <?= htmlspecialchars($cat['breed']) ?>
                  </div>
                  <span class="badge text-bg-light small">ID #<?= (int)$cat['category_id'] ?></span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Locations -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Operating Locations</h5>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">
          <?= count($locations) ?> Total
        </span>
      </div>
      <div class="card-body">
        <form action="<?= auth_url('admin_dashboard.php?tab=management') ?>" method="POST" class="row g-2 mb-3" data-validate novalidate>
          <input type="hidden" name="action" value="add_location">

          <div class="col-sm-9">
            <input type="text" name="location_name" class="form-control form-control-sm" placeholder="City or District Name" required>
          </div>
          <div class="col-sm-3">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> Add</button>
          </div>
        </form>

        <div class="admin-scroll-list">
          <ul class="list-group list-group-flush border-top">
            <?php if (empty($locations)): ?>
              <li class="list-group-item text-center text-muted py-3">No locations registered.</li>
            <?php else: ?>
              <?php foreach ($locations as $loc): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <div>
                    <i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($loc['location_name']) ?>
                  </div>
                  <span class="badge text-bg-light small">ID #<?= (int)$loc['location_id'] ?></span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>