</main>

    <footer class="text-center py-3 text-muted border-top mt-auto bg-white">
      <small>&copy; <?= date('Y') ?> PETSeeker. All rights reserved.</small>
    </footer>

  </div> <!-- /.app-main -->
</div> <!-- /.app-wrapper -->

<?php if (!empty($auth_user)): ?>
  <!-- Notification Offcanvas Sliding Panel -->
  <div class="offcanvas offcanvas-end shadow" tabindex="-1" id="notificationDrawer" aria-labelledby="notificationDrawerLabel" style="width: 380px;">
    <div class="offcanvas-header border-bottom py-3">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-bell-fill text-primary fs-5"></i>
        <h5 class="offcanvas-title fw-bold mb-0" id="notificationDrawerLabel">Notifications</h5>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllReadBtn" title="Mark all as read">
          <i class="bi bi-check2-all"></i>
        </button>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
    </div>
    
    <div class="offcanvas-body p-0" id="notificationListContainer">
      <div class="text-center py-5 text-muted" id="notifLoading">
        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
        <p class="small mb-0">Loading updates...</p>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const notifBadge = document.getElementById('notifBadge');
    const container = document.getElementById('notificationListContainer');
    const markReadBtn = document.getElementById('markAllReadBtn');
    const drawerEl = document.getElementById('notificationDrawer');

    // Retrieve active tab session ID from URL query first, then sessionStorage
    const urlParams = new URLSearchParams(window.location.search);
    const tabId = urlParams.get('tab_id') || sessionStorage.getItem('petseeker_tab_id') || '';
    const baseUrl = '<?= defined("BASE_URL") ? rtrim(BASE_URL, "/") . "/" : "/PetSeeker/" ?>';

    function buildUrl(endpoint, action) {
      let url = `${baseUrl}${endpoint}?action=${encodeURIComponent(action)}`;
      if (tabId) {
        url += `&tab_id=${encodeURIComponent(tabId)}`;
      }
      return url;
    }

    function fetchNotifications() {
      fetch(buildUrl('notification.php', 'fetch'))
        .then(res => {
          if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
          }
          return res.json();
        })
        .then(data => {
          // If server explicitly responds with success: false (e.g., unauthorized)
          if (!data.success) {
            container.innerHTML = `
              <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <p class="small mb-0">${data.error || 'No notifications yet.'}</p>
              </div>`;
            return;
          }

          // Update badge count
          if (notifBadge) {
            if (data.unread_count > 0) {
              notifBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
              notifBadge.classList.remove('d-none');
            } else {
              notifBadge.classList.add('d-none');
            }
          }

          // Empty state
          if (!data.notifications || data.notifications.length === 0) {
            container.innerHTML = `
              <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-2 d-block mb-2 text-secondary opacity-50"></i>
                <p class="small mb-0">No notifications yet.</p>
              </div>`;
            return;
          }

          // Render notifications list
          let html = '<div class="list-group list-group-flush">';
          data.notifications.forEach(item => {
            const bg = item.is_read ? 'bg-white' : 'bg-light';
            let targetUrl = item.link_url ? item.link_url : '#';

            // Preserve tab_id on target links
            if (targetUrl !== '#' && tabId && !targetUrl.includes('tab_id=')) {
              targetUrl += (targetUrl.includes('?') ? '&' : '?') + 'tab_id=' + encodeURIComponent(tabId);
            }

            html += `
              <a href="${targetUrl}" class="list-group-item list-group-item-action ${bg} py-3 px-3 border-bottom">
                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                  <strong class="text-dark small">${item.title}</strong>
                  <small class="text-muted" style="font-size: 0.72rem;">${item.time_ago}</small>
                </div>
                <p class="mb-1 text-secondary small" style="line-height: 1.35;">${item.message}</p>
              </a>
            `;
          });
          html += '</div>';
          container.innerHTML = html;
        })
        .catch(() => {
          container.innerHTML = `
            <div class="text-center py-5 text-muted">
              <i class="bi bi-exclamation-circle text-warning fs-3 d-block mb-2"></i>
              <p class="small mb-0">Unable to load updates.</p>
            </div>`;
        });
    }

    // Initial background fetch
    fetchNotifications();

    // Reload notifications each time drawer slides open
    if (drawerEl) {
      drawerEl.addEventListener('show.bs.offcanvas', fetchNotifications);
    }

    // Mark all as read click event
    if (markReadBtn) {
      markReadBtn.addEventListener('click', function () {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        if (tabId) {
          formData.append('tab_id', tabId);
        }

        fetch(buildUrl('notification.php', 'mark_read'), { 
          method: 'POST', 
          body: formData 
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              if (notifBadge) {
                notifBadge.classList.add('d-none');
              }
              fetchNotifications();
            }
          })
          .catch(err => console.error('Error marking notifications as read:', err));
      });
    }
  });
  </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Application JavaScript -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>