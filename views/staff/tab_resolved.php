<?php if (empty($resolved_matches)): ?>
  <div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
      <i class="bi bi-check2-all fs-1 text-muted d-block mb-2"></i>
      <h5 class="text-muted">No resolved matches on record yet.</h5>
      <p class="small text-muted mb-0">When owners and finders confirm their match proposals, the paired records will be archived here.</p>
    </div>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($resolved_matches as $h): ?>
      <?php
        $lost_img  = get_pet_photo_url($h['lost_photo'] ?? null);
        $found_img = get_pet_photo_url($h['found_photo'] ?? null);
      ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-4 border-success">
          <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div>
              <span class="badge bg-success me-2"><i class="bi bi-check-circle-fill me-1"></i> Reunited & Resolved</span>
              <strong>Match Record #<?= $h['match_id'] ?></strong>
              <span class="text-muted small ms-2">&bull; Confirmed on <?= htmlspecialchars(date('M d, Y', strtotime($h['resolved_date']))) ?></span>
            </div>
            <span class="badge bg-light text-success border border-success">
              Match Score: <?= htmlspecialchars($h['confidence_score'] ?? '100') ?>%
            </span>
          </div>

          <div class="card-body pt-0">
            <div class="row align-items-center g-3 p-3 bg-light rounded mb-3">
              <!-- Lost Pet Info -->
              <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($lost_img): ?>
                    <img src="<?= htmlspecialchars($lost_img) ?>" class="rounded object-fit-cover flex-shrink-0 match-thumb-box" alt="Lost Pet">
                  <?php else: ?>
                    <div class="bg-white rounded d-flex align-items-center justify-content-center text-muted flex-shrink-0 match-thumb-box">
                      <i class="bi bi-camera fs-3"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <span class="badge bg-danger small mb-1">Lost Pet (Report #<?= $h['lost_id'] ?>)</span>
                    <h6 class="fw-bold mb-0"><?= $h['lost_name'] ? htmlspecialchars($h['lost_name']) : 'Unnamed' ?></h6>
                    <div class="small text-muted"><?= htmlspecialchars($h['species'] . ' - ' . $h['lost_breed']) ?></div>
                    <div class="small text-muted">Owner: <strong><?= htmlspecialchars($h['owner_name']) ?></strong></div>
                  </div>
                </div>
              </div>

              <!-- Connector -->
              <div class="col-md-2 text-center text-success">
                <i class="bi bi-check2-all fs-2"></i>
                <div class="small fw-bold">Reunited</div>
              </div>

              <!-- Found Pet Info -->
              <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($found_img): ?>
                    <img src="<?= htmlspecialchars($found_img) ?>" class="rounded object-fit-cover flex-shrink-0 match-thumb-box" alt="Found Pet">
                  <?php else: ?>
                    <div class="bg-white rounded d-flex align-items-center justify-content-center text-muted flex-shrink-0 match-thumb-box">
                      <i class="bi bi-camera fs-3"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <span class="badge bg-success small mb-1">Found Pet (Report #<?= $h['found_id'] ?>)</span>
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($h['found_breed']) ?></h6>
                    <div class="small text-muted"><?= htmlspecialchars($h['found_color']) ?></div>
                    <div class="small text-muted">Finder: <strong><?= htmlspecialchars($h['finder_name']) ?></strong></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact & Audit Record -->
            <div class="p-3 bg-white border rounded">
              <div class="row g-3 small">
                <div class="col-md-6 border-end">
                  <strong class="text-danger"><i class="bi bi-person-fill"></i> Pet Owner Details</strong>
                  <div class="mt-1"><strong>Name:</strong> <?= htmlspecialchars($h['owner_name']) ?></div>
                  <div><strong>Phone:</strong> <?= !empty($h['owner_phone']) ? htmlspecialchars($h['owner_phone']) : '<span class="text-muted fst-italic">None</span>' ?></div>
                  <div><strong>Email:</strong> <?= !empty($h['owner_email']) ? htmlspecialchars($h['owner_email']) : '<span class="text-muted fst-italic">None</span>' ?></div>
                </div>
                <div class="col-md-6">
                  <strong class="text-success"><i class="bi bi-person-check-fill"></i> Finder Details</strong>
                  <div class="mt-1"><strong>Name:</strong> <?= htmlspecialchars($h['finder_name']) ?></div>
                  <div><strong>Phone:</strong> <?= !empty($h['finder_phone']) ? htmlspecialchars($h['finder_phone']) : '<span class="text-muted fst-italic">None</span>' ?></div>
                  <div><strong>Email:</strong> <?= !empty($h['finder_email']) ? htmlspecialchars($h['finder_email']) : '<span class="text-muted fst-italic">None</span>' ?></div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>