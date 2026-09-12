/**
 * Lab Automation System — Core UI JavaScript
 * Interactions: theme toggle, sidebar, dropdowns, search, toasts,
 * tabs, form validation, table sorting/filtering/pagination, notifications.
 *
 * @author Arsalan
 */
(function () {
  'use strict';

  var root = document.documentElement;

  // =========================================================================
  // THEME
  // =========================================================================
  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem('lab_theme', theme);
    var label = document.getElementById('themeLabel');
    if (label) label.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
  }
  var stored = localStorage.getItem('lab_theme');
  if (stored) applyTheme(stored);
  else {
    var label = document.getElementById('themeLabel');
    if (label) label.textContent = root.getAttribute('data-theme') === 'dark' ? 'Light mode' : 'Dark mode';
  }

  // =========================================================================
  // SIDEBAR
  // =========================================================================
  if (localStorage.getItem('lab_sidebar') === 'collapsed') {
    document.body.classList.add('sidebar-collapsed');
  }

  // =========================================================================
  // LOCAL STORAGE PERSISTENCE HELPERS
  // =========================================================================
  function saveToStorage(key, data) {
    try { localStorage.setItem('lab_' + key, JSON.stringify(data)); } catch(e) {}
  }
  function loadFromStorage(key) {
    try { var d = localStorage.getItem('lab_' + key); return d ? JSON.parse(d) : null; } catch(e) { return null; }
  }

  // =========================================================================
  // GLOBAL CLICK DELEGATION
  // =========================================================================
  document.addEventListener('click', function (e) {
    // Theme toggle
    if (e.target.closest('#themeToggle')) {
      var cur = root.getAttribute('data-theme') || 'dark';
      applyTheme(cur === 'dark' ? 'light' : 'dark');
      return;
    }

    // Sidebar collapse
    if (e.target.closest('#sidebarCollapseToggle')) {
      document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem('lab_sidebar',
        document.body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
      return;
    }

    // Mobile sidebar toggle
    if (e.target.closest('#sidebarToggle')) {
      document.body.classList.toggle('sidebar-open');
      return;
    }

    // Dropdown triggers
    var trigger = e.target.closest('[data-dropdown]');
    if (trigger) {
      e.stopPropagation();
      var name = trigger.getAttribute('data-dropdown');
      var menuId = name === 'user-menu' ? 'userMenu' : name === 'notifications' ? 'notifMenu' : null;
      if (menuId) {
        var menu = document.getElementById(menuId);
        var isOpen = menu && menu.classList.contains('show');
        closeAllDropdowns();
        if (menu && !isOpen) menu.classList.add('show');
        return;
      }
    }

    // Tab switching
    var tab = e.target.closest('[data-tab]');
    if (tab) {
      e.preventDefault();
      activateTab(tab);
      return;
    }

    // Toast dismiss
    if (e.target.closest('.toast-close')) {
      var toast = e.target.closest('.toast');
      if (toast) dismissToast(toast);
      return;
    }

    // Modal backdrop click
    if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
      e.target.hidden = true;
      return;
    }

    // Notification item click
    var notifItem = e.target.closest('.notif-item[data-notif-id]');
    if (notifItem) {
      e.preventDefault();
      e.stopPropagation();
      markNotifRead(notifItem);
      var href = notifItem.getAttribute('data-href');
      if (href && href !== '#') {
        setTimeout(function() { window.location.href = href; }, 100);
      }
      return;
    }

    // Mark all notifications read
    if (e.target.closest('#markAllRead')) {
      e.preventDefault();
      e.stopPropagation();
      markAllNotifsRead();
      return;
    }

    // Confirmation buttons
    var confirmBtn = e.target.closest('[data-confirm]');
    if (confirmBtn) {
      e.preventDefault();
      var msg = confirmBtn.getAttribute('data-confirm');
      var successMsg = confirmBtn.getAttribute('data-confirm-success') || 'Action completed successfully.';
      var removeTarget = confirmBtn.getAttribute('data-confirm-remove');
      showConfirmModal(msg, function() {
        if (removeTarget) {
          var elToRemove = confirmBtn.closest(removeTarget);
          if (elToRemove) {
            elToRemove.style.transition = 'opacity 0.3s, transform 0.3s';
            elToRemove.style.opacity = '0';
            elToRemove.style.transform = 'translateX(20px)';
            setTimeout(function() { elToRemove.remove(); }, 300);
          }
        }
        window.showToast(successMsg, 'success');
      });
      return;
    }

    // CSV export
    var exportBtn = e.target.closest('[data-export-csv]');
    if (exportBtn) {
      e.preventDefault();
      var tableId = exportBtn.getAttribute('data-export-csv');
      exportTableCSV(tableId || null);
      return;
    }

    // Close sidebar on outside click (mobile)
    var sidebar = document.getElementById('appSidebar');
    if (document.body.classList.contains('sidebar-open') &&
        sidebar && !sidebar.contains(e.target) &&
        !e.target.closest('#sidebarToggle')) {
      document.body.classList.remove('sidebar-open');
    }

    closeAllDropdowns();
  });

  function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu.show').forEach(function (m) {
      m.classList.remove('show');
    });
  }

  // =========================================================================
  // TABS
  // =========================================================================
  function activateTab(tab) {
    var group = tab.closest('.tabs');
    if (!group) return;

    group.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
    tab.classList.add('active');

    var target = tab.getAttribute('data-tab');
    var container = group.parentElement;
    if (!container) return;

    // Panel-based tabs
    var panels = container.querySelectorAll('.tab-panel');
    if (panels.length > 0) {
      panels.forEach(function(p) { p.classList.remove('active'); });
      var panel = container.querySelector('#' + target);
      if (panel) panel.classList.add('active');
    }

    // Filter-based tabs (for tables)
    var filterValue = tab.getAttribute('data-filter');
    if (filterValue !== null) {
      filterTableByTab(container, filterValue);
    }
  }

  function filterTableByTab(container, filterValue) {
    var table = container.querySelector('.data-table');
    if (!table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var rows = tbody.querySelectorAll('tr');
    var visibleCount = 0;

    rows.forEach(function(row) {
      if (filterValue === '' || filterValue === 'all') {
        row.style.display = '';
        visibleCount++;
      } else {
        var badge = row.querySelector('.status-badge');
        var rowStatus = badge ? badge.textContent.trim().toLowerCase() : '';
        var match = rowStatus.indexOf(filterValue.toLowerCase()) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
      }
    });

    updateEmptyState(table, visibleCount);
    updatePagination(container, tbody);
  }

  // =========================================================================
  // SEARCH MODAL (Ctrl+K)
  // =========================================================================
  var searchModal = document.getElementById('searchModal');
  var searchInput = document.getElementById('searchInput');
  var searchResults = document.getElementById('searchResults');
  var searchSelectedIdx = -1;

  function openSearch() {
    if (searchModal) {
      searchModal.hidden = false;
      if (searchInput) { searchInput.value = ''; searchInput.focus(); }
      if (searchResults) searchResults.innerHTML = '<div class="search-empty"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.4"><circle cx="9" cy="9" r="5.5"/><line x1="13" y1="13" x2="17" y2="17"/></svg><span>Type to search across all modules</span></div>';
      searchSelectedIdx = -1;
    }
  }

  function closeSearch() {
    if (searchModal) { searchModal.hidden = true; }
  }

  var searchTrigger = document.getElementById('searchTrigger');
  if (searchTrigger) searchTrigger.addEventListener('click', openSearch);
  if (searchModal) searchModal.addEventListener('click', function(e) { if (e.target === searchModal) closeSearch(); });

  // Live search
  if (searchInput) {
    searchInput.addEventListener('input', debounce(function() {
      var query = searchInput.value.trim().toLowerCase();
      if (query.length < 2) {
        if (searchResults) searchResults.innerHTML = '<div class="search-empty"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.4"><circle cx="9" cy="9" r="5.5"/><line x1="13" y1="13" x2="17" y2="17"/></svg><span>Type at least 2 characters to search</span></div>';
        return;
      }
      performSearch(query);
    }, 200));

    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        navigateSearchResults(e.key === 'ArrowDown' ? 1 : -1);
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        var active = searchResults && searchResults.querySelector('.search-result-item.active');
        if (active) {
          var href = active.getAttribute('data-href');
          if (href) window.location.href = href;
        }
      }
    });
  }

  function performSearch(query) {
    if (!searchResults) return;

    var results = [];

    // Search navigation items
    var navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    navLinks.forEach(function(link) {
      var text = link.textContent.trim().toLowerCase();
      if (text.indexOf(query) !== -1) {
        results.push({
          type: 'page',
          title: link.textContent.trim(),
          subtitle: 'Navigation',
          href: link.href,
          icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="3" rx="1"/><rect x="2" y="9" width="5" height="3" rx="1"/><rect x="9" y="7" width="5" height="5" rx="1"/></svg>'
        });
      }
    });

    // Search data table rows on current page
    var tables = document.querySelectorAll('.data-table tbody');
    tables.forEach(function(tbody) {
      var rows = tbody.querySelectorAll('tr');
      rows.forEach(function(row) {
        var text = row.textContent.trim().toLowerCase();
        if (text.indexOf(query) !== -1) {
          var link = row.querySelector('a');
          var firstCell = row.querySelector('td');
          var title = firstCell ? firstCell.textContent.trim() : 'Record';
          var secondCell = row.querySelectorAll('td')[1];
          var subtitle = secondCell ? secondCell.textContent.trim() : '';
          results.push({
            type: 'record',
            title: title.substring(0, 60),
            subtitle: subtitle.substring(0, 60),
            href: link ? link.href : '#',
            icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10 2H4a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1V5l-3-3z"/><polyline points="10,2 10,5 13,5"/></svg>'
          });
        }
      });
    });

    // Quick action results
    var quickActions = [
      { title: 'Register New Product', subtitle: 'Products → Register', href: getBaseUrl() + '/products/create.php', icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>' },
      { title: 'View All Products', subtitle: 'Products', href: getBaseUrl() + '/products/index.php', icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 2L14 5.5v5L8 14 2 10.5v-5L8 2z"/></svg>' },
      { title: 'Testing Records', subtitle: 'View all testing records', href: getBaseUrl() + '/testing/index.php', icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M5.5 1.5v4l-3 5.5a1.2 1.2 0 001 1.7h9a1.2 1.2 0 001-1.7l-3-5.5v-4"/></svg>' },
      { title: 'Reports & Analytics', subtitle: 'View reports', href: getBaseUrl() + '/reports/index.php', icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="9" width="3" height="5"/><rect x="6.5" y="5" width="3" height="9"/><rect x="11" y="2" width="3" height="12"/></svg>' },
      { title: 'Departments', subtitle: 'Configuration', href: getBaseUrl() + '/admin/departments.php', icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1.5" y="5.5" width="5" height="5" rx="1"/><rect x="9.5" y="5.5" width="5" height="5" rx="1"/><rect x="5.5" y="1.5" width="5" height="5" rx="1"/></svg>' },
      { title: 'Settings', subtitle: 'System configuration', href: getBaseUrl() + '/admin/settings.php', icon: '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="2"/><path d="M8 2v2M8 12v2M2 8h2M12 8h2"/></svg>' },
    ];

    quickActions.forEach(function(qa) {
      if (qa.title.toLowerCase().indexOf(query) !== -1 ||
          qa.subtitle.toLowerCase().indexOf(query) !== -1) {
        results.push({ type: 'action', title: qa.title, subtitle: qa.subtitle, href: qa.href, icon: qa.icon });
      }
    });

    // Render results
    if (results.length === 0) {
      searchResults.innerHTML = '<div class="search-empty"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.4"><circle cx="9" cy="9" r="5.5"/><line x1="13" y1="13" x2="17" y2="17"/></svg><span>No results for &ldquo;' + escHtml(query) + '&rdquo;</span></div>';
      return;
    }

    var html = '';
    var groupedTypes = {};
    results.slice(0, 10).forEach(function(r) {
      var typeLabel = r.type === 'page' ? 'Pages' : r.type === 'action' ? 'Quick Actions' : 'Records';
      if (!groupedTypes[typeLabel]) { groupedTypes[typeLabel] = []; }
      groupedTypes[typeLabel].push(r);
    });

    Object.keys(groupedTypes).forEach(function(group) {
      html += '<div class="search-result-group">' + escHtml(group) + '</div>';
      groupedTypes[group].forEach(function(r) {
        html += '<a class="search-result-item" data-href="' + escHtml(r.href) + '" href="' + escHtml(r.href) + '">' +
          '<span class="search-result-icon">' + r.icon + '</span>' +
          '<span class="search-result-text"><span class="search-result-title">' + highlightMatch(escHtml(r.title), query) + '</span>' +
          '<span class="search-result-subtitle">' + escHtml(r.subtitle) + '</span></span>' +
          '<kbd class="search-result-hint">↵</kbd></a>';
      });
    });

    searchResults.innerHTML = html;
    searchSelectedIdx = -1;
  }

  function navigateSearchResults(dir) {
    if (!searchResults) return;
    var items = searchResults.querySelectorAll('.search-result-item');
    if (!items.length) return;
    items.forEach(function(item) { item.classList.remove('active'); });
    searchSelectedIdx += dir;
    if (searchSelectedIdx < 0) searchSelectedIdx = items.length - 1;
    if (searchSelectedIdx >= items.length) searchSelectedIdx = 0;
    items[searchSelectedIdx].classList.add('active');
    items[searchSelectedIdx].scrollIntoView({ block: 'nearest' });
  }

  function highlightMatch(text, query) {
    var idx = text.toLowerCase().indexOf(query.toLowerCase());
    if (idx === -1) return text;
    return text.substring(0, idx) + '<mark>' + text.substring(idx, idx + query.length) + '</mark>' + text.substring(idx + query.length);
  }

  // =========================================================================
  // KEYBOARD SHORTCUTS
  // =========================================================================
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      searchModal && !searchModal.hidden ? closeSearch() : openSearch();
    }
    if (e.key === 'Escape') {
      closeSearch();
      closeAllDropdowns();
      document.querySelectorAll('.modal-backdrop:not([hidden])').forEach(function(m) { m.hidden = true; });
    }
  });

  // =========================================================================
  // TOASTS
  // =========================================================================
  function dismissToast(toast) {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 300ms var(--ease-out)';
    setTimeout(function () { toast.remove(); }, 300);
  }

  document.querySelectorAll('.toast').forEach(function (toast) {
    setTimeout(function () { if (toast.parentElement) dismissToast(toast); }, 5000);
  });

  window.showToast = function(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) return;
    var icons = {
      success: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="7" opacity="0.2" fill="currentColor"/><polyline points="5.5,9 8,11.5 12.5,6.5"/></svg>',
      error: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="7" opacity="0.2" fill="currentColor"/><line x1="6.5" y1="6.5" x2="11.5" y2="11.5"/><line x1="11.5" y1="6.5" x2="6.5" y2="11.5"/></svg>',
      warning: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 2L16 15H2L9 2z" opacity="0.2" fill="currentColor"/><line x1="9" y1="7" x2="9" y2="10"/><circle cx="9" cy="12" r="0.5" fill="currentColor"/></svg>',
      info: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="7" opacity="0.2" fill="currentColor"/><line x1="9" y1="8" x2="9" y2="12"/><circle cx="9" cy="6" r="0.5" fill="currentColor"/></svg>'
    };
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = (icons[type] || icons.success) + '<span>' + escHtml(message) + '</span><button type="button" class="toast-close" aria-label="Dismiss">&times;</button>';
    container.appendChild(toast);
    requestAnimationFrame(function() { toast.classList.add('toast-enter'); });
    setTimeout(function() { if (toast.parentElement) dismissToast(toast); }, 5000);
  };

  // =========================================================================
  // MODALS
  // =========================================================================
  window.openModal = function(id) {
    var m = document.getElementById(id);
    if (m) {
      m.hidden = false;
      var first = m.querySelector('input:not([type=hidden]):not([disabled]),select:not([disabled]),textarea:not([disabled])');
      if (first) setTimeout(function() { first.focus(); }, 100);
    }
  };

  window.closeModal = function(id) {
    var m = document.getElementById(id);
    if (m) {
      m.hidden = true;
      m.querySelectorAll('.is-invalid,.is-valid').forEach(function(el) {
        el.classList.remove('is-invalid', 'is-valid');
      });
      m.querySelectorAll('.form-error').forEach(function(el) { el.remove(); });
    }
  };

  // Confirmation modal
  function showConfirmModal(message, onConfirm) {
    var existing = document.getElementById('confirmModal');
    if (existing) existing.remove();

    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.id = 'confirmModal';
    backdrop.innerHTML =
      '<div class="modal" style="max-width:420px;">' +
        '<div class="modal-header">' +
          '<h3 class="modal-title">Confirm Action</h3>' +
          '<button class="modal-close" type="button">&times;</button>' +
        '</div>' +
        '<div class="modal-body">' +
          '<div class="d-flex align-center gap-12" style="margin-bottom:4px;">' +
            '<div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--warning-tint);color:var(--warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
              '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10 2L18 16H2L10 2z"/><line x1="10" y1="8" x2="10" y2="11"/><circle cx="10" cy="13.5" r="0.5" fill="currentColor"/></svg>' +
            '</div>' +
            '<p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin:0;">' + escHtml(message) + '</p>' +
          '</div>' +
        '</div>' +
        '<div class="modal-footer">' +
          '<button class="btn btn-secondary" type="button" id="confirmCancel">Cancel</button>' +
          '<button class="btn btn-danger" type="button" id="confirmOk">Confirm</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(backdrop);

    backdrop.querySelector('.modal-close').addEventListener('click', function() { backdrop.remove(); });
    backdrop.querySelector('#confirmCancel').addEventListener('click', function() { backdrop.remove(); });
    backdrop.querySelector('#confirmOk').addEventListener('click', function() {
      backdrop.remove();
      if (onConfirm) onConfirm();
    });
    backdrop.addEventListener('click', function(e) { if (e.target === backdrop) backdrop.remove(); });
  }

  // =========================================================================
  // NOTIFICATIONS
  // =========================================================================
  function sendNotifAction(fd) {
    var url = BASE_URL + '/api/notifications.php';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) fd.append('csrf_token', csrfMeta.getAttribute('content'));
    if (navigator.sendBeacon) {
      navigator.sendBeacon(url, fd);
    } else {
      fetch(url, { method: 'POST', body: fd, keepalive: true });
    }
  }

  function markNotifRead(item) {
    if (!item.classList.contains('notif-unread')) return;
    item.classList.remove('notif-unread');
    var dot = item.querySelector('.notif-dot');
    if (dot) dot.remove();
    updateNotifCount();

    var id = item.getAttribute('data-notif-id');
    if (id) {
      var fd = new FormData();
      fd.append('action', 'mark_read');
      fd.append('id', id);
      sendNotifAction(fd);
    }
  }

  function markAllNotifsRead() {
    var items = document.querySelectorAll('.notif-item.notif-unread');
    if (items.length === 0) {
      window.showToast('All notifications are already read.', 'info');
      return;
    }
    items.forEach(function(item) {
      item.classList.remove('notif-unread');
      var dot = item.querySelector('.notif-dot');
      if (dot) dot.remove();
    });
    updateNotifCount();
    var fd = new FormData();
    fd.append('action', 'mark_all_read');
    sendNotifAction(fd);
    window.showToast('All notifications marked as read.', 'success');
  }

  function updateNotifCount() {
    var unread = document.querySelectorAll('.notif-item.notif-unread').length;
    var badge = document.getElementById('notifBadge');
    if (badge) {
      badge.textContent = unread;
      badge.style.display = unread > 0 ? '' : 'none';
    }
  }

  // No localStorage restore needed — read state is persisted server-side

  // =========================================================================
  // TABLE SORTING
  // =========================================================================
  document.querySelectorAll('.data-table').forEach(function(table) {
    var headers = table.querySelectorAll('thead .sortable');
    headers.forEach(function(th, colIdx) {
      th.style.cursor = 'pointer';
      th.style.userSelect = 'none';
      th.addEventListener('click', function() {
        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        var actualIdx = Array.prototype.indexOf.call(th.parentElement.children, th);
        var dir = th.getAttribute('data-sort') === 'asc' ? 'desc' : 'asc';

        headers.forEach(function(h) {
          if (h !== th) h.removeAttribute('data-sort');
        });
        th.setAttribute('data-sort', dir);

        var rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function(a, b) {
          var aCell = a.children[actualIdx];
          var bCell = b.children[actualIdx];
          if (!aCell || !bCell) return 0;

          var aVal = (aCell.getAttribute('data-sort-value') || aCell.textContent).trim();
          var bVal = (bCell.getAttribute('data-sort-value') || bCell.textContent).trim();

          var aNum = parseFloat(aVal.replace(/[^0-9.\-]/g, ''));
          var bNum = parseFloat(bVal.replace(/[^0-9.\-]/g, ''));
          if (!isNaN(aNum) && !isNaN(bNum)) {
            return dir === 'asc' ? aNum - bNum : bNum - aNum;
          }

          var aDate = Date.parse(aVal);
          var bDate = Date.parse(bVal);
          if (!isNaN(aDate) && !isNaN(bDate)) {
            return dir === 'asc' ? aDate - bDate : bDate - aDate;
          }

          var cmp = aVal.localeCompare(bVal, undefined, { sensitivity: 'base' });
          return dir === 'asc' ? cmp : -cmp;
        });

        rows.forEach(function(row) { tbody.appendChild(row); });
      });
    });
  });

  // =========================================================================
  // TABLE SEARCH & FILTERING
  // =========================================================================
  document.querySelectorAll('.filter-bar').forEach(function(filterBar) {
    var container = filterBar.closest('.app-content') || filterBar.parentElement;
    var table = container ? container.querySelector('.data-table') : null;
    if (!table) return;

    var filterSearchInput = filterBar.querySelector('.filter-search input');
    var selectFilters = filterBar.querySelectorAll('select.form-control');

    function applyFilters() {
      var tbody = table.querySelector('tbody');
      if (!tbody) return;

      var query = filterSearchInput ? filterSearchInput.value.trim().toLowerCase() : '';
      var rows = tbody.querySelectorAll('tr');
      var visibleCount = 0;

      rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var matchesSearch = !query || text.indexOf(query) !== -1;

        var matchesFilters = true;
        selectFilters.forEach(function(sel) {
          if (sel.value) {
            var filterText = sel.value.toLowerCase();
            if (text.indexOf(filterText) === -1) {
              matchesFilters = false;
            }
          }
        });

        var show = matchesSearch && matchesFilters;
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });

      updateEmptyState(table, visibleCount);
      updatePagination(container, tbody);
    }

    if (filterSearchInput) {
      filterSearchInput.addEventListener('input', debounce(applyFilters, 250));
    }
    selectFilters.forEach(function(sel) {
      sel.addEventListener('change', applyFilters);
    });
  });

  // =========================================================================
  // EMPTY STATES
  // =========================================================================
  function updateEmptyState(table, visibleCount) {
    var wrapper = table.closest('.table-wrap') || table.parentElement;
    var existing = wrapper.querySelector('.table-empty-state');

    if (visibleCount === 0) {
      if (!existing) {
        var emptyEl = document.createElement('div');
        emptyEl.className = 'table-empty-state';
        emptyEl.innerHTML =
          '<div class="empty-state">' +
            '<svg width="48" height="48" viewBox="0 0 48 48" fill="none" stroke="var(--ink-faint)" stroke-width="1.5" stroke-linecap="round" opacity="0.5">' +
              '<circle cx="22" cy="22" r="14"/><line x1="32" y1="32" x2="42" y2="42" stroke-width="2.5"/>' +
              '<path d="M18 20h8M18 24h5"/>' +
            '</svg>' +
            '<p class="empty-state-title">No results found</p>' +
            '<p class="empty-state-text">Try adjusting your search or filter criteria.</p>' +
          '</div>';
        wrapper.appendChild(emptyEl);
      }
      existing = wrapper.querySelector('.table-empty-state');
      if (existing) existing.style.display = '';
      table.style.display = 'none';
    } else {
      if (existing) existing.style.display = 'none';
      table.style.display = '';
    }
  }

  // =========================================================================
  // CLIENT-SIDE PAGINATION
  // =========================================================================
  var ROWS_PER_PAGE = 10;

  function initPagination() {
    document.querySelectorAll('.pagination-wrap').forEach(function(paginationNav) {
      var container = paginationNav.closest('.app-content') || paginationNav.parentElement;
      var table = container ? container.querySelector('.data-table') : null;
      if (!table) return;
      var tbody = table.querySelector('tbody');
      if (!tbody) return;

      var dataset = paginationNav.dataset;
      if (!dataset.initialized) {
        dataset.initialized = 'true';
        dataset.currentPage = '1';
        updatePagination(container, tbody);
      }
    });
  }

  function updatePagination(container, tbody) {
    var paginationNav = container.querySelector('.pagination-wrap');
    if (!paginationNav) return;

    var allRows = tbody.querySelectorAll('tr');
    var visibleRows = [];
    allRows.forEach(function(r) {
      if (r.style.display !== 'none') visibleRows.push(r);
    });

    var totalPages = Math.max(1, Math.ceil(visibleRows.length / ROWS_PER_PAGE));
    var currentPage = parseInt(paginationNav.dataset.currentPage || '1', 10);
    if (currentPage > totalPages) currentPage = totalPages;
    paginationNav.dataset.currentPage = currentPage;

    visibleRows.forEach(function(row, idx) {
      var pageStart = (currentPage - 1) * ROWS_PER_PAGE;
      var pageEnd = pageStart + ROWS_PER_PAGE;
      row.style.display = (idx >= pageStart && idx < pageEnd) ? '' : 'none';
    });

    renderPaginationControls(paginationNav, currentPage, totalPages, visibleRows.length, container, tbody);
  }

  function renderPaginationControls(nav, current, total, count, container, tbody) {
    var html = '<ul class="pagination">';
    html += '<li class="page-item' + (current <= 1 ? ' disabled' : '') + '">' +
            '<a class="page-link" data-page="' + (current - 1) + '">&laquo;</a></li>';

    var start = Math.max(1, current - 2);
    var end = Math.min(total, current + 2);

    if (start > 1) {
      html += '<li class="page-item"><a class="page-link" data-page="1">1</a></li>';
      if (start > 2) html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
    }

    for (var i = start; i <= end; i++) {
      html += '<li class="page-item' + (i === current ? ' active' : '') + '">' +
              '<a class="page-link" data-page="' + i + '">' + i + '</a></li>';
    }

    if (end < total) {
      if (end < total - 1) html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
      html += '<li class="page-item"><a class="page-link" data-page="' + total + '">' + total + '</a></li>';
    }

    html += '<li class="page-item' + (current >= total ? ' disabled' : '') + '">' +
            '<a class="page-link" data-page="' + (current + 1) + '">&raquo;</a></li>';
    html += '</ul>';

    var fromRow = count > 0 ? (current - 1) * ROWS_PER_PAGE + 1 : 0;
    var toRow = Math.min(current * ROWS_PER_PAGE, count);
    html += '<span class="pagination-info">Showing ' + fromRow + '–' + toRow + ' of ' + count + '</span>';

    nav.innerHTML = html;

    nav.querySelectorAll('.page-link[data-page]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        var page = parseInt(link.getAttribute('data-page'), 10);
        if (page < 1 || page > total) return;
        nav.dataset.currentPage = page;
        updatePagination(container, tbody);
        var tableEl = container.querySelector('.data-table');
        if (tableEl) tableEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  // =========================================================================
  // CSV EXPORT
  // =========================================================================
  function exportTableCSV(tableSelector) {
    var table = tableSelector
      ? document.querySelector(tableSelector)
      : document.querySelector('.data-table');
    if (!table) {
      window.showToast('No table found to export.', 'error');
      return;
    }

    var rows = [];
    var headers = [];
    table.querySelectorAll('thead th').forEach(function(th) {
      var text = th.textContent.trim();
      if (!th.classList.contains('col-actions') && text) headers.push('"' + text.replace(/"/g, '""') + '"');
    });
    rows.push(headers.join(','));

    table.querySelectorAll('tbody tr').forEach(function(tr) {
      if (tr.style.display === 'none') return;
      var cells = [];
      var tds = tr.querySelectorAll('td');
      tds.forEach(function(td, idx) {
        if (idx < headers.length) {
          var text = td.textContent.trim().replace(/\s+/g, ' ');
          cells.push('"' + text.replace(/"/g, '""') + '"');
        }
      });
      rows.push(cells.join(','));
    });

    var csv = rows.join('\n');
    var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'export_' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    window.showToast('CSV exported successfully.', 'success');
  }

  // =========================================================================
  // FORM VALIDATION
  // =========================================================================
  var validators = {
    required: function(v) { return v.trim().length > 0 ? '' : 'This field is required.'; },
    email: function(v) {
      if (!v.trim()) return '';
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? '' : 'Please enter a valid email address.';
    },
    minlength: function(v, len) { return v.length >= parseInt(len) ? '' : 'Must be at least ' + len + ' characters.'; },
    maxlength: function(v, len) { return v.length <= parseInt(len) ? '' : 'Must be no more than ' + len + ' characters.'; },
    numeric: function(v) { return v === '' || /^-?\d*\.?\d+$/.test(v) ? '' : 'Please enter a valid number.'; },
    alpha_space: function(v) {
      if (!v.trim()) return '';
      return /^[A-Za-z\s.\-']+$/.test(v) ? '' : 'Only letters, spaces, hyphens, and periods are allowed.';
    },
    username: function(v) { return /^[A-Za-z0-9_.]{4,30}$/.test(v) ? '' : 'Must be 4–30 characters: letters, numbers, underscore, dot.'; },
    serial: function(v) {
      if (!v.trim()) return '';
      if (!/^[A-Z]{2,4}-\d{4}-\d{3,5}$/.test(v)) return 'Format: XX-YYYY-NNNN (e.g. FZ-2026-0451).';
      return '';
    },
    product_code: function(v) {
      if (!v.trim()) return '';
      if (!/^[A-Z0-9\-]{3,20}$/.test(v)) return 'Uppercase letters, numbers, and hyphens only (3–20 chars).';
      return '';
    },
    password: function(v) {
      if (v.length < 8) return 'Minimum 8 characters required.';
      if (!/[A-Z]/.test(v)) return 'Must include at least one uppercase letter.';
      if (!/[a-z]/.test(v)) return 'Must include at least one lowercase letter.';
      if (!/[0-9]/.test(v)) return 'Must include at least one number.';
      if (!/[^A-Za-z0-9]/.test(v)) return 'Must include at least one special character.';
      return '';
    },
    match: function(v, fieldName) {
      var other = document.getElementById(fieldName) || document.querySelector('[name="' + fieldName + '"]');
      return other && v === other.value ? '' : 'Passwords do not match.';
    },
    date_not_future: function(v) {
      if (!v) return '';
      return new Date(v) <= new Date() ? '' : 'Date cannot be in the future.';
    },
    date_valid: function(v) {
      if (!v) return '';
      var d = new Date(v);
      return !isNaN(d.getTime()) ? '' : 'Please enter a valid date.';
    },
    file_type: function(input) {
      if (!input.files || !input.files.length) return '';
      var allowed = (input.getAttribute('data-allowed') || 'pdf,png,jpg,jpeg').split(',');
      for (var i = 0; i < input.files.length; i++) {
        var ext = input.files[i].name.split('.').pop().toLowerCase();
        if (allowed.indexOf(ext) === -1) return 'Allowed formats: ' + allowed.join(', ').toUpperCase() + '.';
      }
      return '';
    },
    file_size: function(input) {
      if (!input.files || !input.files.length) return '';
      var max = parseInt(input.getAttribute('data-max-size') || '5242880', 10);
      for (var i = 0; i < input.files.length; i++) {
        if (input.files[i].size > max) return 'Maximum file size is ' + Math.round(max / 1048576) + ' MB.';
      }
      return '';
    }
  };

  function validateField(input) {
    var rules = (input.getAttribute('data-validate') || '').split('|').filter(Boolean);
    var value = input.value || '';
    var error = '';

    for (var i = 0; i < rules.length; i++) {
      var parts = rules[i].split(':');
      var rule = parts[0];
      var param = parts[1];

      if (rule === 'file_type' || rule === 'file_size') {
        error = validators[rule](input);
      } else if (validators[rule]) {
        error = validators[rule](value, param);
      }
      if (error) break;
    }

    setFieldState(input, error);
    return error === '';
  }

  function setFieldState(input, error) {
    var group = input.closest('.form-group') || input.parentElement;
    if (!group) return;

    input.classList.remove('is-invalid', 'is-valid');
    var existing = group.querySelector('.form-error');
    if (existing) existing.remove();

    if (error) {
      input.classList.add('is-invalid');
      var errorEl = document.createElement('div');
      errorEl.className = 'form-error';
      errorEl.innerHTML = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6" cy="6" r="5"/><line x1="6" y1="4" x2="6" y2="6.5"/><circle cx="6" cy="8.5" r="0.4" fill="currentColor"/></svg>' + escHtml(error);
      input.parentElement.appendChild(errorEl);
    } else if (input.value && input.value.trim()) {
      input.classList.add('is-valid');
    }
  }

  // Validate on blur
  document.addEventListener('blur', function(e) {
    if (e.target.matches && e.target.matches('[data-validate]')) {
      validateField(e.target);
    }
  }, true);

  // Clear error on input
  document.addEventListener('input', function(e) {
    if (e.target.matches && e.target.matches('.is-invalid[data-validate]')) {
      var group = e.target.closest('.form-group') || e.target.parentElement;
      if (group) {
        var err = group.querySelector('.form-error');
        if (err) err.remove();
      }
      e.target.classList.remove('is-invalid');
    }
  });

  // Form submit validation + functional save
  document.addEventListener('submit', function(e) {
    var form = e.target;
    if (!form.matches || !form.matches('[data-validate-form]')) return;

    e.preventDefault();
    var fields = form.querySelectorAll('[data-validate]');
    var valid = true;
    fields.forEach(function(f) {
      if (!validateField(f)) valid = false;
    });

    if (!valid) {
      var firstErr = form.querySelector('.is-invalid');
      if (firstErr) firstErr.focus();
      window.showToast('Please fix the errors before submitting.', 'error');
      return;
    }

    // Collect form data
    var formData = {};
    var formInputs = form.querySelectorAll('input[name], select[name], textarea[name]');
    formInputs.forEach(function(input) {
      if (input.type === 'checkbox') {
        formData[input.name] = input.checked;
      } else {
        formData[input.name] = input.value;
      }
    });

    var formId = form.getAttribute('data-form-id');

    var submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
      var originalText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="animation:spin 0.8s linear infinite"><circle cx="7" cy="7" r="5" stroke-dasharray="20" stroke-dashoffset="6"/></svg> Saving…';
      submitBtn.style.minWidth = submitBtn.offsetWidth + 'px';

      setTimeout(function() {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        submitBtn.style.minWidth = '';

        // Persist to localStorage
        if (formId) {
          saveToStorage('form_' + formId, formData);
        }

        // Handle specific forms
        if (formId === 'profile_info') {
          var nameInput = form.querySelector('[name="full_name"]');
          if (nameInput && nameInput.value) {
            saveToStorage('user_name', nameInput.value);
            var avatarEls = document.querySelectorAll('.user-avatar');
            var parts = nameInput.value.trim().split(' ');
            var initials = parts.length > 1
              ? (parts[0][0] + parts[parts.length-1][0]).toUpperCase()
              : nameInput.value.substring(0,2).toUpperCase();
            avatarEls.forEach(function(el) {
              if (el.closest('.user-trigger') || el.closest('.profile-layout')) {
                el.textContent = initials;
              }
            });
            var nameDisplay = document.querySelector('.user-name');
            if (nameDisplay) nameDisplay.textContent = nameInput.value;
            var profileName = document.querySelector('.profile-layout h3');
            if (profileName) profileName.textContent = nameInput.value;
          }
          window.showToast('Profile updated successfully.', 'success');
        } else if (formId === 'profile_password') {
          form.reset();
          form.querySelectorAll('.is-valid').forEach(function(el) { el.classList.remove('is-valid'); });
          window.showToast('Password updated successfully.', 'success');
        } else if (formId === 'add_tester') {
          addTesterToDOM(formData);
          form.reset();
          form.querySelectorAll('.is-valid').forEach(function(el) { el.classList.remove('is-valid'); });
          var modal = form.closest('.modal-backdrop');
          if (modal) modal.hidden = true;
          window.showToast('Tester created successfully.', 'success');
        } else if (formId === 'add_dept') {
          addDepartmentToDOM(formData);
          form.reset();
          form.querySelectorAll('.is-valid').forEach(function(el) { el.classList.remove('is-valid'); });
          var modal2 = form.closest('.modal-backdrop');
          if (modal2) modal2.hidden = true;
          window.showToast('Department created successfully.', 'success');
        } else if (formId === 'add_testing_type') {
          form.reset();
          form.querySelectorAll('.is-valid').forEach(function(el) { el.classList.remove('is-valid'); });
          var modal3 = form.closest('.modal-backdrop');
          if (modal3) modal3.hidden = true;
          window.showToast('Testing type created successfully.', 'success');
        } else {
          window.showToast('Changes saved successfully.', 'success');
        }
      }, 800);
    } else {
      window.showToast('Changes saved successfully.', 'success');
    }
  });

  // Profile data is fetched from DB by PHP — no localStorage restore needed

  // =========================================================================
  // TESTER CRUD (client-side DOM manipulation)
  // =========================================================================
  function addTesterToDOM(data) {
    var grid = document.querySelector('.tester-cards-grid');
    if (!grid) return;

    var name = data.full_name || 'New Tester';
    var parts = name.trim().split(' ');
    var initials = parts.length > 1
      ? (parts[0][0] + parts[parts.length-1][0]).toUpperCase()
      : name.substring(0,2).toUpperCase();
    var roleLabel = data.role === 'testing_engineer' ? 'Testing Engineer' : 'Lab Technician';
    var deptSelect = document.querySelector('[data-form-id="add_tester"] [name="department_id"]');
    var dept = deptSelect ? (deptSelect.options[deptSelect.selectedIndex] ? deptSelect.options[deptSelect.selectedIndex].text : '—') : '—';
    var email = data.email || '';

    var card = document.createElement('div');
    card.className = 'card card-hover tester-card';
    card.innerHTML =
      '<div class="card-body" style="padding:20px;">' +
        '<div class="d-flex align-center gap-12" style="margin-bottom:16px;">' +
          '<span class="user-avatar" style="width:44px;height:44px;font-size:0.875rem;">' + escHtml(initials) + '</span>' +
          '<div style="flex:1;min-width:0;">' +
            '<div style="font-weight:600;font-size:0.9375rem;">' + escHtml(name) + '</div>' +
            '<div style="font-size:0.75rem;color:var(--ink-faint);">' + escHtml(roleLabel) + ' · ' + escHtml(dept) + '</div>' +
          '</div>' +
          '<span class="status-badge badge-success"><span class="status-dot"></span>Active</span>' +
        '</div>' +
        '<div class="text-mono" style="font-size:0.75rem;color:var(--ink-faint);margin-bottom:12px;">' + escHtml(email) + '</div>' +
        '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;padding-top:12px;border-top:1px solid var(--line);">' +
          '<div><div style="font-weight:700;font-size:1rem;font-variant-numeric:tabular-nums;">0</div><div style="font-size:0.6875rem;color:var(--ink-faint);">Active Tests</div></div>' +
          '<div><div style="font-weight:700;font-size:1rem;font-variant-numeric:tabular-nums;">0</div><div style="font-size:0.6875rem;color:var(--ink-faint);">Completed</div></div>' +
          '<div><div style="font-weight:700;font-size:1rem;color:var(--success);font-variant-numeric:tabular-nums;">—</div><div style="font-size:0.6875rem;color:var(--ink-faint);">Pass Rate</div></div>' +
        '</div>' +
      '</div>' +
      '<div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">' +
        '<button class="btn btn-sm btn-ghost" onclick="openModal(\'editTesterModal\')">Edit</button>' +
        '<button class="btn btn-sm btn-ghost" style="color:var(--fail);" data-confirm="Remove this tester from the system?" data-confirm-success="Tester removed successfully." data-confirm-remove=".tester-card">Remove</button>' +
      '</div>';
    grid.insertBefore(card, grid.firstChild);
  }

  function addDepartmentToDOM(data) {
    var grid = document.querySelector('.dept-cards-grid');
    if (!grid) return;

    var name = data.dept_name || 'New Department';
    var desc = data.description || '';

    var card = document.createElement('div');
    card.className = 'card card-hover dept-card';
    card.style.cursor = 'pointer';
    card.innerHTML =
      '<div class="card-body">' +
        '<div class="d-flex align-center justify-between" style="margin-bottom:16px;">' +
          '<div class="d-flex align-center gap-10">' +
            '<div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);color:var(--accent);display:flex;align-items:center;justify-content:center;">' +
              '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="7" width="6" height="6" rx="1"/><rect x="12" y="7" width="6" height="6" rx="1"/><rect x="7" y="2" width="6" height="6" rx="1"/></svg>' +
            '</div>' +
            '<div>' +
              '<h3 style="font-size:1rem;font-weight:600;margin:0;">' + escHtml(name) + '</h3>' +
              '<span class="text-soft" style="font-size:0.75rem;">' + escHtml(desc) + '</span>' +
            '</div>' +
          '</div>' +
          '<span class="status-badge badge-success"><span class="status-dot"></span>Active</span>' +
        '</div>' +
        '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;padding-top:16px;border-top:1px solid var(--line);">' +
          '<div><div style="font-family:var(--font-display);font-weight:700;font-size:1.25rem;color:var(--ink);">0</div><div style="font-size:0.6875rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.06em;">Testers</div></div>' +
          '<div><div style="font-family:var(--font-display);font-weight:700;font-size:1.25rem;color:var(--warning);">0</div><div style="font-size:0.6875rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.06em;">Active Tests</div></div>' +
          '<div><div style="font-family:var(--font-display);font-weight:700;font-size:1.25rem;color:var(--ink);">—</div><div style="font-size:0.6875rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.06em;">Avg Cycle</div></div>' +
        '</div>' +
      '</div>' +
      '<div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">' +
        '<button class="btn btn-sm btn-ghost" onclick="event.stopPropagation();openModal(\'editDeptModal\')">Edit</button>' +
        '<button class="btn btn-sm btn-ghost">View Testers</button>' +
      '</div>';
    grid.insertBefore(card, grid.firstChild);
  }

  // =========================================================================
  // SETTINGS SAVE/RESET (persist to localStorage)
  // =========================================================================
  function initSettingsSave() {
    document.querySelectorAll('.settings-save-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var card = btn.closest('.card');
        if (!card) return;

        var data = {};
        card.querySelectorAll('input, select, textarea').forEach(function(input) {
          var name = input.name || input.closest('.form-group,.form-row')?.querySelector('.form-label')?.textContent.trim().replace(/[^a-zA-Z]/g,'_').toLowerCase() || '';
          if (!name) return;
          if (input.type === 'checkbox') {
            data[name] = input.checked;
          } else {
            data[name] = input.value;
          }
        });

        var sectionTitle = card.querySelector('.card-title');
        var sectionName = sectionTitle ? sectionTitle.textContent.trim().replace(/\s+/g, '_').toLowerCase() : 'section';
        saveToStorage('settings_' + sectionName, data);

        var originalText = btn.textContent;
        btn.disabled = true;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="animation:spin 0.8s linear infinite"><circle cx="7" cy="7" r="5" stroke-dasharray="20" stroke-dashoffset="6"/></svg> Saving…';
        setTimeout(function() {
          btn.disabled = false;
          btn.textContent = originalText;
          window.showToast(sectionTitle ? sectionTitle.textContent.trim() + ' settings saved.' : 'Settings saved.', 'success');
        }, 600);
      });
    });
  }
  initSettingsSave();

  // =========================================================================
  // NOTIFICATION PREFERENCES (toggle persistence)
  // =========================================================================
  function initNotifPrefs() {
    var prefsBtn = document.getElementById('saveNotifPrefs');
    if (!prefsBtn) return;
    prefsBtn.addEventListener('click', function(e) {
      e.preventDefault();
      var card = prefsBtn.closest('.card');
      if (!card) return;

      var prefs = {};
      card.querySelectorAll('.toggle-switch input').forEach(function(cb, idx) {
        prefs['pref_' + idx] = cb.checked;
      });
      saveToStorage('notif_prefs', prefs);

      var originalText = prefsBtn.textContent;
      prefsBtn.disabled = true;
      prefsBtn.textContent = 'Saving…';
      setTimeout(function() {
        prefsBtn.disabled = false;
        prefsBtn.textContent = originalText;
        window.showToast('Notification preferences saved.', 'success');
      }, 600);
    });

    // Restore
    var saved = loadFromStorage('notif_prefs');
    if (saved) {
      var card = prefsBtn.closest('.card');
      if (card) {
        card.querySelectorAll('.toggle-switch input').forEach(function(cb, idx) {
          if (saved['pref_' + idx] !== undefined) cb.checked = saved['pref_' + idx];
        });
      }
    }
  }
  initNotifPrefs();

  // =========================================================================
  // ACTIVE SESSIONS (detect real browser)
  // =========================================================================
  function detectBrowser() {
    var ua = navigator.userAgent;
    var browser = 'Unknown Browser';
    var device = 'Unknown Device';
    var icon = 'desktop';

    if (ua.indexOf('Edg/') !== -1) browser = 'Edge';
    else if (ua.indexOf('OPR/') !== -1 || ua.indexOf('Opera') !== -1) browser = 'Opera';
    else if (ua.indexOf('Chrome') !== -1) browser = 'Chrome';
    else if (ua.indexOf('Firefox') !== -1) browser = 'Firefox';
    else if (ua.indexOf('Safari') !== -1) browser = 'Safari';

    if (ua.indexOf('Windows') !== -1) device = 'Windows';
    else if (ua.indexOf('Macintosh') !== -1 || ua.indexOf('Mac OS') !== -1) device = 'macOS';
    else if (ua.indexOf('Linux') !== -1) device = 'Linux';
    else if (ua.indexOf('Android') !== -1) { device = 'Android'; icon = 'mobile'; }
    else if (ua.indexOf('iPhone') !== -1 || ua.indexOf('iPad') !== -1) { device = 'iOS'; icon = 'mobile'; }

    return { browser: browser, device: device, icon: icon };
  }

  function initActiveSessions() {
    var sessionEl = document.getElementById('currentSessionInfo');
    if (!sessionEl) return;
    var info = detectBrowser();
    sessionEl.querySelector('.session-browser').textContent = info.browser + ' on ' + info.device;
  }
  initActiveSessions();

  // =========================================================================
  // REVEAL ANIMATION (Intersection Observer)
  // =========================================================================
  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if ('IntersectionObserver' in window) {
    var revealObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -60px 0px' });

    document.querySelectorAll('.reveal').forEach(function(el) {
      if (prefersReducedMotion) {
        el.classList.add('visible');
      } else {
        revealObserver.observe(el);
      }
    });

  }

  // =========================================================================
  // SCROLL-EMERGE ANIMATION SYSTEM (public pages)
  // Content emerges FROM its container via clip-path masking + transform.
  // Reversible in both scroll directions, driven by scroll position per-frame.
  // =========================================================================
  (function() {
    var emergeEls = document.querySelectorAll('[data-emerge]');
    if (!emergeEls.length) return;

    // Note: scroll-emerge always runs — it is scroll-position-driven (not
    // a triggered animation) so it stays accessible. Elements at progress 1
    // render identically to no-animation state.

    var items = [];
    var vh = window.innerHeight;
    var running = false;
    var LERP = 0.12;

    function easeOut(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    emergeEls.forEach(function(el) {
      var children = el.querySelectorAll('[data-emerge-child]');
      var rect = el.getBoundingClientRect();
      items.push({
        el: el,
        section: el.closest('.emerge-section') || el.parentElement,
        children: Array.prototype.slice.call(children),
        intensity: parseFloat(el.getAttribute('data-emerge')) || 1,
        layoutTop: rect.top + window.scrollY,
        layoutHeight: rect.height,
        current: -1,
        target: 0,
        childCurrent: [],
        prev: -1
      });
    });

    function getTarget(item) {
      var top = item.layoutTop - window.scrollY;
      var bottom = top + item.layoutHeight;

      if (top >= vh) return 0;
      if (bottom <= 0) return 0;

      var enterStart = vh * 0.92;
      var enterEnd = vh * 0.28;
      var exitThresh = vh * 0.12;

      if (top >= enterStart) return 0;
      if (top >= enterEnd) {
        return (enterStart - top) / (enterStart - enterEnd);
      }
      if (bottom > exitThresh) return 1;
      return bottom / exitThresh;
    }

    function render(item) {
      var p = easeOut(item.current);
      var rounded = Math.round(p * 100) / 100;
      if (rounded === item.prev) return;
      item.prev = rounded;

      var k = item.intensity;
      var inv = 1 - p;

      var ty = inv * 70 * k;
      var sc = 0.9 + p * 0.1;

      var clipY = inv * 14 * k;
      var clipX = inv * 3 * k;
      var clipR = inv * 14 * k;

      item.el.style.transform = 'translateY(' + ty.toFixed(1) + 'px) scale(' + sc.toFixed(4) + ')';
      item.el.style.opacity = (0.08 + p * 0.92).toFixed(3);
      item.el.style.clipPath = 'inset(' + clipY.toFixed(1) + '% ' + clipX.toFixed(1) + '% ' + clipY.toFixed(1) + '% ' + clipX.toFixed(1) + '% round ' + clipR.toFixed(0) + 'px)';

      for (var i = 0; i < item.children.length; i++) {
        var delay = (i + 1) * 0.09;
        var childTarget = Math.max(0, Math.min(1, (item.current - delay) / Math.max(0.01, 1 - delay)));
        if (item.childCurrent[i] === undefined) item.childCurrent[i] = childTarget;
        item.childCurrent[i] += (childTarget - item.childCurrent[i]) * 0.18;
        if (Math.abs(childTarget - item.childCurrent[i]) < 0.003) item.childCurrent[i] = childTarget;

        var cp = easeOut(item.childCurrent[i]);
        var cinv = 1 - cp;
        var cty = cinv * 36 * k;
        var csc = 0.94 + cp * 0.06;

        item.children[i].style.transform = 'translateY(' + cty.toFixed(1) + 'px) scale(' + csc.toFixed(4) + ')';
        item.children[i].style.opacity = cp.toFixed(3);
      }
    }

    function recalcLayout() {
      vh = window.innerHeight;
      items.forEach(function(item) {
        var prev = item.el.style.cssText;
        item.el.style.transform = 'none';
        item.el.style.clipPath = 'none';
        item.el.style.opacity = '1';
        var rect = item.el.getBoundingClientRect();
        item.layoutTop = rect.top + window.scrollY;
        item.layoutHeight = rect.height;
        item.el.style.cssText = prev;
        item.prev = -1;
      });
    }

    function tick() {
      var settled = true;
      for (var i = 0; i < items.length; i++) {
        var item = items[i];
        item.target = getTarget(item);
        if (item.current < 0) {
          item.current = item.target;
        } else {
          item.current += (item.target - item.current) * LERP;
          if (Math.abs(item.target - item.current) < 0.003) {
            item.current = item.target;
          }
        }
        if (item.current !== item.target) settled = false;
        for (var c = 0; c < item.childCurrent.length; c++) {
          if (item.childCurrent[c] !== undefined) {
            var ct = Math.max(0, Math.min(1, (item.current - (c + 1) * 0.09) / Math.max(0.01, 1 - (c + 1) * 0.09)));
            if (Math.abs(ct - item.childCurrent[c]) > 0.003) settled = false;
          }
        }
        render(item);
      }
      if (!settled) {
        requestAnimationFrame(tick);
      } else {
        running = false;
      }
    }

    function startLoop() {
      if (!running) {
        running = true;
        requestAnimationFrame(tick);
      }
    }

    window.addEventListener('scroll', startLoop, { passive: true });
    window.addEventListener('resize', function() {
      recalcLayout();
      startLoop();
    }, { passive: true });

    items.forEach(function(item) { item.current = getTarget(item); });
    startLoop();
  })();

  // =========================================================================
  // TOLERANCE BAR ANIMATION (Landing page)
  // =========================================================================
  if ('IntersectionObserver' in window && !prefersReducedMotion) {
    var barObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var bars = entry.target.querySelectorAll('.lp-tol-param-fill');
          bars.forEach(function(bar, i) {
            var targetWidth = bar.style.width;
            bar.style.width = '0%';
            bar.style.transitionDelay = (i * 0.12) + 's';
            requestAnimationFrame(function() {
              requestAnimationFrame(function() {
                bar.style.width = targetWidth;
              });
            });
          });
          barObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    document.querySelectorAll('.lp-tol-visual').forEach(function(el) {
      barObserver.observe(el);
    });
  }

  // =========================================================================
  // FILE DROPZONE
  // =========================================================================
  document.querySelectorAll('.file-dropzone').forEach(function(zone) {
    var input = zone.querySelector('input[type="file"]');

    zone.addEventListener('dragover', function(e) {
      e.preventDefault();
      zone.classList.add('dragover');
    });

    zone.addEventListener('dragleave', function() {
      zone.classList.remove('dragover');
    });

    zone.addEventListener('drop', function(e) {
      e.preventDefault();
      zone.classList.remove('dragover');
      if (input && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
        showUploadedFiles(zone, e.dataTransfer.files);
      }
    });

    zone.addEventListener('click', function(e) {
      if (e.target.closest('.file-remove')) return;
      if (input) input.click();
    });

    if (input) {
      input.addEventListener('change', function() {
        if (input.files.length) {
          showUploadedFiles(zone, input.files);
          // Validate file
          if (input.hasAttribute('data-validate') || input.hasAttribute('data-allowed')) {
            var error = '';
            if (input.getAttribute('data-allowed')) error = validators.file_type(input);
            if (!error && input.getAttribute('data-max-size')) error = validators.file_size(input);
            if (error) {
              window.showToast(error, 'error');
            }
          }
        }
      });
    }
  });

  function showUploadedFiles(zone, files) {
    var existing = zone.querySelector('.file-list');
    if (existing) existing.remove();

    var list = document.createElement('div');
    list.className = 'file-list';

    Array.from(files).forEach(function(file) {
      var size = file.size < 1024 ? file.size + ' B' :
                 file.size < 1048576 ? (file.size / 1024).toFixed(1) + ' KB' :
                 (file.size / 1048576).toFixed(1) + ' MB';

      var item = document.createElement('div');
      item.className = 'file-list-item';
      item.innerHTML =
        '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round"><path d="M8.5 1.5H3.5a1 1 0 00-1 1v9a1 1 0 001 1h7a1 1 0 001-1v-6l-3-4z"/></svg>' +
        '<span class="file-list-name">' + escHtml(file.name) + '</span>' +
        '<span class="file-list-size">' + size + '</span>' +
        '<button type="button" class="file-remove" aria-label="Remove">&times;</button>';
      list.appendChild(item);

      item.querySelector('.file-remove').addEventListener('click', function(e) {
        e.stopPropagation();
        item.remove();
        if (!list.children.length) list.remove();
      });
    });

    zone.appendChild(list);
  }

  // =========================================================================
  // NUMBER COUNTER ANIMATION
  // =========================================================================
  document.querySelectorAll('[data-count-to]').forEach(function(el) {
    var target = parseFloat(el.getAttribute('data-count-to'));
    var suffix = el.getAttribute('data-count-suffix') || '';
    var duration = 900;
    var startTime = null;

    function animate(ts) {
      if (!startTime) startTime = ts;
      var progress = Math.min((ts - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 4);
      var current = target * eased;
      el.textContent = (target % 1 === 0 ? Math.round(current).toLocaleString() : current.toFixed(1)) + suffix;
      if (progress < 1) requestAnimationFrame(animate);
    }

    if ('IntersectionObserver' in window) {
      var obs = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting) {
          requestAnimationFrame(animate);
          obs.unobserve(el);
        }
      }, { threshold: 0.5 });
      obs.observe(el);
    } else {
      requestAnimationFrame(animate);
    }
  });

  // =========================================================================
  // UTILITIES
  // =========================================================================
  function debounce(fn, delay) {
    var timer;
    return function() {
      var context = this, args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function() { fn.apply(context, args); }, delay);
    };
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function getBaseUrl() {
    var scripts = document.querySelectorAll('link[rel="stylesheet"]');
    for (var i = 0; i < scripts.length; i++) {
      var href = scripts[i].getAttribute('href') || '';
      var idx = href.indexOf('/assets/');
      if (idx !== -1) return href.substring(0, idx);
    }
    return '/lab-automation';
  }

  // =========================================================================
  // SEARCH FACET TABS
  // =========================================================================
  document.querySelectorAll('[data-search-facet]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var group = btn.closest('.tabs');
      if (group) {
        group.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
      }
      btn.classList.add('active');

      var facet = btn.getAttribute('data-search-facet');
      var container = document.getElementById('searchResultsContainer');
      if (!container) return;

      var cards = container.querySelectorAll('.search-result-card');
      var headings = container.querySelectorAll('.search-facet-group');

      cards.forEach(function(card) {
        card.style.display = (facet === 'all' || card.getAttribute('data-facet') === facet) ? '' : 'none';
      });

      headings.forEach(function(h) {
        h.style.display = (facet === 'all' || h.getAttribute('data-facet') === facet) ? '' : 'none';
      });
    });
  });

  // =========================================================================
  // PASS RATE CHART (Dashboard)
  // =========================================================================
  var chartData = {
    7:  [88, 92, 85, 95, 91, 97, 94],
    30: [88, 92, 85, 95, 91, 97, 94, 89, 93, 96, 92, 88, 94, 96, 91, 90, 87, 95, 93, 88, 96, 94, 91, 89, 97, 92, 95, 93, 90, 94],
    90: [86, 89, 91, 88, 92, 90, 85, 93, 95, 88, 91, 94, 87, 90, 92, 96, 89, 93, 88, 95, 91, 94, 90, 87, 93, 96, 92, 89, 91, 95, 88, 94, 90, 93, 87, 91, 96, 92, 88, 95, 90, 94, 91, 89, 93, 97, 88, 92, 95, 91, 86, 94, 90, 93, 88, 96, 92, 89, 95, 91, 94, 87, 93, 90, 96, 92, 88, 95, 91, 94, 89, 93, 97, 90, 92, 88, 95, 91, 86, 94, 93, 88, 96, 92, 89, 95, 91, 94, 97, 93]
  };

  function renderPassRateChart(period) {
    var container = document.getElementById('passRateChart');
    if (!container) return;

    var data = chartData[period] || chartData[7];
    var len = data.length;
    var maxBarHeight = 170;
    var gap = len > 30 ? '2px' : '5px';

    var html = '';
    for (var i = 0; i < len; i++) {
      var val = data[i];
      var barH = Math.round(val * (maxBarHeight / 100));
      var color = val >= 90 ? 'var(--success)' : (val >= 85 ? 'var(--warning)' : 'var(--fail)');

      var daysAgo = len - i - 1;
      var d = new Date();
      d.setDate(d.getDate() - daysAgo);
      var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var label = months[d.getMonth()] + ' ' + d.getDate();

      var showLabel = len <= 14 || (len <= 30 && i % 3 === 0) || (len > 30 && i % 7 === 0) || i === len - 1;
      var showValue = len <= 14;

      html += '<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;min-width:0;">';
      if (showValue) {
        html += '<span style="font-size:0.5625rem;color:var(--ink-faint);font-variant-numeric:tabular-nums;">' + val + '%</span>';
      }
      html += '<div class="chart-bar" style="width:100%;height:' + barH + 'px;background:' + color + ';border-radius:3px 3px 0 0;opacity:0.8;transition:opacity 0.2s,height 0.4s var(--ease-out);min-width:2px;" title="' + label + ': ' + val + '%"></div>';
      if (showLabel) {
        html += '<span style="font-size:0.5rem;color:var(--ink-faint);white-space:nowrap;">' + label + '</span>';
      }
      html += '</div>';
    }

    container.style.gap = gap;
    container.innerHTML = html;
  }

  document.querySelectorAll('[data-chart-period]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var group = btn.closest('.chart-period-btns');
      if (group) {
        group.querySelectorAll('button').forEach(function(b) {
          b.className = 'btn btn-sm btn-ghost';
          b.style.fontSize = '0.6875rem';
        });
      }
      btn.className = 'btn btn-sm btn-secondary';
      btn.style.fontSize = '0.6875rem';
      renderPassRateChart(parseInt(btn.getAttribute('data-chart-period'), 10));
    });
  });

  renderPassRateChart(7);

  // Page entrance animation is now CSS-driven (.app-content has animation
  // in style.css), so no JS class addition is needed. This avoids the
  // flash caused by content being visible before the JS-added class.

  // =========================================================================
  // INITIALIZATION
  // =========================================================================
  initPagination();

})();
