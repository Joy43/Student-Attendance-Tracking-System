<?php
require_once "header.php";

// ─── Fetch live statistics ───
function q($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    return $r ? (mysqli_fetch_assoc($r)['cnt'] ?? 0) : 0;
}

$total_students  = q($conn, "SELECT COUNT(*) cnt FROM student_details");
$total_courses   = q($conn, "SELECT COUNT(*) cnt FROM course_details");
$total_faculty   = q($conn, "SELECT COUNT(*) cnt FROM faculty_details");
$today           = date('Y-m-d');
$today_present   = q($conn, "SELECT COUNT(DISTINCT student_id) cnt FROM attendance_details WHERE on_date='$today' AND status='Present'");
$today_absent    = q($conn, "SELECT COUNT(DISTINCT student_id) cnt FROM attendance_details WHERE on_date='$today' AND status='Absent'");
$today_late      = q($conn, "SELECT COUNT(DISTINCT student_id) cnt FROM attendance_details WHERE on_date='$today' AND status='Late'");
$today_leave     = q($conn, "SELECT COUNT(DISTINCT student_id) cnt FROM attendance_details WHERE on_date='$today' AND status='Leave'");
$att_pct         = $total_students > 0 ? round(($today_present / $total_students) * 100) : 0;

// ─── Last 7 days attendance trend ───
$trend_labels = $trend_present = $trend_absent = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[] = date('D', strtotime($d));
    $trend_present[] = q($conn, "SELECT COUNT(DISTINCT student_id) cnt FROM attendance_details WHERE on_date='$d' AND status='Present'");
    $trend_absent[]  = q($conn, "SELECT COUNT(DISTINCT student_id) cnt FROM attendance_details WHERE on_date='$d' AND status='Absent'");
}

