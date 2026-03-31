<?php
// student/history.php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$u   = currentUser();
$uid = $u['id'];
$db  = db();

$logs = $db->prepare(
    "SELECT * FROM ojt_logs WHERE user_id=? ORDER BY log_date DESC"
);
$logs->execute([$uid]);
$rows = $logs->fetchAll();

$approved = studentApprovedHours($uid);
$prof     = $db->prepare('SELECT required_hours FROM users WHERE id=?');
$prof->execute([$uid]);
$req = (int) $prof->fetchColumn();

$counts = array_count_values(array_column($rows, 'status'));

renderHead('My History');
?>

<div class="section-header">
  <div>
    <h2>My Log History</h2>
    <p><?= count($rows) ?> total entr<?= count($rows) !== 1 ? 'ies' : 'y' ?></p>
  </div>
  <a href="<?= BASE_URL ?>/student/log.php" class="btn btn-primary">
    <i class="icon-plus"></i> New Log
  </a>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card">
    <div class="stat-icon ic-blue"><i class="icon-clock"></i></div>
    <div class="stat-label">Approved Hours</div>
    <div class="stat-value"><?= fmtHours($approved) ?></div>
    <div class="stat-sub">out of <?= $req ?>h</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-green"><i class="icon-check-circle"></i></div>
    <div class="stat-label">Approved</div>
    <div class="stat-value"><?= $counts['approved'] ?? 0 ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-amber"><i class="icon-hourglass"></i></div>
    <div class="stat-label">Pending</div>
    <div class="stat-value"><?= $counts['pending'] ?? 0 ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-red"><i class="icon-x-circle"></i></div>
    <div class="stat-label">Rejected</div>
    <div class="stat-value"><?= $counts['rejected'] ?? 0 ?></div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3><i class="icon-calendar"></i> All Entries</h3>
  </div>
  <?php if ($rows): ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Date</th><th>Time In</th><th>Time Out</th>
          <th>Hours</th><th>Status</th><th>Remarks</th><th>Admin Note</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="td-muted"><?= count($rows) - $i ?></td>
          <td class="td-name"><?= fmtDate($r['log_date']) ?></td>
          <td><?= fmtTime($r['time_in']) ?></td>
          <td><?= fmtTime($r['time_out']) ?></td>
          <td><?= $r['hours_rendered'] > 0 ? '<strong>' . fmtHours((float)$r['hours_rendered']) . '</strong>' : '—' ?></td>
          <td><?= statusBadge($r['status']) ?></td>
          <td style="max-width:180px;color:var(--text-2);font-size:.82rem">
            <?= $r['remarks'] ? e(mb_strimwidth($r['remarks'], 0, 55, '…')) : '—' ?>
          </td>
          <td style="max-width:180px;font-size:.82rem;color:<?= $r['status']==='rejected'?'var(--red)':'var(--text-2)' ?>">
            <?= $r['admin_note'] ? e(mb_strimwidth($r['admin_note'], 0, 55, '…')) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="padding:12px 16px;font-weight:600;text-align:right;font-size:.82rem;color:var(--text-2)">Total approved:</td>
          <td colspan="4" style="padding:12px 16px;font-weight:700;color:var(--green)"><?= fmtHours($approved) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body">
    <div class="empty-state">
      <i class="icon-book-open"></i>
      <p>No log entries yet. Start by logging today's hours.</p>
      <a href="<?= BASE_URL ?>/student/log.php" class="btn btn-primary btn-sm">
        <i class="icon-plus"></i> Log Hours
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php renderFoot(); ?>