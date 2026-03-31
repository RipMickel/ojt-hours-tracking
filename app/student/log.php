<?php
// student/log.php — Single entry per day only
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$u   = currentUser();
$uid = $u['id'];
$db  = db();

// ── Selected date (default today) ────────────────────────────
$logDate = $_POST['log_date'] ?? $_GET['date'] ?? date('Y-m-d');

// ── OPTIONAL: Load latest log for display only ───────────────
$todayStmt = $db->prepare(
    "SELECT * FROM ojt_logs 
     WHERE user_id=? AND log_date=? 
     ORDER BY id DESC LIMIT 1"
);
$todayStmt->execute([$uid, $logDate]);
$today = $todayStmt->fetch();

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $timeIn   = trim($_POST['time_in']  ?? '');
    $timeOut  = trim($_POST['time_out'] ?? '');
    $remarks  = trim($_POST['remarks']  ?? '');
    $logDate  = $_POST['log_date'] ?? date('Y-m-d');

    // Validate
    $errors = [];
    if (!$timeIn)  $errors[] = 'Time In is required.';
    if ($timeOut && $timeOut <= $timeIn) $errors[] = 'Time Out must be after Time In.';

    $hours = ($timeIn && $timeOut) ? calcHours($timeIn, $timeOut) : 0.0;

    if (empty($errors)) {

        // 🔒 CHECK: already has log for this date?
        $checkStmt = $db->prepare(
            "SELECT COUNT(*) FROM ojt_logs WHERE user_id=? AND log_date=?"
        );
        $checkStmt->execute([$uid, $logDate]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            // ❌ Prevent duplicate log
            $errors[] = 'You cannot log again for this date.';
        } else {
            // ✅ Insert if no existing log
            $stmt = $db->prepare(
                "INSERT INTO ojt_logs (user_id, log_date, time_in, time_out, hours_rendered, remarks, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([$uid, $logDate, $timeIn, $timeOut ?: null, $hours, $remarks ?: null]);

            flash('Log submitted successfully!', 'success');
            redirect(BASE_URL . '/student/log.php?date=' . $logDate);
        }
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
    <p>Record your time for <?= date('l, F j, Y', strtotime($logDate)) ?></p>
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

<div class="grid-2" style="align-items:start">

  <!-- Log form -->
  <div class="card">
    <div class="card-head">
      <h3><i class="icon-timer"></i> New Log Entry</h3>
    </div>
    <div class="card-body">
      <form method="POST" class="log-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">

        <!-- Date -->
        <div class="form-group">
          <label class="form-label">Date <span class="req">*</span></label>
          <input type="date" name="log_date" class="form-control"
                 value="<?= e($logDate) ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Time In <span class="req">*</span></label>
            <input type="time" name="time_in" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Time Out</label>
            <input type="time" name="time_out" class="form-control">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-full">
          <i class="icon-save"></i> Submit Log
        </button>
      </form>
    </div>
  </div>

  <!-- Progress sidebar -->
  <div>
    <div class="card">
      <div class="card-head"><h3>Your Progress</h3></div>
      <div class="card-body">
        <?php $p = pct($approved, $req); ?>
        <div><?= fmtHours($approved) ?> / <?= $req ?> hrs</div>
        <div class="progress-bar-track">
          <div class="progress-bar-fill" style="width:<?= $p ?>%"></div>
        </div>
      </div>
    </div>

    <!-- Latest log preview -->
    <?php if ($today): ?>
    <div class="card" style="margin-top:16px">
      <div class="card-head"><h3>Latest Entry (<?= fmtDate($logDate) ?>)</h3></div>
      <div class="card-body">
        <div>Time In: <?= fmtTime($today['time_in']) ?></div>
        <div>Time Out: <?= fmtTime($today['time_out']) ?></div>
        <div>Hours: <?= fmtHours((float)$today['hours_rendered']) ?></div>
        <div>Status: <?= statusBadge($today['status']) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php renderFoot(); ?>