// ─── Recent activity (last 5 attendance actions) ───
$recent_q = mysqli_query($conn, "
    SELECT s.name, s.roll_no, a.status, a.on_date, c.code
    FROM attendance_details a
    JOIN student_details s ON a.student_id = s.id
    JOIN course_details c  ON a.course_id  = c.id
    ORDER BY a.on_date DESC
    LIMIT 5
");
$recent = [];
if ($recent_q) while ($r = mysqli_fetch_assoc($recent_q)) $recent[] = $r;
?>

<!-- ─── Page Header ─── -->
<div class="page-header">
  <div class="page-breadcrumb">
    <span>Home</span> <span>›</span> <span style="color:var(--text)">Dashboard</span>
  </div>
  <h1 class="page-title">Dashboard</h1>
  <p style="color:var(--text-secondary);font-size:.875rem;margin-top:2px">
    Welcome back, <strong><?= htmlspecialchars($faculty_user) ?></strong> — <?= date('l, F j, Y') ?>
  </p>
</div>

<!-- ─── Stats Row ─── -->
<div class="stats-row">
  <div class="stat-card blue">
    <div class="stat-icon-wrap">📚</div>
    <div class="stat-label">Total Students</div>
    <div class="stat-value"><?= $total_students ?></div>
    <div class="stat-footer"><?= $total_courses ?> courses active</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon-wrap">✅</div>
    <div class="stat-label">Present Today</div>
    <div class="stat-value"><?= $today_present ?></div>
    <div class="stat-footer">out of <?= $total_students ?> students</div>
    <div class="progress-bar" style="margin-top:6px"><div class="progress-fill green" style="width:<?= $att_pct ?>%"></div></div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon-wrap">❌</div>
    <div class="stat-label">Absent Today</div>
    <div class="stat-value"><?= $today_absent ?></div>
    <div class="stat-footer">students not present</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon-wrap">⏰</div>
    <div class="stat-label">Late Today</div>
    <div class="stat-value"><?= $today_late ?></div>
    <div class="stat-footer">arrived late</div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon-wrap">📝</div>
    <div class="stat-label">Leave Requests</div>
    <div class="stat-value"><?= $today_leave ?></div>
    <div class="stat-footer">on leave today</div>
  </div>
  <div class="stat-card sky">
    <div class="stat-icon-wrap">📊</div>
    <div class="stat-label">Attendance %</div>
    <div class="stat-value"><?= $att_pct ?>%</div>
    <div class="stat-footer">today's overall rate</div>
    <div class="progress-bar" style="margin-top:6px"><div class="progress-fill blue" style="width:<?= $att_pct ?>%"></div></div>
  </div>
</div>

<!-- ─── Charts Row ─── -->
<div class="chart-grid">
  <!-- Trend Line Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📈 7-Day Attendance Trend</span>
      <span style="font-size:.75rem;color:var(--text-secondary)">Last 7 days</span>
    </div>
    <div class="card-body" style="height:240px;position:relative;">
      <canvas id="trendChart"></canvas>
    </div>
  </div>
  <!-- Pie Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">🎯 Today's Overview</span>
    </div>
    <div class="card-body" style="height:240px;display:flex;align-items:center;justify-content:center;position:relative;">
      <canvas id="pieChart"></canvas>
    </div>
  </div>
</div>

<!-- ─── Bottom Grid ─── -->
<div class="bottom-grid">
  <!-- Recent Activity -->
  <div class="card" style="grid-column:span 2;">
    <div class="card-header">
      <span class="card-title">🔔 Recent Attendance Activity</span>
      <a href="/attapp/dashboard/view_attendance.php" class="btn btn-ghost btn-sm" style="font-size:.8rem;color:var(--primary)">View All →</a>
    </div>
    <div class="card-body" style="padding-top:0;padding-bottom:0;">
      <?php if (empty($recent)): ?>
        <div class="table-empty">No attendance records yet.</div>
      <?php else: ?>
        <?php foreach ($recent as $r): ?>
        <?php
          $status_colors = ['Present'=>'var(--present)','Absent'=>'var(--absent)','Late'=>'var(--late)','Leave'=>'var(--leave)'];
          $dot_color = $status_colors[$r['status']] ?? '#94A3B8';
        ?>
        <div class="activity-item">
          <span class="activity-dot" style="background:<?= $dot_color ?>"></span>
          <div>
            <div class="activity-text">
              <strong><?= htmlspecialchars($r['name']) ?></strong> (<?= htmlspecialchars($r['roll_no']) ?>) marked
              <strong style="color:<?= $dot_color ?>"><?= htmlspecialchars($r['status']) ?></strong>
              in <em><?= htmlspecialchars($r['code']) ?></em>
            </div>
            <div class="activity-time"><?= date('M j, Y', strtotime($r['on_date'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Quick Links -->
  <div class="card">
    <div class="card-header"><span class="card-title">⚡ Quick Actions</span></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
      <a href="/attapp/dashboard/take_attendance.php" class="btn btn-primary" style="width:100%;justify-content:flex-start;gap:10px;text-decoration:none;">
        📋 Take Today's Attendance
      </a>
      <a href="/attapp/dashboard/manage_students.php" class="btn btn-secondary" style="width:100%;justify-content:flex-start;gap:10px;text-decoration:none;">
        👤 Manage Student Catalog
      </a>
      <a href="/attapp/dashboard/view_attendance.php" class="btn btn-secondary" style="width:100%;justify-content:flex-start;gap:10px;text-decoration:none;">
        📊 Generate Reports
      </a>
    </div>
  </div>
</div>

<!-- ─── Charts JS ─── -->
<script>
const trendLabels  = <?= json_encode($trend_labels) ?>;
const trendPresent = <?= json_encode($trend_present) ?>;
const trendAbsent  = <?= json_encode($trend_absent) ?>;

const isDark = document.body.classList.contains('dark-mode');
Chart.defaults.color = isDark ? '#94A3B8' : '#64748B';
Chart.defaults.borderColor = isDark ? '#334155' : '#E2E8F0';
Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";

// Line chart
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: trendLabels,
    datasets: [
      { label: 'Present', data: trendPresent, borderColor:'#16A34A', backgroundColor:'rgba(22,163,74,.08)', tension:.4, fill:true, pointRadius:4, pointHoverRadius:6 },
      { label: 'Absent',  data: trendAbsent,  borderColor:'#DC2626', backgroundColor:'rgba(220,38,38,.06)', tension:.4, fill:true, pointRadius:4, pointHoverRadius:6 },
    ]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom',labels:{padding:16,usePointStyle:true}}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});

// Pie chart
const todayP = <?= $today_present ?>, todayA = <?= $today_absent ?>, todayL = <?= $today_late ?>, todayLv = <?= $today_leave ?>, noData = (todayP+todayA+todayL+todayLv === 0);
new Chart(document.getElementById('pieChart'), {
  type: 'doughnut',
  data: {
    labels: noData ? ['No Data'] : ['Present', 'Absent', 'Late', 'Leave'],
    datasets: [{
      data: noData ? [1] : [todayP, todayA, todayL, todayLv],
      backgroundColor: noData ? ['#E2E8F0'] : ['#16A34A','#DC2626','#EA580C','#7C3AED'],
      borderWidth: 2, borderColor: isDark ? '#1E293B' : '#FFFFFF'
    }]
  },
  options: {
    responsive:true, maintainAspectRatio:false, cutout:'65%',
    plugins:{ legend:{ position:'bottom', labels:{ padding:14, usePointStyle:true, font:{ size:11 } } } }
  }
});
</script>

<?php require_once "footer.php"; ?>
