<?php
session_start();
if (!isset($_SESSION['faculty'])) {
    header("Location: /attapp/login/index.php");
    exit();
}

$path = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 1);
require_once $path . "/attapp/database/database.php";

$current_page = basename($_SERVER['PHP_SELF']);
$faculty_user  = $_SESSION['faculty'];

// Active nav helper
function activeNav($page, $cur) {
    return ($page === $cur) ? 'active' : '';
}

// SVG icon helper using Lucide-style paths (inline)
function icon($name) {
    $icons = [
        'grid'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'clipboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>',
        'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'book'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'bar-chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
        'settings'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'bell'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'search'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'sun'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'moon'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'mail'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'log-out'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'user-plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>',
        'menu'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
        'download'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'edit'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
        'trash'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',
        'eye'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        'calendar'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'x'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    ];
    return $icons[$name] ?? '<svg viewBox="0 0 24 24"></svg>';
}
?>
<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AttApp — Student Attendance Management</title>
<link rel="stylesheet" href="/attapp/dashboard/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body id="body-root">

<div class="app-shell">
<!-- ══════════════════ SIDEBAR ══════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">A</div>
    <div>
      <div class="logo-name">AttApp</div>
      <div class="logo-sub">Management System</div>
    </div>
  </div>

  <div class="sidebar-section-label">Main Menu</div>
  <nav class="sidebar-nav">
    <a href="/attapp/dashboard/dashboard.php" class="nav-item <?= activeNav('dashboard.php', $current_page) ?>">
      <span class="nav-icon"><?= icon('grid') ?></span> Dashboard
    </a>
    <a href="/attapp/dashboard/take_attendance.php" class="nav-item <?= activeNav('take_attendance.php', $current_page) ?>">
      <span class="nav-icon"><?= icon('clipboard') ?></span> Attendance
    </a>
    <a href="/attapp/dashboard/manage_students.php" class="nav-item <?= activeNav('manage_students.php', $current_page) ?>">
      <span class="nav-icon"><?= icon('users') ?></span> Students
    </a>
    <a href="/attapp/dashboard/view_attendance.php" class="nav-item <?= activeNav('view_attendance.php', $current_page) ?>">
      <span class="nav-icon"><?= icon('bar-chart') ?></span> Reports
    </a>
  </nav>

  <div class="sidebar-section-label">System</div>
  <nav class="sidebar-nav">
    <a href="#" class="nav-item">
      <span class="nav-icon"><?= icon('bell') ?></span> Notifications
      <span class="nav-badge">3</span>
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon"><?= icon('settings') ?></span> Settings
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar-sm"><?= strtoupper(substr($faculty_user, 0, 2)) ?></div>
      <div>
        <div class="user-name-sm"><?= htmlspecialchars($faculty_user) ?></div>
        <div class="user-role-sm">Faculty Member</div>
      </div>
    </div>
    <a href="/attapp/logout.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;color:#94A3B8;font-size:.8rem;font-weight:600;margin-top:4px;transition:all .15s ease;" onmouseover="this.style.background='rgba(220,38,38,.1)';this.style.color='#FCA5A5'" onmouseout="this.style.background='';this.style.color='#94A3B8'">
      <span style="width:14px;height:14px;display:inline-flex"><?= icon('log-out') ?></span> Sign Out
    </a>
  </div>
</aside>

<!-- ══════════════════ PAGE CONTAINER ══════════════════ -->
<div class="page-container">

  <!-- TOP NAV -->
  <header class="topnav">
    <!-- Hamburger (mobile) -->
    <button class="icon-btn" id="menu-btn" style="display:none" onclick="document.getElementById('sidebar').classList.toggle('open')">
      <span style="width:18px;height:18px"><?= icon('menu') ?></span>
    </button>

    <!-- Search -->
    <div class="topnav-search">
      <span style="width:16px;height:16px;color:var(--text-disabled);flex-shrink:0"><?= icon('search') ?></span>
      <input type="text" placeholder="Search students, courses…" id="global-search">
    </div>

    <div class="topnav-right">
      <!-- Academic Year -->
      <select class="year-select" id="year-select">
        <option>2025–2026</option>
        <option>2024–2025</option>
        <option>2023–2024</option>
      </select>

      <!-- Notifications -->
      <div style="position:relative" id="notif-wrap">
        <button class="icon-btn" id="notif-btn" title="Notifications" onclick="toggleNotifPanel()">
          <span style="width:18px;height:18px"><?= icon('bell') ?></span>
          <span class="notif-dot" id="notif-dot" style="display:none"></span>
        </button>
        <!-- Badge Count -->
        <span id="notif-badge" style="
          display:none; position:absolute; top:-4px; right:-4px;
          background:var(--absent); color:white; font-size:.62rem; font-weight:800;
          min-width:17px; height:17px; border-radius:999px; align-items:center;
          justify-content:center; line-height:1; padding:0 4px;
          border:2px solid var(--surface); pointer-events:none;
        ">0</span>

        <!-- Dropdown Panel -->
        <div id="notif-panel" style="
          display:none; position:absolute; top:calc(100% + 10px); right:0;
          width:360px; background:var(--surface); border:1px solid var(--border);
          border-radius:var(--r-lg); box-shadow:var(--shadow-xl);
          z-index:9999; overflow:hidden;
        ">
          <!-- Header -->
          <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)">
            <span style="font-size:.9rem;font-weight:700">🔔 Notifications</span>
            <button onclick="markAllRead()" style="font-size:.75rem;font-weight:600;color:var(--primary);background:none;border:none;cursor:pointer;">Mark all read</button>
          </div>
          <!-- List -->
          <div id="notif-list" style="max-height:380px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--border) transparent">
            <div style="padding:32px;text-align:center;color:var(--text-secondary);font-size:.85rem" id="notif-empty">No notifications yet</div>
          </div>
          <!-- Footer -->
          <div style="padding:10px 16px;border-top:1px solid var(--border);text-align:center">
            <a href="#" onclick="clearAllRead();return false;" style="font-size:.78rem;color:var(--text-secondary)">Clear read notifications</a>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <button class="icon-btn" title="Messages">
        <span style="width:18px;height:18px"><?= icon('mail') ?></span>
      </button>

      <!-- Theme Toggle -->
      <button class="icon-btn" id="theme-btn" title="Toggle theme" onclick="toggleTheme()">
        <span style="width:18px;height:18px" id="theme-icon"><?= icon('moon') ?></span>
      </button>

      <div class="topnav-divider"></div>

      <!-- Profile Avatar -->
      <div class="topnav-avatar" title="<?= htmlspecialchars($faculty_user) ?>">
        <?= strtoupper(substr($faculty_user, 0, 2)) ?>
      </div>
    </div>
  </header>

  <!-- MAIN SCROLL AREA (pages inject here) -->
  <div class="main-scroll" id="main-scroll">

<!-- ══════════ PAGE CONTENT STARTS ══════════ -->
