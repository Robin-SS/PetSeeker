<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3 border-0">
    <h5 class="fw-bold mb-0">Active Community Listings</h5>
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Pet Details</th>
          <th>Type</th>
          <th>Breed & Color</th>
          <th>Location & Date</th>
          <th>Status</th>
          <th>Reporter</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($active_reports)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">No active community reports found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($active_reports as $item): ?>
            <?php $img_src = function_exists('get_pet_photo_url') ? get_pet_photo_url($item['photo'] ?? null) : ($item['photo'] ?? null); ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <?php if (!empty($img_src)): ?>
                    <img src="<?= htmlspecialchars($img_src) ?>" 
                         class="rounded me-2 border flex-shrink-0" 
                         alt="Pet Thumbnail"
                         style="width: 48px !important; height: 48px !important; min-width: 48px !important; max-width: 48px !important; min-height: 48px !important; max-height: 48px !important; object-fit: cover !important; display: block;">
                  <?php else: ?>
                    <div class="bg-light rounded border d-flex align-items-center justify-content-center me-2 text-muted flex-shrink-0"
                         style="width: 48px !important; height: 48px !important; min-width: 48px !important; font-size: 1.1rem;">
                      <i class="bi bi-camera"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <strong class="d-block text-truncate" style="max-width: 140px;">
                      <?= !empty($item['pet_name']) ? htmlspecialchars($item['pet_name']) : 'Unnamed' ?>
                    </strong>
                    <div class="small text-muted">ID: #<?= (int)$item['report_id'] ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge <?= $item['report_type'] === 'lost' ? 'bg-danger' : 'bg-success' ?>">
                  <?= ucfirst($item['report_type']) ?>
                </span>
              </td>
              <td>
                <div><?= htmlspecialchars($item['species'] . ' - ' . $item['breed']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($item['color']) ?></div>
              </td>
              <td>
                <div><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($item['location_name']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($item['event_date']) ?></div>
              </td>
              <td>
                <span class="badge <?= $item['status'] === 'Approved' ? 'bg-success' : 'bg-secondary' ?>">
                  <?= htmlspecialchars($item['status']) ?>
                </span>
              </td>
              <td>
                <div class="small fw-semibold"><?= htmlspecialchars($item['reporter_name']) ?></div>
              </td>
              <td class="text-end">
                <a href="<?= auth_url('view_report.php?type=' . $item['report_type'] . '&id=' . $item['report_id']) ?>" class="btn btn-sm btn-outline-secondary me-1" target="_blank" title="View Full Report">
                  <i class="bi bi-eye"></i>
                </a>

                <form action="<?= auth_url('staff_dashboard.php?tab=reports') ?>" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this active report? This will remove the listing and pet record.');">
                  <input type="hidden" name="action" value="takedown_post">
                  <input type="hidden" name="report_type" value="<?= htmlspecialchars($item['report_type']) ?>">
                  <input type="hidden" name="report_id" value="<?= (int)$item['report_id'] ?>">
                  <input type="hidden" name="pet_id" value="<?= (int)$item['pet_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Takedown Report">
                    <i class="bi bi-trash3-fill"></i> Takedown
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>