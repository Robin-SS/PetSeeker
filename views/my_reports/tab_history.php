<?php if (empty($history_matches)): ?>
  <div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
      <i class="bi bi-clock-history fs-1 text-muted d-block mb-2"></i>
      <h5 class="text-muted">No completed match history yet.</h5>
      <p class="small text-muted mb-0">When matches are confirmed, their reunion details and contact records will be saved here.</p>
    </div>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($history_matches as $h): ?>
      <?php 
        $i_am_owner    = ((int)$h['owner_id'] === $user_id);
        $partner_name  = $i_am_owner ? $h['finder_name'] : $h['owner_name'];
        $partner_phone = $i_am_owner ? $h['finder_phone'] : $h['owner_phone'];
        $partner_email = $i_am_owner ? $h['finder_email'] : $h['owner_email'];
        $partner_role  = $i_am_owner ? 'Finder' : 'Pet Owner';

        $lost_img      = get_pet_photo_url($h['lost_photo'] ?? null);
        $found_img     = get_pet_photo_url($h['found_photo'] ?? null);
      ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-4 border-success">
          <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div>
              <span class="badge bg-success me-2"><i class="bi bi-check-circle-fill me-1"></i> Reunited & Resolved</span>
              <strong>Match Record #<?= $h['match_id'] ?></strong>
              <span class="text-muted small ms-2">&bull; Resolved on <?= htmlspecialchars(date('M d, Y', strtotime($h['resolved_date']))) ?></span>
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
                  </div>
                </div>
              </div>

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
                  </div>
                </div>
              </div>
            </div>

            <!-- Handover Contact Info Box -->
            <div class="p-3 bg-white border rounded">
              <h6 class="fw-bold mb-2 text-success">
                <i class="bi bi-person-lines-fill me-1"></i> Contact Information (<?= $partner_role ?>)
              </h6>
              <div class="d-flex flex-wrap gap-4 text-muted small">
                <div><strong>Name:</strong> <?= htmlspecialchars($partner_name) ?></div>
                <div>
                  <strong>Phone:</strong> 
                  <?= !empty($partner_phone) ? htmlspecialchars($partner_phone) : '<span class="fst-italic text-muted">Not provided</span>' ?>
                </div>
                <div>
                  <strong>Email:</strong> 
                  <?= !empty($partner_email) ? htmlspecialchars($partner_email) : '<span class="fst-italic text-muted">Not provided</span>' ?>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>