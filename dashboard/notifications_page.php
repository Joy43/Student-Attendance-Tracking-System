<?php
require_once "header.php";

$faculty = mysqli_real_escape_string($conn, $faculty_user);

// ─── Actions ───
$toast_msg = $toast_type = '';

if (isset($_GET['mark_all'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE faculty_user='$faculty'");
    $toast_msg = 'All notifications marked as read.'; $toast_type = 'success';
}
if (isset($_GET['delete_all'])) {
    mysqli_query($conn, "DELETE FROM notifications WHERE faculty_user='$faculty'");
    $toast_msg = 'All notifications cleared.'; $toast_type = 'success';
}
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM notifications WHERE id='$del_id' AND faculty_user='$faculty'");
    header("Location: /attapp/dashboard/notifications_page.php"); exit();
}
if (isset($_GET['mark_id'])) {
    $mid = (int)$_GET['mark_id'];
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id='$mid' AND faculty_user='$faculty'");
    header("Location: /attapp/dashboard/notifications_page.php"); exit();
}

// ─── Filter ───
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$allowed = ['all','create','update','delete','unread'];
if (!in_array($filter, $allowed)) $filter = 'all';

$where = "faculty_user='$faculty'";
if ($filter === 'unread') $where .= " AND is_read=0";
elseif (in_array($filter, ['create','update','delete'])) $where .= " AND type='$filter'";

// ─── Load notifications ───
$notifs_q = mysqli_query($conn, "
    SELECT * FROM notifications WHERE $where ORDER BY created_at DESC
");
$notifs = [];
if ($notifs_q) while ($r = mysqli_fetch_assoc($notifs_q)) $notifs[] = $r;

// ─── Counts ───
function countWhere($conn, $where) {
    $r = mysqli_query($conn, "SELECT COUNT(*) cnt FROM notifications WHERE $where");
    return $r ? (mysqli_fetch_assoc($r)['cnt'] ?? 0) : 0;
}
$fac_where = "faculty_user='$faculty'";
$count_all    = countWhere($conn, $fac_where);
$count_unread = countWhere($conn, $fac_where . " AND is_read=0");
$count_create = countWhere($conn, $fac_where . " AND type='create'");
$count_update = countWhere($conn, $fac_where . " AND type='update'");
$count_delete = countWhere($conn, $fac_where . " AND type='delete'");

$type_config = [
    'create' => ['icon'=>'➕','color'=>'#16A34A','bg'=>'#F0FDF4','border'=>'rgba(22,163,74,.2)','label'=>'Created'],
    'update' => ['icon'=>'✏️','color'=>'#2563EB','bg'=>'#EFF6FF','border'=>'rgba(37,99,235,.2)','label'=>'Updated'],
    'delete' => ['icon'=>'🗑️','color'=>'#DC2626','bg'=>'#FEF2F2','border'=>'rgba(220,38,38,.2)','label'=>'Deleted'],
];
?>

<?php if ($toast_msg): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    showToast('<?= $toast_type === 'success' ? '✅ Done' : 'ℹ️ Info' ?>', '<?= htmlspecialchars($toast_msg, ENT_QUOTES) ?>', '<?= $toast_type ?>', 3000);
});
</script>
<?php endif; ?>

<!-- ─── Page Header ─── -->
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div class="page-breadcrumb">
      <a href="/attapp/dashboard/dashboard.php" style="color:var(--text-secondary)">Home</a>
      <span>›</span><span style="color:var(--text)">Notifications</span>
    </div>
    <h1 class="page-title">🔔 Notifications</h1>
    <p style="color:var(--text-secondary);font-size:.875rem;margin-top:2px">
      <?= $count_unread > 0 ? "<strong style='color:var(--absent)'>$count_unread unread</strong> of $count_all total" : "$count_all total notifications" ?>
    </p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php if ($count_unread > 0): ?>
    <a href="?mark_all=1" class="btn btn-secondary btn-sm" onclick="return confirm('Mark all as read?')">✅ Mark All Read</a>
    <?php endif; ?>
    <?php if ($count_all > 0): ?>
    <a href="?delete_all=1" class="btn btn-danger btn-sm" onclick="return confirm('Delete ALL notifications? This cannot be undone.')">🗑️ Clear All</a>
    <?php endif; ?>
  </div>
