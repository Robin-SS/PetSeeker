document.addEventListener('DOMContentLoaded', () => {

  // --------------------------------------------------------------------------
  // Helper: Retrieve active tab's session ID from URL parameter
  // --------------------------------------------------------------------------
  function getActiveSessionId() {
    const params = new URLSearchParams(window.location.search);
    return params.get('sid');
  }

  const currentSid = getActiveSessionId();

  // --------------------------------------------------------------------------
  // 1. Collapsible Sidebar Toggle (Desktop & Mobile)
  // --------------------------------------------------------------------------
  const sidebar = document.getElementById('appSidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (sidebar && toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      if (window.innerWidth >= 992) {
        // Desktop collapse / expand
        sidebar.classList.toggle('collapsed');
      } else {
        // Mobile drawer open
        sidebar.classList.toggle('show-mobile');
        if (backdrop) backdrop.classList.toggle('show');
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', () => {
        sidebar.classList.remove('show-mobile');
        backdrop.classList.remove('show');
      });
    }
  }

  // --------------------------------------------------------------------------
  // 2. Automatic Session ID (sid) Form & Link Propagation
  // --------------------------------------------------------------------------
  if (currentSid) {
    // Pre-populate forms on load
    document.querySelectorAll('form').forEach((form) => {
      let sidInput = form.querySelector('input[name="sid"]');
      if (!sidInput) {
        sidInput = document.createElement('input');
        sidInput.type = 'hidden';
        sidInput.name = 'sid';
        sidInput.value = currentSid;
        form.prepend(sidInput);
      }
    });

    // Intercept dynamically created forms on submit
    document.addEventListener('submit', (e) => {
      const form = e.target.closest('form');
      if (!form) return;

      let sidInput = form.querySelector('input[name="sid"]');
      if (!sidInput) {
        sidInput = document.createElement('input');
        sidInput.type = 'hidden';
        sidInput.name = 'sid';
        sidInput.value = currentSid;
        form.prepend(sidInput);
      }
    }, true);

    // Global Link Interceptor: Appends sid to any internal link clicked
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a');
      if (!link || !link.href) return;

      const rawHref = link.getAttribute('href') || '';
      if (rawHref.startsWith('#') || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:')) {
        return;
      }

      try {
        const targetUrl = new URL(link.href, window.location.origin);

        if (targetUrl.origin === window.location.origin) {
          if (!targetUrl.searchParams.has('sid')) {
            targetUrl.searchParams.set('sid', currentSid);
            link.href = targetUrl.toString();
          }
        }
      } catch (err) {
        // Handle relative or edge-case links gracefully
      }
    });
  }

  // --------------------------------------------------------------------------
  // 3. Live Pet Photo Upload Preview & Size Validation
  // Targets: views/reports/form_lost.php, views/reports/form_found.php
  // --------------------------------------------------------------------------
  const photoInput = document.getElementById('petPhotoInput');
  const previewContainer = document.getElementById('previewContainer');
  const previewImg = document.getElementById('petPhotoPreview');

  if (photoInput && previewContainer && previewImg) {
    photoInput.addEventListener('change', (e) => {
      const file = e.target.files[0];

      if (!file) {
        previewContainer.classList.add('d-none');
        previewImg.src = '#';
        return;
      }

      // Max size limit: 5MB
      const maxSizeBytes = 5 * 1024 * 1024;
      if (file.size > maxSizeBytes) {
        alert('File size exceeds the 5MB limit. Please select a smaller photo.');
        photoInput.value = '';
        previewContainer.classList.add('d-none');
        previewImg.src = '#';
        return;
      }

      // Allowed MIME types
      const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!validTypes.includes(file.type)) {
        alert('Invalid file format. Only JPG, PNG, and WEBP images are allowed.');
        photoInput.value = '';
        previewContainer.classList.add('d-none');
        previewImg.src = '#';
        return;
      }

      // Render image preview
      const objectUrl = URL.createObjectURL(file);
      previewImg.src = objectUrl;
      previewContainer.classList.remove('d-none');
    });
  }

  // --------------------------------------------------------------------------
  // 4. Client-Side Required Field Validation
  // Targets: login.php, register.php, report forms
  // --------------------------------------------------------------------------
  const formsToValidate = document.querySelectorAll('form[data-validate]');
  formsToValidate.forEach((form) => {
    form.addEventListener('submit', (e) => {
      let isFormValid = true;
      const requiredFields = form.querySelectorAll('[required]');

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          field.classList.add('is-invalid');
          isFormValid = false;
        } else {
          field.classList.remove('is-invalid');
        }
      });

      if (!isFormValid) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });

  // --------------------------------------------------------------------------
  // 5. Live Card Filter / Search (Index and Feed Listings)
  // Targets: index.php
  // --------------------------------------------------------------------------
  const searchInput = document.getElementById('petSearchInput');
  const petCards = document.querySelectorAll('.pet-card-item');

  if (searchInput && petCards.length > 0) {
    searchInput.addEventListener('input', (e) => {
      const term = e.target.value.toLowerCase().trim();

      petCards.forEach((card) => {
        const textContent = card.textContent.toLowerCase();
        if (textContent.includes(term)) {
          card.classList.remove('d-none');
        } else {
          card.classList.add('d-none');
        }
      });
    });
  }

  // --------------------------------------------------------------------------
  // 6. Global Confirmation Handler
  // Targets: views/admin/tab_users.php, views/my_reports/tab_lost.php, etc.
  // --------------------------------------------------------------------------
  document.addEventListener('submit', (e) => {
    const form = e.target.closest('form');
    if (!form) return;

    const confirmMessage = form.getAttribute('data-confirm');
    if (confirmMessage) {
      if (!window.confirm(confirmMessage)) {
        e.preventDefault();
        e.stopPropagation();
      }
    }
  });

  // --------------------------------------------------------------------------
  // 7. Admin User Directory Live Table Filters
  // Targets: views/admin/tab_users.php
  // --------------------------------------------------------------------------
  function attachTableFilter(inputId, tableId, noMatchId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const noMatchRow = document.getElementById(noMatchId);
    if (!input || !table) return;

    input.addEventListener('input', function () {
      const filter = this.value.trim().toLowerCase();
      const rows = table.querySelectorAll('tbody tr.user-row');
      let visibleCount = 0;

      rows.forEach((row) => {
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

  // --------------------------------------------------------------------------
  // 8. Admin Search Input Clear Button Delegation
  // Targets: views/admin/tab_users.php
  // --------------------------------------------------------------------------
  document.addEventListener('click', (e) => {
    const clearBtn = e.target.closest('[data-clear-target]');
    if (!clearBtn) return;

    const targetInputId = clearBtn.getAttribute('data-clear-target');
    const input = document.getElementById(targetInputId);
    if (input) {
      input.value = '';
      input.dispatchEvent(new Event('input'));
      input.focus();
    }
  });

});