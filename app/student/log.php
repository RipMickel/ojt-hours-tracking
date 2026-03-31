<?php
// student/log.php — Log or update today's hours
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$u   = currentUser();
$uid = $u['id'];
$db  = db();

// ── Load today's log if it exists ────────────────────────────
$todayStmt = $db->prepare("SELECT * FROM ojt_logs WHERE user_id=? AND log_date=CURDATE() LIMIT 1");
$todayStmt->execute([$uid]);
$today = $todayStmt->fetch();

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $timeIn   = trim($_POST['time_in']  ?? '');
    $timeOut  = trim($_POST['time_out'] ?? '');
    $remarks  = trim($_POST['remarks']  ?? '');
    $logDate  = date('Y-m-d');

    // Validate
    $errors = [];
    if (!$timeIn)  $errors[] = 'Time In is required.';
    if ($timeOut && $timeOut <= $timeIn) $errors[] = 'Time Out must be after Time In.';

    $hours = ($timeIn && $timeOut) ? calcHours($timeIn, $timeOut) : 0.0;

    if (empty($errors)) {
        if ($today) {
            // Update existing
            if ($today['status'] !== 'pending') {
                flash('This log has already been reviewed and cannot be edited.', 'warning');
                redirect(BASE_URL . '/student/log.php');
            }
            $stmt = $db->prepare(
                "UPDATE ojt_logs SET time_in=?, time_out=?, hours_rendered=?, remarks=? WHERE id=?"
            );
            $stmt->execute([$timeIn, $timeOut ?: null, $hours, $remarks ?: null, $today['id']]);
            flash('Log updated successfully.', 'success');
        } else {
            // Insert new
            $stmt = $db->prepare(
                "INSERT INTO ojt_logs (user_id, log_date, time_in, time_out, hours_rendered, remarks, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$uid, $logDate, $timeIn, $timeOut ?: null, $hours, $remarks ?: null]);
            flash('Log submitted successfully!', 'success');
        }
        redirect(BASE_URL . '/student/log.php');
    }
}

$approved = studentApprovedHours($uid);
$profStmt = $db->prepare('SELECT required_hours FROM users WHERE id=?');
$profStmt->execute([$uid]);
$req = (int) $profStmt->fetchColumn();

renderHead('Log Hours');
?>

<div class="section-header">
  <div>
    <h2>Log Hours</h2>
    <p>Record your time in and time out for today, <?= date('l, F j') ?></p>
  </div>
  <a href="<?= BASE_URL ?>/student/history.php" class="btn btn-ghost btn-sm">
    <i class="icon-calendar"></i> My History
  </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
  <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  <button class="alert-close" onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>

<?php if ($today && $today['status'] !== 'pending'): ?>
<div class="alert alert-<?= $today['status'] === 'approved' ? 'success' : 'warning' ?>">
  Today's log has been <strong><?= $today['status'] ?></strong> and can no longer be edited.
  <?php if ($today['admin_note']): ?>
  Admin note: <em><?= e($today['admin_note']) ?></em>
  <?php endif; ?>
  <button class="alert-close" onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>

<div class="grid-2" style="align-items:start">

  <!-- Log form -->
  <div class="card">
    <div class="card-head">
      <h3><i class="icon-timer"></i> <?= $today ? 'Update Today\'s Log' : 'New Log Entry' ?></h3>
      <?php if ($today): ?>
      <span class="badge badge-<?= $today['status'] ?>"><?= ucfirst($today['status']) ?></span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php $disabled = ($today && $today['status'] !== 'pending') ? 'disabled' : ''; ?>
      <form method="POST" class="log-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="time_in">Time In <span class="req">*</span></label>
            <input type="time" id="time_in" name="time_in" class="form-control" data-time-in
                   value="<?= e($today['time_in'] ?? $_POST['time_in'] ?? '') ?>"
                   required <?= $disabled ?>>
          </div>
          <div class="form-group">
            <label class="form-label" for="time_out">Time Out</label>
            <input type="time" id="time_out" name="time_out" class="form-control" data-time-out
                   value="<?= e($today['time_out'] ?? $_POST['time_out'] ?? '') ?>"
                   <?= $disabled ?>>
            <p class="form-hint">Leave blank if you haven't finished yet.</p>
          </div>
        </div>

        <div class="form-group" style="margin-top:4px">
          <label class="form-label">Hours Preview</label>
          <div class="hours-preview">— hrs</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="remarks">Remarks / Tasks Done</label>
          <textarea id="remarks" name="remarks" class="form-control" rows="3"
                    placeholder="Briefly describe what you worked on today…"
                    <?= $disabled ?>><?= e($today['remarks'] ?? $_POST['remarks'] ?? '') ?></textarea>
        </div>

        <?php if (!$disabled): ?>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:4px">
          <i class="icon-save"></i> <?= $today ? 'Update Log' : 'Submit Log' ?>
        </button>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Progress sidebar -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-head"><h3><i class="icon-trending-up"></i> Your Progress</h3></div>
      <div class="card-body">
        <?php $p = pct($approved, $req); ?>
        <div class="progress-meta">
          <span><?= fmtHours($approved) ?> approved</span>
          <span><?= $p ?>%</span>
        </div>
        <div class="progress-bar-track" style="margin-bottom:10px">
          <div class="progress-bar-fill <?= $p>=100?'done':'' ?>" data-pct="<?= $p ?>" style="width:0%"></div>
        </div>
        <div style="font-size:.82rem;color:var(--text-2)">
          <?= fmtHours(max(0.0, $req - $approved)) ?> remaining of <?= $req ?>h required
        </div>
        <?php if ($p >= 100): ?>
        <p style="margin-top:10px;font-size:.82rem;color:var(--green);font-weight:600">🎉 Target reached!</p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($today): ?>
    <div class="card">
      <div class="card-head"><h3><i class="icon-info"></i> Today's Entry</h3></div>
      <div class="card-body">
        <div class="info-list">
          <div class="info-row"><span class="info-key">Status</span><span class="info-value"><?= statusBadge($today['status']) ?></span></div>
          <div class="info-row"><span class="info-key">Time In</span><span class="info-value"><?= fmtTime($today['time_in']) ?></span></div>
          <div class="info-row"><span class="info-key">Time Out</span><span class="info-value"><?= fmtTime($today['time_out']) ?></span></div>
          <div class="info-row"><span class="info-key">Hours</span><span class="info-value"><?= $today['hours_rendered'] > 0 ? fmtHours((float)$today['hours_rendered']) : '—' ?></span></div>
        </div>
        <?php if ($today['admin_note']): ?>
        <div style="margin-top:12px;padding:10px;background:var(--canvas);border-radius:var(--radius);font-size:.82rem;color:var(--text-2)">
          <strong style="display:block;margin-bottom:3px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em">Admin Note</strong>
          <?= e($today['admin_note']) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php renderFoot(); ?>