</div>

<!-- ─── Summary Cards ─── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px">
  <?php
  $tabs = [
    'all'    => ['All',     $count_all,    '📋','#2563EB','#EFF6FF','rgba(37,99,235,.2)'],
    'unread' => ['Unread',  $count_unread, '🔵','#7C3AED','#F5F3FF','rgba(124,58,237,.2)'],
    'create' => ['Created', $count_create, '➕','#16A34A','#F0FDF4','rgba(22,163,74,.2)'],
    'update' => ['Updated', $count_update, '✏️','#2563EB','#EFF6FF','rgba(37,99,235,.2)'],
    'delete' => ['Deleted', $count_delete, '🗑️','#DC2626','#FEF2F2','rgba(220,38,38,.2)'],
  ];
  foreach ($tabs as $key => [$label,$count,$em,$col,$bg,$bdr]):
    $active = $filter === $key;
  ?>
  <a href="?filter=<?= $key ?>" style="
    background:<?= $active ? $bg : 'var(--surface)' ?>;
    border:2px solid <?= $active ? $col : 'var(--border)' ?>;
    border-radius:var(--r-lg);padding:16px;text-decoration:none;
    display:flex;flex-direction:column;gap:6px;
    box-shadow:<?= $active ? "0 0 0 3px {$bdr}" : 'var(--shadow-sm)' ?>;
    transition:all .15s ease;
  ">
    <span style="font-size:1.2rem"><?= $em ?></span>
    <span style="font-size:1.5rem;font-weight:800;letter-spacing:-.04em;color:<?= $col ?>"><?= $count ?></span>
    <span style="font-size:.72rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em"><?= $label ?></span>
  </a>
  <?php endforeach; ?>
</div>

