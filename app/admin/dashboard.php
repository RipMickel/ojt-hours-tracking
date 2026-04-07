<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$db = db();

$totalStudents = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalLogs     = (int) $db->query("SELECT COUNT(*) FROM ojt_logs")->fetchColumn();
$pend          = pendingCount();
$approvedHrs   = (float) $db->query("SELECT COALESCE(SUM(hours_rendered),0) FROM ojt_logs WHERE status='approved'")->fetchColumn();

$rp = $db->query(
    "SELECT l.*,u.full_name,u.student_number
     FROM ojt_logs l JOIN users u ON u.id=l.user_id
     WHERE l.status='pending'
     ORDER BY l.log_date DESC LIMIT 7"
)->fetchAll();

$top = $db->query(
    "SELECT u.full_name,u.student_number,u.required_hours,
            COALESCE(SUM(CASE WHEN l.status='approved' THEN l.hours_rendered ELSE 0 END),0) h
     FROM users u LEFT JOIN ojt_logs l ON l.user_id=u.id
     WHERE u.role='student'
     GROUP BY u.id ORDER BY h DESC LIMIT 6"
)->fetchAll();

renderHead('Dashboard');
?>

<div class="section-header">
  <div>
    <h2>Admin Dashboard</h2>
    <p>System-wide OJT overview</p>
  </div>
  <?php if ($pend > 0): ?>
  <a href="<?= BASE_URL ?>/admin/pending.php" class="btn btn-primary">
    <i class="icon-clock"></i> <?= $pend ?> Pending Review
  </a>
  <?php endif; ?>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon ic-navy"><i class="icon-users"></i></div>
    <div class="stat-label">Students</div>
    <div class="stat-value"><?= $totalStudents ?></div>
    <div class="stat-sub">registered</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-blue"><i class="icon-book-open"></i></div>
    <div class="stat-label">Total Logs</div>
    <div class="stat-value"><?= $totalLogs ?></div>
    <div class="stat-sub">all submissions</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-amber"><i class="icon-hourglass"></i></div>
    <div class="stat-label">Pending</div>
    <div class="stat-value"><?= $pend ?></div>
    <div class="stat-sub">need action</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-green"><i class="icon-clock"></i></div>
    <div class="stat-label">Approved Hours</div>
    <div class="stat-value"><?= fmtHours($approvedHrs) ?></div>
    <div class="stat-sub">across all students</div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head">
      <h3><i class="icon-clock"></i> Pending Logs</h3>
      <a href="<?= BASE_URL ?>/admin/pending.php" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if ($rp): ?>
    <div class="table-scroll">
      <table>
        <thead><tr><th>Student</th><th>Date</th><th>Hours</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rp as $r): ?>
          <tr>
            <td>
              <div class="td-name"><?= e($r['full_name']) ?></div>
              <div class="td-muted"><?= e($r['student_number'] ?? '—') ?></div>
            </td>
            <td><?= fmtDate($r['log_date']) ?></td>
            <td><?= $r['hours_rendered'] > 0 ? fmtHours((float)$r['hours_rendered']) : '—' ?></td>
            <td><a href="<?= BASE_URL ?>/admin/pending.php?id=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">Review</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="card-body">
      <div class="empty-state" style="padding:24px 0">
        <i class="icon-check-circle" style="color:var(--green);opacity:1"></i>
        <p style="color:var(--green);font-weight:600">All caught up!</p>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-head">
      <h3><i class="icon-bar-chart-2"></i> Hours Leaderboard</h3>
      <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-ghost btn-sm">All students</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if ($top): ?>
      <?php foreach ($top as $s): ?>
      <?php $p = pct((float)$s['h'], (int)$s['required_hours']); ?>
      <div style="padding:13px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.875rem">
          <span style="font-weight:600"><?= e($s['full_name']) ?></span>
          <span style="color:var(--text-2)"><?= fmtHours((float)$s['h']) ?> / <?= $s['required_hours'] ?>h</span>
        </div>
        <div class="progress-bar-track" style="height:5px">
          <div class="progress-bar-fill <?= $p>=100?'done':'' ?>" data-pct="<?= $p ?>" style="width:0%"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state"><i class="icon-users"></i><p>No students yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php renderFoot(); ?>