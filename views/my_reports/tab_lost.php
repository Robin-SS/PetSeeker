<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3 border-0">
    <h5 class="fw-bold mb-0">Active Lost Pet Listings</h5>
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Pet Details</th>
          <th>Breed & Color</th>
          <th>Location & Date</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($my_lost_reports)): ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">No active lost pet reports.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($my_lost_reports as $row): ?>
            <?php $img_src = get_pet_photo_url($row['photo'] ?? null); ?>
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
                    <strong><?= $row['pet_name'] ? htmlspecialchars($row['pet_name']) : 'Unnamed' ?></strong>
                    <div class="small text-muted">ID: #<?= $row['lost_id'] ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div><?= htmlspecialchars($row['species'] . ' - ' . $row['breed']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($row['color']) ?></div>
              </td>
              <td>
                <div><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($row['location_name']) ?></div>
                <div class="small text-muted"><i class="bi bi-calendar me-1"></i><?= htmlspecialchars($row['date_lost']) ?></div>
              </td>
              <td>
                <span class="badge <?= $row['status'] === 'Approved' ? 'bg-success' : 'bg-secondary' ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td class="text-end">
                <a href="<?= auth_url('view_report.php?type=lost&id=' . $row['lost_id']) ?>" class="btn btn-sm btn-outline-secondary me-1" target="_blank" title="View Public Post">
                  <i class="bi bi-eye"></i>
                </a>

                <form action="<?= auth_url('my_reports.php?tab=lost') ?>" method="POST" class="d-inline" onsubmit="return confirm('Mark this pet as resolved? It will move to your history.');">
                  <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid ?? '') ?>">
                  <input type="hidden" name="action" value="resolve_report">
                  <input type="hidden" name="report_type" value="lost">
                  <input type="hidden" name="report_id" value="<?= $row['lost_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Mark as Resolved">
                    <i class="bi bi-check2-circle"></i> Resolve
                  </button>
                </form>

                <form action="<?= auth_url('my_reports.php?tab=lost') ?>" method="POST" class="d-inline" onsubmit="return confirm('Permanently remove this report?');">
                  <input type="hidden" name="sid" value="<?= htmlspecialchars($auth_sid ?? '') ?>">
                  <input type="hidden" name="action" value="delete_report">
                  <input type="hidden" name="report_type" value="lost">
                  <input type="hidden" name="report_id" value="<?= $row['lost_id'] ?>">
                  <input type="hidden" name="pet_id" value="<?= $row['pet_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Report">
                    <i class="bi bi-trash3"></i>
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