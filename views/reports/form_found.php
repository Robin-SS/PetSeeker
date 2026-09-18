<?php
/** 
 * @var array       $categories
 * @var array       $locations
 * @var string      $auth_sid
 * @var string      $error
 * @var string|null $name
 * @var int|null    $category_id
 * @var string|null $color
 * @var int|null    $location_id
 * @var string|null $date_found
 * @var string|null $additional_notes
 */
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    
    <div class="d-flex align-items-center mb-4">
      <a href="<?= auth_url('my_reports.php?tab=found') ?>" class="btn btn-outline-secondary btn-sm me-3">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <div>
        <h2 class="fw-bold mb-0"><i class="bi bi-geo-alt-fill text-success me-2"></i>Report a Found Pet</h2>
        <p class="text-muted mb-0">Submit the details of a pet you found so their family can locate them.</p>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form action="<?= auth_url('report_found.php') ?>" method="POST" enctype="multipart/form-data" data-validate novalidate>
          <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid ?? '') ?>">

          <!-- Pet Basic Info -->
          <h5 class="fw-bold text-success border-bottom pb-2 mb-3">1. Found Pet Details</h5>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Collar Tag / Known Name <span class="text-muted small fw-normal">(Optional)</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Tag said 'Buddy' or leave blank" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Species & Breed <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="">Select Species / Breed...</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['category_id'] ?>" <?= (isset($category_id) && (int)$category_id === (int)$cat['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['species'] . ' - ' . $cat['breed']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Primary Color / Distinct Features <span class="text-danger">*</span></label>
              <input type="text" name="color" class="form-control" placeholder="e.g. Black with white paws, clipped ear" value="<?= htmlspecialchars($color ?? '') ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Pet Photo <span class="text-muted small fw-normal">(Recommended)</span></label>
              <input type="file" name="photo" id="petPhotoInput" class="form-control" accept="image/jpeg,image/png,image/webp">
              <div class="form-text small">Accepted formats: JPG, PNG, WEBP (Max 5MB).</div>
              <div class="mt-2 text-center preview-wrapper d-none" id="previewContainer">
                <img id="petPhotoPreview" src="#" alt="Pet Preview" class="img-thumbnail custom-preview-img">
              </div>
            </div>
          </div>

          <!-- Event Incident Details -->
          <h5 class="fw-bold text-success border-bottom pb-2 mb-3">2. Discovery Details</h5>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Location Found <span class="text-danger">*</span></label>
              <select name="location_id" class="form-select" required>
                <option value="">Select City / Area...</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= (int)$loc['location_id'] ?>" <?= (isset($location_id) && (int)$location_id === (int)$loc['location_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($loc['location_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Date Found <span class="text-danger">*</span></label>
              <input type="date" name="date_found" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($date_found ?? date('Y-m-d')) ?>" required>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Holding Location & Condition Notes</label>
              <textarea name="additional_notes" class="form-control" rows="3" placeholder="Where is the pet currently staying? Mention collar presence, medical state, friendliness, or shelter coordinates..."><?= htmlspecialchars($additional_notes ?? '') ?></textarea>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="d-flex justify-content-end gap-2">
            <a href="<?= auth_url('my_reports.php?tab=found') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
            <button type="submit" class="btn btn-success px-4">
              <i class="bi bi-send-fill me-1"></i> Publish Found Report
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>