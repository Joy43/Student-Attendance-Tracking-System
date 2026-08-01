<!-- ══════════ PAGE CONTENT ENDS ══════════ -->
  </div><!-- /main-scroll -->
</div><!-- /page-container -->
</div><!-- /app-shell -->

<!-- ══════════════════ TOAST CONTAINER ══════════════════ -->
<div class="toast-container" id="toast-container"></div>

<!-- ══════════════════ SCRIPTS ══════════════════ -->
<script>
/* ─── Theme Toggle ─── */
(function initTheme() {
  // Default to dark mode unless explicitly set to light
  if (localStorage.getItem('theme') !== 'light') {
    document.body.classList.add('dark-mode');
    document.getElementById('theme-icon').innerHTML = `<?= icon('sun') ?>`;
    localStorage.setItem('theme', 'dark'); // Set it so it's consistent
  }
})();

function toggleTheme() {
  const isDark = document.body.classList.toggle('dark-mode');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
  document.getElementById('theme-icon').innerHTML = isDark
    ? `<?= icon('sun') ?>`
    : `<?= icon('moon') ?>`;
}

/* ─── Toast Helper ─── */
function showToast(title, msg, type = 'success', duration = 3500) {
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const container = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = `
    <span class="toast-icon">${icons[type]}</span>
    <div><div class="toast-title">${title}</div><div class="toast-msg">${msg}</div></div>
    <button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>
  `;
  container.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, duration);
}

/* ─── Mobile menu auto-hide ─── */
const menuBtn = document.getElementById('menu-btn');
function checkMobile() {
  if (window.innerWidth <= 900) {
    if (menuBtn) menuBtn.style.display = 'flex';
  } else {
    if (menuBtn) menuBtn.style.display = 'none';
    document.getElementById('sidebar').classList.remove('open');
  }
}
window.addEventListener('resize', checkMobile);
checkMobile();

/* ─── Modal helpers ─── */
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    closeNotifPanel();
  }
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('active'); });
});

/* ══════════════════════════════════════════
   NOTIFICATION SYSTEM
   ══════════════════════════════════════════ */
const NOTIF_API = '/notifications.php';
let notifOpen = false;

const typeConfig = {
  create: { icon: '➕', color: '#16A34A', bg: '#F0FDF4', border: 'rgba(22,163,74,.2)'  },
  update: { icon: '✏️', color: '#2563EB', bg: '#EFF6FF', border: 'rgba(37,99,235,.2)' },
  delete: { icon: '🗑️', color: '#DC2626', bg: '#FEF2F2', border: 'rgba(220,38,38,.2)' },
};

function toggleNotifPanel() {
  notifOpen = !notifOpen;
  const panel = document.getElementById('notif-panel');
  panel.style.display = notifOpen ? 'block' : 'none';
  if (notifOpen) fetchNotifications();
}

function closeNotifPanel() {
  notifOpen = false;
  const panel = document.getElementById('notif-panel');
  if (panel) panel.style.display = 'none';
}

// Close when clicking outside the bell wrapper
document.addEventListener('click', e => {
  const wrap = document.getElementById('notif-wrap');
  if (wrap && !wrap.contains(e.target)) closeNotifPanel();
});

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60)    return 'Just now';
  if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  return Math.floor(diff / 86400) + 'd ago';
}

