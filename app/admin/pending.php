<?php
// admin/pending.php — Review, approve, or reject pending logs
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');

$db = db();

// ── Handle approve / reject POST ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['_action'] ?? '';
    $logId  = (int) ($_POST['log_id'] ?? 0);

    if ($logId && in_array($action, ['approve', 'reject'], true)) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $note      = trim($_POST['admin_note'] ?? '');
        $stmt = $db->prepare("UPDATE ojt_logs SET status=?, admin_note=? WHERE id=? AND status='pending'");
        $stmt->execute([$newStatus, $note ?: null, $logId]);
        flash('Log entry ' . $newStatus . '.', $newStatus === 'approved' ? 'success' : 'info');
    }
    redirect(BASE_URL . '/admin/pending.php');
}

// ── Single-entry review mode ─────────────────────────────────
$reviewId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$reviewing = null;
if ($reviewId) {
    $s = $db->prepare(
        "SELECT l.*,u.full_name,u.student_number,u.course,u.company,u.required_hours,
                COALESCE(SUM(CASE WHEN l2.status='approved' THEN l2.hours_rendered ELSE 0 END),0) approved_total
         FROM ojt_logs l
         JOIN users u ON u.id=l.user_id
         LEFT JOIN ojt_logs l2 ON l2.user_id=l.user_id
         WHERE l.id=? AND l.status='pending'
         GROUP BY l.id"
    );
    $s->execute([$reviewId]);
    $reviewing = $s->fetch();
}

// ── All pending list ─────────────────────────────────────────
$pending = $db->query(
    "SELECT l.*,u.full_name,u.student_number
     FROM ojt_logs l JOIN users u ON u.id=l.user_id
     WHERE l.status='pending'
     ORDER BY l.log_date ASC"
)->fetchAll();

renderHead('Pending Review');
?>

<div class="section-header">
  <div>
    <h2>Pending Review</h2>
    <p><?= count($pending) ?> log<?= count($pending) !== 1 ? 's' : '' ?> awaiting your action</p>
  </div>
</div>

<?php if ($reviewing): ?>
<!-- ── Single review panel ──────────────────────────────── -->
<div class="review-panel">
  <div class="card-head">
    <h3><i class="icon-file-check"></i> Reviewing Log #<?= $reviewing['id'] ?></h3>
    <a href="<?= BASE_URL ?>/admin/pending.php" class="btn btn-ghost btn-sm"><i class="icon-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <div class="grid-2" style="gap:24px">
      <div>
        <h4 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:12px">Log Details</h4>
        <div class="info-list">
          <?php
          $fields = [
            'Student'   => $reviewing['full_name'],
            'Student #' => $reviewing['student_number'] ?? '—',
            'Course'    => $reviewing['course'] ?? '—',
            'Company'   => $reviewing['company'] ?? '—',
            'Date'      => fmtDate($reviewing['log_date']),
            'Time In'   => fmtTime($reviewing['time_in']),
            'Time Out'  => fmtTime($reviewing['time_out']),
            'Hours'     => $reviewing['hours_rendered'] > 0 ? fmtHours((float)$reviewing['hours_rendered']) : '—',
          ];
          foreach ($fields as $k => $v): ?>
          <div class="info-row">
            <span class="info-key"><?= e($k) ?></span>
            <span class="info-value"><?= e($v) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($reviewing['remarks']): ?>
        <div style="margin-top:14px;padding:12px;background:var(--canvas);border-radius:var(--radius);font-size:.85rem;color:var(--text-2)">
          <strong style="display:block;margin-bottom:4px;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3)">Student Remarks</strong>
          <?= e($reviewing['remarks']) ?>
        </div>
        <?php endif; ?>
      </div>
      <div>
        <h4 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);margin-bottom:12px">Student Progress</h4>
        <?php
        $approved = (float)$reviewing['approved_total'];
        $req      = (int)$reviewing['required_hours'];
        $p        = pct($approved, $req);
        $afterApprove = $approved + (float)$reviewing['hours_rendered'];
        $pAfter   = pct($afterApprove, $req);
        ?>
        <div style="background:var(--canvas);border-radius:var(--radius);padding:16px;margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:8px">
            <span style="color:var(--text-2)">Currently approved</span>
            <strong><?= fmtHours($approved) ?> / <?= $req ?>h (<?= $p ?>%)</strong>
          </div>
          <div class="progress-bar-track">
            <div class="progress-bar-fill <?= $p>=100?'done':'' ?>" data-pct="<?= $p ?>" style="width:0%"></div>
          </div>
          <?php if ($reviewing['hours_rendered'] > 0): ?>
          <div style="margin-top:10px;font-size:.78rem;color:var(--green)">
            ✓ If approved: <?= fmtHours($afterApprove) ?> (<?= $pAfter ?>%)
          </div>
          <?php endif; ?>
        </div>

        <form method="POST">
          <input type="hidden" name="_csrf"   value="<?= e(csrf()) ?>">
          <input type="hidden" name="log_id"  value="<?= $reviewing['id'] ?>">
          <div class="form-group">
            <label class="form-label">Admin Note <span style="font-weight:400;color:var(--text-3)">(optional)</span></label>
            <textarea name="admin_note" class="form-control" rows="3" placeholder="Add a note for the student…"></textarea>
          </div>
          <div style="display:flex;gap:10px">
            <button type="submit" name="_action" value="approve" class="btn btn-success" style="flex:1;justify-content:center">
              <i class="icon-check"></i> Approve
            </button>
            <button type="submit" name="_action" value="reject" class="btn btn-danger" style="flex:1;justify-content:center"
                    onclick="return confirm('Reject this log entry?')">
              <i class="icon-x"></i> Reject
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── Pending list ──────────────────────────────────────── -->
<div class="card">
  <div class="card-head">
    <h3><i class="icon-list"></i> All Pending Entries</h3>
  </div>
  <?php if ($pending): ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr><th>Student</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Quick Action</th><th>Review</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pending as $r): ?>
        <tr <?= $reviewId === (int)$r['id'] ? 'style="background:#fffbf0"' : '' ?>>
          <td>
            <div class="td-name"><?= e($r['full_name']) ?></div>
            <div class="td-muted"><?= e($r['student_number'] ?? '—') ?></div>
          </td>
          <td><?= fmtDate($r['log_date']) ?></td>
          <td><?= fmtTime($r['time_in']) ?></td>
          <td><?= fmtTime($r['time_out']) ?></td>
          <td><?= $r['hours_rendered'] > 0 ? '<strong>' . fmtHours((float)$r['hours_rendered']) . '</strong>' : '—' ?></td>
          <td style="white-space:nowrap">
            <form method="POST" style="display:inline">
              <input type="hidden" name="_csrf"   value="<?= e(csrf()) ?>">
              <input type="hidden" name="log_id"  value="<?= $r['id'] ?>">
              <button type="submit" name="_action" value="approve" class="btn btn-success btn-sm" style="margin-right:4px">
                <i class="icon-check"></i> Approve
              </button>
              <button type="submit" name="_action" value="reject" class="btn btn-danger btn-sm"
                      onclick="return confirm('Reject this entry?')">
                <i class="icon-x"></i> Reject
              </button>
            </form>
          </td>
          <td>
            <a href="?id=<?= $r['id'] ?>" class="btn btn-ghost btn-sm">
              <i class="icon-eye"></i> Detail
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body">
    <div class="empty-state" style="padding:40px 0">
      <i class="icon-check-circle" style="color:var(--green);opacity:1;font-size:3rem"></i>
      <p style="color:var(--green);font-weight:600;font-size:1rem">All caught up! No pending entries.</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php renderFoot(); ?>