<?php
/**
 * @var array $users
 * @var array $auth_user
 */

// Segregate users into standard and privileged groups
$normal_users = [];
$privileged_users = [];

foreach ($users as $u) {
    if (in_array($u['role'], ['Administrator', 'Staff'], true)) {
        $privileged_users[] = $u;
    } else {
        $normal_users[] = $u;
    }
}
?>

<div class="row g-4">
  <!-- Left Box: Regular / Community Users -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3 border-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0 text-primary">
            <i class="bi bi-people me-2"></i>Community Users
          </h5>
          <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2" id="normalCountBadge">
            <?= count($normal_users) ?> Registered
          </span>
        </div>
        <!-- Search Input -->
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="normalUserSearch" class="form-control bg-light border-start-0" placeholder="Search by name, email, phone, or ID...">
          <button class="btn btn-outline-secondary" type="button" onclick="clearUserSearch('normalUserSearch', 'normalUserTable')">Clear</button>
        </div>
      </div>

      <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
        <table class="table align-middle mb-0" id="normalUserTable">
          <thead class="table-light sticky-top">
            <tr>
              <th class="ps-3">User ID</th>
              <th>Name & Contact</th>
              <th>Registered</th>
              <th class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($normal_users)): ?>
              <tr class="no-data-row">
                <td colspan="4" class="text-center text-muted py-4">No community users found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($normal_users as $u): ?>
                <tr class="user-row">
                  <td class="ps-3 fw-semibold text-muted">#<?= (int)$u['user_id'] ?></td>
                  <td>
                    <div class="fw-bold text-dark user-name"><?= htmlspecialchars($u['name']) ?></div>
                    <div class="small text-muted user-email"><?= htmlspecialchars($u['email']) ?></div>
                    <?php if (!empty($u['phone_number'])): ?>
                      <div class="small text-muted user-phone"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($u['phone_number']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="small text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($u['created_at']))) ?></div>
                  </td>
                  <td class="text-end pe-3">
                    <form action="<?= auth_url('admin_dashboard.php?tab=users') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this user? This cannot be undone.');">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="target_user_id" value="<?= (int)$u['user_id'] ?>">

                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User">
                        <i class="bi bi-trash-fill me-1"></i> Delete
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            <tr id="normalNoMatchesRow" class="d-none">
              <td colspan="4" class="text-center text-muted py-4">No matching community users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right Box: Staff & Administrators -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3 border-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0 text-dark">
            Staff & Admins
          </h5>
          <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2" id="privilegedCountBadge">
            <?= count($privileged_users) ?> Accounts
          </span>
        </div>
        <!-- Search Input -->
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="privilegedUserSearch" class="form-control bg-light border-start-0" placeholder="Search staff or admin...">
          <button class="btn btn-outline-secondary" type="button" onclick="clearUserSearch('privilegedUserSearch', 'privilegedUserTable')">Clear</button>
        </div>
      </div>

      <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
        <table class="table align-middle mb-0" id="privilegedUserTable">
          <thead class="table-light sticky-top">
            <tr>
              <th class="ps-3">Name & Role</th>
              <th class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($privileged_users)): ?>
              <tr class="no-data-row">
                <td colspan="2" class="text-center text-muted py-4">No privileged accounts found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($privileged_users as $u): ?>
                <tr class="user-row">
                  <td class="ps-3">
                    <div class="d-flex align-items-center gap-2">
                      <span class="fw-bold text-dark user-name"><?= htmlspecialchars($u['name'] ?: ($u['username'] ?? 'Staff')) ?></span>
                      <span class="badge <?= match($u['role']) {
                        'Administrator' => 'bg-danger',
                        'Staff'         => 'bg-warning text-dark',
                        default         => 'bg-secondary'
                      } ?>">
                        <?= htmlspecialchars($u['role']) ?>
                      </span>
                    </div>
                    <div class="small text-muted user-email"><?= htmlspecialchars($u['email'] ?: ('@' . ($u['username'] ?? ''))) ?></div>
                    <div class="small text-muted">ID: #<?= (int)$u['user_id'] ?> &bull; Joined <?= htmlspecialchars(date('M d, Y', strtotime($u['created_at']))) ?></div>
                  </td>
                  <td class="text-end pe-3">
                    <?php if ((int)$u['user_id'] !== (int)$auth_user['user_id']): ?>
                      <form action="<?= auth_url('admin_dashboard.php?tab=users') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this staff/admin account? This cannot be undone.');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="target_user_id" value="<?= (int)$u['user_id'] ?>">

                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                          <i class="bi bi-trash-fill"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">Current Session</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            <tr id="privilegedNoMatchesRow" class="d-none">
              <td colspan="2" class="text-center text-muted py-4">No matching staff or admin accounts found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function attachTableFilter(inputId, tableId, noMatchId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const noMatchRow = document.getElementById(noMatchId);
    if (!input || !table) return;

    input.addEventListener('input', function () {
      const filter = this.value.trim().toLowerCase();
      const rows = table.querySelectorAll('tbody tr.user-row');
      let visibleCount = 0;

      rows.forEach(function (row) {
        const text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
          row.classList.remove('d-none');
          visibleCount++;
        } else {
          row.classList.add('d-none');
        }
      });

      if (noMatchRow) {
        if (visibleCount === 0 && rows.length > 0) {
          noMatchRow.classList.remove('d-none');
        } else {
          noMatchRow.classList.add('d-none');
        }
      }
    });
  }

  attachTableFilter('normalUserSearch', 'normalUserTable', 'normalNoMatchesRow');
  attachTableFilter('privilegedUserSearch', 'privilegedUserTable', 'privilegedNoMatchesRow');
});

function clearUserSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (input) {
    input.value = '';
    input.dispatchEvent(new Event('input'));
    input.focus();
  }
}
</script>