<!-- ─── Notifications List ─── -->
<div class="card">
  <!-- Toolbar -->
  <div class="card-header" style="flex-wrap:wrap;gap:10px">
    <span class="card-title">
      <?php $labels=['all'=>'All','unread'=>'Unread','create'=>'Student Created','update'=>'Student Updated','delete'=>'Student Deleted'];?>
      <?= $labels[$filter] ?? 'All' ?> Notifications
      <span style="font-size:.8rem;font-weight:500;color:var(--text-secondary);margin-left:6px">(<?= count($notifs) ?>)</span>
    </span>
    <!-- Search -->
    <div class="table-search" style="max-width:260px">
      <span style="color:var(--text-disabled);width:14px;height:14px;flex-shrink:0"><?= icon('search') ?></span>
      <input type="text" id="notif-search" placeholder="Search notifications…" oninput="filterNotifs(this.value)" style="border:none;background:transparent;outline:none;font-size:.8rem;color:var(--text);width:100%">
    </div>
  </div>

  <!-- List Body -->
  <div id="notif-list-body">
    <?php if (empty($notifs)): ?>
    <div class="empty-state" style="padding:64px 24px">
      <div class="icon">🔕</div>
      <div class="title">No notifications here</div>
      <p style="margin-top:4px;font-size:.85rem">
        <?= $filter !== 'all' ? '<a href="?filter=all" style="color:var(--primary)">View all notifications</a>' : 'Notifications will appear here when students are added, updated, or deleted.' ?>
      </p>
    </div>
    <?php else: ?>
    <?php foreach ($notifs as $n):
      $cfg = $type_config[$n['type']] ?? $type_config['create'];
      $unread_style = $n['is_read'] == 0 ? "background:{$cfg['bg']};" : '';
      $time_diff = time() - strtotime($n['created_at']);
      if ($time_diff < 60)       $time_label = 'Just now';
      elseif ($time_diff < 3600) $time_label = floor($time_diff/60) . 'm ago';
      elseif ($time_diff < 86400)$time_label = floor($time_diff/3600) . 'h ago';
      else                        $time_label = date('M j, Y g:i A', strtotime($n['created_at']));
    ?>
    <div class="notif-row" data-search="<?= strtolower($n['title'].' '.$n['message']) ?>" style="
      display:flex;align-items:flex-start;gap:16px;padding:16px 20px;
      border-bottom:1px solid var(--border);transition:background .15s;<?= $unread_style ?>
    ">
      <!-- Icon -->
      <div style="
        width:44px;height:44px;border-radius:12px;flex-shrink:0;
        background:<?= $cfg['bg'] ?>;border:1px solid <?= $cfg['border'] ?>;
        display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-top:2px;
      "><?= $cfg['icon'] ?></div>

      <!-- Content -->
      <div style="flex-grow:1;min-width:0">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
          <span style="font-size:.875rem;font-weight:700;color:<?= $cfg['color'] ?>"><?= htmlspecialchars($n['title']) ?></span>
          <!-- Type chip -->
          <span style="
            font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:999px;
            background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;
            border:1px solid <?= $cfg['border'] ?>;text-transform:uppercase;letter-spacing:.05em
          "><?= $cfg['label'] ?></span>
          <!-- Unread dot -->
          <?php if ($n['is_read'] == 0): ?>
          <span style="width:8px;height:8px;background:var(--absent);border-radius:50%;flex-shrink:0" title="Unread"></span>
          <?php endif; ?>
        </div>
        <div style="font-size:.85rem;color:var(--text-secondary);line-height:1.5;margin-bottom:6px">
          <?= htmlspecialchars($n['message']) ?>
        </div>
        <div style="font-size:.75rem;color:var(--text-disabled);display:flex;align-items:center;gap:6px">
          <span><?= icon('calendar') !== '' ? '<span style="width:12px;height:12px;display:inline-flex;opacity:.6">' . icon('calendar') . '</span>' : '' ?></span>
          <span><?= date('D, M j, Y · g:i A', strtotime($n['created_at'])) ?></span>
          <span style="color:var(--border)">·</span>
          <span><?= $time_label ?></span>
        </div>
      </div>

      <!-- Actions -->
      <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;align-items:flex-end">
        <?php if ($n['is_read'] == 0): ?>
        <a href="?mark_id=<?= $n['id'] ?>&filter=<?= $filter ?>" class="btn btn-secondary btn-sm" title="Mark as read" style="white-space:nowrap">
          ✅ Mark Read
        </a>
        <?php else: ?>
        <span style="font-size:.72rem;font-weight:600;color:var(--present);padding:4px 8px;background:var(--present-bg);border-radius:6px;white-space:nowrap">✓ Read</span>
        <?php endif; ?>
        <a href="?delete_id=<?= $n['id'] ?>" class="btn btn-sm" style="background:var(--absent-bg);color:var(--absent);border:1px solid rgba(220,38,38,.2);white-space:nowrap" onclick="return confirm('Delete this notification?')">
          🗑️ Delete
        </a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!empty($notifs)): ?>
  <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center">
    <span style="font-size:.8rem;color:var(--text-secondary)">Showing <?= count($notifs) ?> notification<?= count($notifs) !== 1 ? 's' : '' ?></span>
    <div style="display:flex;gap:8px">
      <?php if ($count_unread > 0): ?>
      <a href="?mark_all=1" class="btn btn-secondary btn-sm" onclick="return confirm('Mark all as read?')">✅ Mark All Read</a>
      <?php endif; ?>
      <?php if ($count_all > 0): ?>
      <a href="?delete_all=1" class="btn btn-danger btn-sm" onclick="return confirm('Clear all notifications?')">🗑️ Clear All</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function filterNotifs(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.notif-row').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
}

// Auto mark visible items as read after 5s (passive read-on-view)
document.querySelectorAll('.notif-row').forEach(row => {
  const markBtn = row.querySelector('a[href*="mark_id"]');
  if (markBtn) {
    // Fetch mark_read via API silently
    const href = markBtn.getAttribute('href');
    const match = href.match(/mark_id=(\d+)/);
    if (match) {
      setTimeout(() => {
        fetch('/attapp/dashboard/notifications.php?action=mark_read&id=' + match[1]);
        row.style.background = '';
        const dot = row.querySelector('[title="Unread"]');
        if (dot) dot.remove();
      }, 5000);
    }
  }
});
</script>

<?php require_once "footer.php"; ?>
