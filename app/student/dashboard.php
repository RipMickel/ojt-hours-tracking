<?php
// student/dashboard.php
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('student');

$u   = currentUser();
$uid = $u['id'];
$db  = db();

$prof = $db->prepare('SELECT * FROM users WHERE id = ?');
$prof->execute([$uid]);
$me = $prof->fetch();

$approved = studentApprovedHours($uid);
$req      = (int) $me['required_hours'];
$progress = pct($approved, $req);
$remain   = max(0.0, $req - $approved);

$sc = $db->prepare("SELECT status, COUNT(*) n FROM ojt_logs WHERE user_id = ? GROUP BY status");
$sc->execute([$uid]);
$counts = array_column($sc->fetchAll(), 'n', 'status');

$tl = $db->prepare("SELECT * FROM ojt_logs WHERE user_id = ? AND log_date = CURDATE() LIMIT 1");
$tl->execute([$uid]);
$today = $tl->fetch();

$rl = $db->prepare("SELECT * FROM ojt_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 6");
$rl->execute([$uid]);
$recent = $rl->fetchAll();

renderHead('Dashboard');
?>

<div class="section-header">
  <div>
    <h2>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $u['name'])[0]) ?> 👋</h2>
    <p>Here's your OJT progress at a glance.</p>
  </div>
  <a href="<?= BASE_URL ?>/student/log.php" class="btn btn-primary">
    <i class="icon-plus"></i> Log Hours
  </a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon ic-blue"><i class="icon-clock"></i></div>
    <div class="stat-label">Approved Hours</div>
    <div class="stat-value"><?= fmtHours($approved) ?></div>
    <div class="stat-sub">of <?= $req ?>h required</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-green"><i class="icon-trending-up"></i></div>
    <div class="stat-label">Progress</div>
    <div class="stat-value"><?= $progress ?>%</div>
    <div class="stat-sub"><?= fmtHours($remain) ?> remaining</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-amber"><i class="icon-hourglass"></i></div>
    <div class="stat-label">Pending</div>
    <div class="stat-value"><?= $counts['pending'] ?? 0 ?></div>
    <div class="stat-sub">awaiting admin review</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon ic-navy"><i class="icon-book-open"></i></div>
    <div class="stat-label">Total Logs</div>
    <div class="stat-value"><?= array_sum($counts) ?></div>
    <div class="stat-sub"><?= $counts['rejected'] ?? 0 ?> rejected</div>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    <div class="progress-meta">
      <span>OJT Completion — <strong><?= fmtHours($approved) ?> approved</strong></span>
      <span><strong><?= $progress ?>%</strong></span>
    </div>
    <div class="progress-bar-track">
      <div class="progress-bar-fill <?= $progress >= 100 ? 'done' : '' ?>"
           data-pct="<?= $progress ?>" style="width:0%"></div>
    </div>
    <?php if ($progress >= 100): ?>
    <p style="font-size:.8rem;color:var(--green);margin-top:6px;font-weight:600">
      🎉 Congratulations! You've completed your required hours.
    </p>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head">
      <h3><i class="icon-calendar-check"></i> Today</h3>
      <?php if (!$today): ?>
      <a href="<?= BASE_URL ?>/student/log.php" class="btn btn-primary btn-sm">Log Now</a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if ($today): ?>
        <div class="info-list">
          <div class="info-row"><span class="info-key">Status</span><span class="info-value"><?= statusBadge($today['status']) ?></span></div>
          <div class="info-row"><span class="info-key">Time In</span><span class="info-value"><?= fmtTime($today['time_in']) ?></span></div>
          <div class="info-row"><span class="info-key">Time Out</span>
            <span class="info-value">
              <?php if ($today['time_out']): ?>
                <?= fmtTime($today['time_out']) ?>
              <?php else: ?>
                <span style="color:var(--amber);font-weight:600">Not yet logged</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="info-row"><span class="info-key">Hours</span><span class="info-value"><?= $today['hours_rendered'] > 0 ? fmtHours((float)$today['hours_rendered']) : '—' ?></span></div>
        </div>
        <?php if (!$today['time_out']): ?>
        <a href="<?= BASE_URL ?>/student/log.php" class="btn btn-primary btn-full" style="margin-top:14px">
          <i class="icon-timer-off"></i> Log Time Out
        </a>
        <?php endif; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:20px 0">
          <i class="icon-calendar-x"></i>
          <p>No log entry for today yet.</p>
          <a href="<?= BASE_URL ?>/student/log.php" class="btn btn-primary btn-sm">Log Time In</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3><i class="icon-user"></i> My Profile</h3></div>
    <div class="card-body">
      <div class="info-list">
        <?php
        $fields = [
          'Student No.' => $me['student_number'] ?? '—',
          'Course'      => $me['course']  ?? '—',
          'Company'     => $me['company'] ?? '—',
          'Email'       => $me['email'],
          'Req. Hours'  => $req . ' hours',
        ];
        foreach ($fields as $k => $v): ?>
        <div class="info-row">
          <span class="info-key"><?= e($k) ?></span>
          <span class="info-value" style="max-width:60%;word-break:break-all;text-align:right"><?= e($v) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($recent): ?>
<div class="card" style="margin-top:20px">
  <div class="card-head">
    <h3><i class="icon-list"></i> Recent Logs</h3>
    <a href="<?= BASE_URL ?>/student/history.php" class="btn btn-ghost btn-sm">View all</a>
  </div>
  <div class="table-scroll">
    <table>
      <thead>
        <tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Hours</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $row): ?>
        <tr>
          <td class="td-name"><?= fmtDate($row['log_date']) ?></td>
          <td><?= fmtTime($row['time_in']) ?></td>
          <td><?= fmtTime($row['time_out']) ?></td>
          <td><?= $row['hours_rendered'] > 0 ? fmtHours((float)$row['hours_rendered']) : '—' ?></td>
          <td><?= statusBadge($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php renderFoot(); ?>