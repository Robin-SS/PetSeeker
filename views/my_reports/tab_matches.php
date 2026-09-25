<?php if (empty($pending_matches)): ?>
  <div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
      <i class="bi bi-link-45deg fs-1 text-muted d-block mb-2"></i>
      <h5 class="text-muted">No pending match proposals.</h5>
      <p class="small text-muted mb-0">When someone proposes a potential match for one of your pets, it will show up here for review.</p>
    </div>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($pending_matches as $m): ?>
      <?php 
        $is_sender = ((int)$m['proposed_by_user_id'] === $user_id); 
        $lost_img  = function_exists('get_pet_photo_url') ? get_pet_photo_url($m['lost_photo'] ?? null) : ($m['lost_photo'] ?? null);
        $found_img = function_exists('get_pet_photo_url') ? get_pet_photo_url($m['found_photo'] ?? null) : ($m['found_photo'] ?? null);
      ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div>
              <span class="badge bg-warning text-dark me-2">Pending Decision</span>
              <strong>Proposal #<?= (int)$m['match_id'] ?></strong>
              <span class="text-muted small ms-2">&bull; Submitted <?= htmlspecialchars(date('M d, Y', strtotime($m['created_at']))) ?></span>
            </div>

          </div>

          <div class="card-body pt-0">
            <div class="row align-items-center g-3 p-3 bg-light rounded">
              <!-- Lost Pet Side -->
              <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                  <?php if (!empty($lost_img)): ?>
                    <img src="<?= htmlspecialchars($lost_img) ?>" 
                         class="rounded border flex-shrink-0" 
                         alt="Lost Pet"
                         style="width: 72px !important; height: 72px !important; min-width: 72px !important; max-width: 72px !important; min-height: 72px !important; max-height: 72px !important; object-fit: cover !important; display: block;">
                  <?php else: ?>
                    <div class="bg-white rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                         style="width: 72px !important; height: 72px !important; min-width: 72px !important; font-size: 1.5rem;">
                      <i class="bi bi-camera"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <span class="badge bg-danger small mb-1">Lost Pet #<?= (int)$m['lost_id'] ?></span>
                    <h6 class="fw-bold mb-0 text-truncate" style="max-width: 180px;"><?= !empty($m['lost_name']) ? htmlspecialchars($m['lost_name']) : 'Unnamed' ?></h6>
                    <div class="small text-muted"><?= htmlspecialchars($m['lost_species'] . ' - ' . $m['lost_breed']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($m['lost_color']) ?></div>
                    <a href="<?= auth_url('view_report.php?type=lost&id=' . $m['lost_id']) ?>" target="_blank" class="small text-decoration-none">View Post &rarr;</a>
                  </div>
                </div>
              </div>

              <!-- Connector -->
              <div class="col-md-2 text-center text-muted">
                <i class="bi bi-arrow-left-right fs-4 d-none d-md-inline"></i>
                <div class="small fw-semibold mt-1">Matched With</div>
              </div>

              <!-- Found Pet Side -->
              <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                  <?php if (!empty($found_img)): ?>
                    <img src="<?= htmlspecialchars($found_img) ?>" 
                         class="rounded border flex-shrink-0" 
                         alt="Found Pet"
                         style="width: 72px !important; height: 72px !important; min-width: 72px !important; max-width: 72px !important; min-height: 72px !important; max-height: 72px !important; object-fit: cover !important; display: block;">
                  <?php else: ?>
                    <div class="bg-white rounded border d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                         style="width: 72px !important; height: 72px !important; min-width: 72px !important; font-size: 1.5rem;">
                      <i class="bi bi-camera"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <span class="badge bg-success small mb-1">Found Pet #<?= (int)$m['found_id'] ?></span>
                    <h6 class="fw-bold mb-0 text-truncate" style="max-width: 180px;"><?= htmlspecialchars($m['found_breed']) ?></h6>
                    <div class="small text-muted">Color: <?= htmlspecialchars($m['found_color']) ?></div>
                    <a href="<?= auth_url('view_report.php?type=found&id=' . $m['found_id']) ?>" target="_blank" class="small text-decoration-none">View Post &rarr;</a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recipient Actions -->
            <?php if (!$is_sender): ?>
              <div class="d-flex justify-content-between align-items-center mt-3 pt-2">
                <div class="text-muted small">
                  <i class="bi bi-info-circle me-1"></i> Proposed by <strong><?= htmlspecialchars($m['proposer_name'] ?? 'Community Member') ?></strong>. Confirm if this is the pet.
                </div>
                <div class="d-flex gap-2">
                  <form action="<?= auth_url('my_reports.php?tab=matches') ?>" method="POST" class="d-inline" onsubmit="return confirm('Confirm and accept this match proposal? This will resolve both listings.');">
                    <input type="hidden" name="action" value="accept_match">
                    <input type="hidden" name="match_id" value="<?= (int)$m['match_id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                      <i class="bi bi-check-lg me-1"></i> Confirm & Accept Match
                    </button>
                  </form>

                  <form action="<?= auth_url('my_reports.php?tab=matches') ?>" method="POST" class="d-inline" onsubmit="return confirm('Decline this match proposal?');">
                    <input type="hidden" name="action" value="decline_match">
                    <input type="hidden" name="match_id" value="<?= (int)$m['match_id'] ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm px-3">
                      <i class="bi bi-x-lg me-1"></i> Decline
                    </button>
                  </form>
                </div>
              </div>
            <?php else: ?>
              <div class="mt-3 text-muted small">
                <i class="bi bi-clock-history me-1"></i> You proposed this match. Waiting for the recipient to confirm or decline.
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>