function renderNotifications(data) {
  const list  = document.getElementById('notif-list');
  const badge = document.getElementById('notif-badge');
  const dot   = document.getElementById('notif-dot');
  const notifs = data.notifications || [];
  const unread = data.unread || 0;

  // Update topnav badge
  if (unread > 0) {
    badge.textContent = unread > 99 ? '99+' : unread;
    badge.style.display = 'inline-flex';
    dot.style.display = 'block';
  } else {
    badge.style.display = 'none';
    dot.style.display = 'none';
  }

  // Sync sidebar badge
  const sideBadge = document.getElementById('sidebar-notif-badge');
  if (sideBadge) {
    if (unread > 0) {
      sideBadge.textContent = unread > 99 ? '99+' : unread;
      sideBadge.style.display = 'inline-flex';
    } else {
      sideBadge.style.display = 'none';
    }
  }

  if (notifs.length === 0) {
    list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-secondary);font-size:.85rem">🔕 No notifications yet</div>';
    return;
  }

  list.innerHTML = notifs.map(n => {
    const cfg = typeConfig[n.type] || typeConfig.create;
    const unreadBg = n.is_read == 0 ? `background:${cfg.bg};` : '';
    return `<div class="notif-item" id="notif-item-${n.id}" style="
        display:flex;gap:12px;align-items:flex-start;
        padding:12px 16px;border-bottom:1px solid var(--border);
        cursor:pointer;transition:background .15s ease,opacity .25s ease,height .25s ease;${unreadBg}
      " onclick="markRead(${n.id}, this)">
      <div style="
        width:36px;height:36px;border-radius:10px;flex-shrink:0;
        background:${cfg.bg};border:1px solid ${cfg.border};
        display:flex;align-items:center;justify-content:center;font-size:1rem;margin-top:2px;
      ">${cfg.icon}</div>
      <div style="flex-grow:1;min-width:0;overflow:hidden">
        <div style="font-size:.82rem;font-weight:700;color:${cfg.color};margin-bottom:3px">${n.title}</div>
        <div style="font-size:.78rem;color:var(--text-secondary);line-height:1.45;word-break:break-word">${n.message}</div>
        <div style="font-size:.7rem;color:var(--text-disabled);margin-top:5px">${timeAgo(n.created_at)}</div>
      </div>
      <button onclick="event.stopPropagation();dismissNotif(${n.id})" title="Dismiss" style="
        background:none;border:none;cursor:pointer;color:var(--text-disabled);
        font-size:.85rem;padding:2px 5px;border-radius:4px;flex-shrink:0;line-height:1;
        transition:color .15s;
      " onmouseover="this.style.color='var(--absent)'" onmouseout="this.style.color='var(--text-disabled)'">✕</button>
    </div>`;
  }).join('');
}

function fetchNotifications() {
  fetch(NOTIF_API + '?action=fetch')
    .then(r => r.json())
    .then(data => renderNotifications(data))
    .catch(() => {});
}

function markRead(id, el) {
  if (el) el.style.background = '';
  fetch(NOTIF_API + '?action=mark_read&id=' + id)
    .then(() => fetchNotifications());
}

function markAllRead() {
  fetch(NOTIF_API + '?action=mark_all_read', { method: 'POST' })
    .then(() => {
      fetchNotifications();
      showToast('Notifications', 'All notifications marked as read.', 'success', 2500);
    });
}

function dismissNotif(id) {
  const item = document.getElementById('notif-item-' + id);
  if (item) {
    item.style.opacity = '0';
    item.style.maxHeight = '0';
    item.style.padding = '0';
    item.style.overflow = 'hidden';
    item.style.borderBottom = 'none';
    item.style.transition = 'all .25s ease';
  }
  fetch(NOTIF_API + '?action=delete&id=' + id)
    .then(() => setTimeout(fetchNotifications, 300));
}

function clearAllRead() {
  const items = document.querySelectorAll('.notif-item');
  let promises = [];
  items.forEach(item => {
    // Read items have no custom background set
    if (window.getComputedStyle(item).backgroundColor === 'rgba(0, 0, 0, 0)'
        || !item.style.background) {
      const id = item.id.replace('notif-item-', '');
      promises.push(fetch(NOTIF_API + '?action=delete&id=' + id));
      item.remove();
    }
  });
  Promise.all(promises).then(() => {
    fetchNotifications();
    showToast('Cleared', 'Read notifications removed.', 'info', 2500);
  });
}

// Fetch on load + auto-refresh every 30 seconds
fetchNotifications();
setInterval(fetchNotifications, 30000);
</script>
</body>
</html>
