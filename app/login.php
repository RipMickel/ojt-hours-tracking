<?php
// login.php
require_once __DIR__ . '/includes/app.php';

if (isLoggedIn()) {
    $r = currentUser()['role'];
    redirect(BASE_URL . ($r === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = attemptLogin($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($res['ok']) {
        redirect(BASE_URL . ($res['role'] === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
    }
    $err = $res['msg'];
}

$notice = match ($_GET['e'] ?? '') {
    'session' => ['msg' => 'Your session expired. Please sign in again.', 'type' => 'warning'],
    'access'  => ['msg' => 'You do not have permission to view that page.',  'type' => 'error'],
    default   => null,
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign In · OJT Tracker</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="login-page">

  <div class="login-panel">
    <div class="login-brand">
      <span class="login-brand-mark">OJT</span>
      <span class="login-brand-text">Hours<br>Tracker</span>
    </div>
    <h1 class="login-headline">Track every<br>hour of your<br><em>internship.</em></h1>
    <p class="login-sub">
      Log your daily time-in and time-out, accumulate
      approved hours, and hit your OJT target with ease.
    </p>
  </div>

  <div class="login-form-panel">
    <div class="login-form-inner">
      <h2>Welcome back</h2>
      <p>Sign in to your OJT Tracker account</p>

      <?php if ($notice): ?>
      <div class="alert alert-<?= e($notice['type']) ?>">
        <?= e($notice['msg']) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
      </div>
      <?php endif; ?>

      <?php if ($err): ?>
      <div class="alert alert-error">
        <?= e($err) ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
        <div class="form-group">
          <label class="form-label" for="email">Email address</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="you@example.com" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;padding:11px;">
          <i class="icon-log-in"></i> Sign In
        </button>
      </form>

      <div class="demo-box">
        <strong>Demo credentials (password: <code>ask the dev!</code>)</strong>
        <div class="demo-row">
          <span><span class="demo-tag">Admin</span> admin@ojt.local</span>
        </div>
        <div class="demo-row">
          <span><span class="demo-tag">Student</span> mickel@student.local</span>
        </div>
        <div class="demo-row">
          <span><span class="demo-tag">Student</span> asnairah@student.local</span>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>