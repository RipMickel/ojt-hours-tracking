<?php
// admin/students.php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$students = db()->query(
    "SELECT u.*,
        COALESCE(SUM(CASE WHEN l.status='approved' THEN l.hours_rendered ELSE 0 END),0) approved_hours,
        COUNT(CASE WHEN l.status='pending' THEN 1 END) pending_ct,
        COUNT(l.id) total_logs
     FROM users u
     LEFT JOIN ojt_logs l ON l.user_id = u.id
     WHERE u.role='student'
     GROUP BY u.id
     ORDER BY u.full_name"
)->fetchAll();

renderHead('Students');
?>

<div class="section-header">
  <div>
    <h2>All Students</h2>
    <p><?= count($students) ?> registered student<?= count($students) !== 1 ? 's' : '' ?></p>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3><i class="icon-users"></i> Student List</h3>
  </div>
  <?php if ($students): ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Student</th><th>Student No.</th><th>Course</th><th>Company</th>
          <th>Approved</th><th>Required</th><th>Progress</th><th>Pending</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <?php $p = pct((float)$s['approved_hours'], (int)$s['required_hours']); ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="user-avatar" style="background:var(--navy-light);color:#fff;border:none;font-size:.72rem;flex-shrink:0">
                <?= strtoupper(substr($s['full_name'],0,1)) ?>
              </div>
              <div>
                <div class="td-name"><?= e($s['full_name']) ?></div>
                <div class="td-muted"><?= e($s['email']) ?></div>
              </div>
            </div>
          </td>
          <td class="td-muted"><?= e($s['student_number'] ?? '—') ?></td>
          <td style="font-size:.82rem;max-width:160px"><?= e($s['course'] ?? '—') ?></td>
          <td style="font-size:.82rem;max-width:160px"><?= e($s['company'] ?? '—') ?></td>
          <td><strong style="color:var(--green)"><?= fmtHours((float)$s['approved_hours']) ?></strong></td>
          <td class="td-muted"><?= $s['required_hours'] ?>h</td>
          <td style="min-width:130px">
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bar-track" style="flex:1">
                <div class="progress-bar-fill <?= $p>=100?'done':'' ?>"
                     data-pct="<?= $p ?>" style="width:0%"></div>
              </div>
              <span style="font-size:.75rem;color:var(--text-2);white-space:nowrap"><?= $p ?>%</span>
            </div>
          </td>
          <td>
            <?php if ($s['pending_ct'] > 0): ?>
              <span class="badge badge-pending"><?= $s['pending_ct'] ?></span>
            <?php else: ?>
              <span class="td-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/logs.php?uid=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">
              <i class="icon-list"></i> Logs
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body">
    <div class="empty-state"><i class="icon-users"></i><p>No students registered yet.</p></div>
  </div>
  <?php endif; ?>
</div>

<?php renderFoot(); ?>