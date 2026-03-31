<?php
// admin/logs.php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$db = db();

$fUid    = isset($_GET['uid'])    ? (int) $_GET['uid']   : 0;
$fStatus = $_GET['status'] ?? '';

$sql    = "SELECT l.*,u.full_name,u.student_number FROM ojt_logs l JOIN users u ON u.id=l.user_id WHERE 1=1";
$params = [];
if ($fUid)    { $sql .= ' AND l.user_id=?'; $params[] = $fUid; }
if ($fStatus) { $sql .= ' AND l.status=?';  $params[] = $fStatus; }
$sql .= ' ORDER BY l.log_date DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Delete handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    verifyCsrf();
    $delId = (int) ($_POST['log_id'] ?? 0);
    if ($delId) {
        $db->prepare("DELETE FROM ojt_logs WHERE id=?")->execute([$delId]);
        flash('Log entry deleted.', 'success');
    }
    redirect(BASE_URL . '/admin/logs.php?' . http_build_query(['uid' => $fUid, 'status' => $fStatus]));
}

$students = $db->query("SELECT id,full_name,student_number FROM users WHERE role='student' ORDER BY full_name")->fetchAll();

$subName = '';
if ($fUid) {
    foreach ($students as $s) {
        if ((int)$s['id'] === $fUid) { $subName = ' — ' . $s['full_name']; break; }
    }
}

renderHead('All Logs');
?>

<div class="section-header">
  <div>
    <h2>All Logs<?= e($subName) ?></h2>
    <p><?= count($rows) ?> entr<?= count($rows) !== 1 ? 'ies' : 'y' ?> found</p>
  </div>
  <?php if ($fUid || $fStatus): ?>
  <a href="<?= BASE_URL ?>/admin/logs.php" class="btn btn-ghost btn-sm">
    <i class="icon-x"></i> Clear filters
  </a>
  <?php endif; ?>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:180px">
        <label class="form-label" style="font-size:.78rem;margin-bottom:4px">Student</label>
        <select name="uid" class="form-control" style="padding:7px 10px">
          <option value="">All Students</option>
          <?php foreach ($students as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $fUid === (int)$s['id'] ? 'selected' : '' ?>>
            <?= e($s['full_name']) ?> (<?= e($s['student_number'] ?? 'no ID') ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="min-width:140px">
        <label class="form-label" style="font-size:.78rem;margin-bottom:4px">Status</label>
        <select name="status" class="form-control" style="padding:7px 10px">
          <option value="">All</option>
          <option value="pending"  <?= $fStatus==='pending'  ? 'selected':'' ?>>Pending</option>
          <option value="approved" <?= $fStatus==='approved' ? 'selected':'' ?>>Approved</option>
          <option value="rejected" <?= $fStatus==='rejected' ? 'selected':'' ?>>Rejected</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">
        <i class="icon-filter"></i> Filter
      </button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3><i class="icon-table"></i> Log Entries</h3>
  </div>
  <?php if ($rows): ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Student</th><th>Date</th><th>Time In</th><th>Time Out</th>
          <th>Hours</th><th>Status</th><th>Remarks</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="td-name"><?= e($r['full_name']) ?></div>
            <div class="td-muted"><?= e($r['student_number'] ?? '—') ?></div>
          </td>
          <td><?= fmtDate($r['log_date']) ?></td>
          <td><?= fmtTime($r['time_in']) ?></td>
          <td><?= fmtTime($r['time_out']) ?></td>
          <td><?= $r['hours_rendered'] > 0 ? '<strong>' . fmtHours((float)$r['hours_rendered']) . '</strong>' : '—' ?></td>
          <td><?= statusBadge($r['status']) ?></td>
          <td style="max-width:160px;font-size:.82rem;color:var(--text-2)">
            <?= $r['remarks'] ? e(mb_strimwidth($r['remarks'],0,50,'…')) : '—' ?>
          </td>
          <td style="white-space:nowrap">
            <?php if ($r['status'] === 'pending'): ?>
            <a href="<?= BASE_URL ?>/admin/pending.php?id=<?= $r['id'] ?>" class="btn btn-ghost btn-sm" style="margin-right:4px">
              <i class="icon-pencil"></i>
            </a>
            <?php endif; ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this log entry? This cannot be undone.')">
              <input type="hidden" name="_csrf"   value="<?= e(csrf()) ?>">
              <input type="hidden" name="_action" value="delete">
              <input type="hidden" name="log_id"  value="<?= $r['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);border-color:var(--red-bg)">
                <i class="icon-trash-2"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body">
    <div class="empty-state"><i class="icon-book-open"></i><p>No log entries match the selected filters.</p></div>
  </div>
  <?php endif; ?>
</div>

<?php renderFoot(); ?>