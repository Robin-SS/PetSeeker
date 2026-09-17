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
            <?php $img_src = get_pet_photo_url($item['photo'] ?? null); ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <?php if ($img_src): ?>
                    <img src="<?= htmlspecialchars($img_src) ?>" class="rounded me-2 object-fit-cover" width="50" height="50" alt="Pet Thumbnail">
                  <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-2 text-muted" style="width: 50px; height: 50px;">
                      <i class="bi bi-camera"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <strong><?= $item['pet_name'] ? htmlspecialchars($item['pet_name']) : 'Unnamed' ?></strong>
                    <div class="small text-muted">ID: #<?= $item['report_id'] ?></div>
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
                  <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid ?? '') ?>">
                  <input type="hidden" name="action" value="takedown_post">
                  <input type="hidden" name="report_type" value="<?= $item['report_type'] ?>">
                  <input type="hidden" name="report_id" value="<?= $item['report_id'] ?>">
                  <input type="hidden" name="pet_id" value="<?= $item['pet_id'] ?>">
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