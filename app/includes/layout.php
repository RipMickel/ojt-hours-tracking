<?php
// includes/layout.php

function renderHead(string $title): void
{
    $u    = currentUser();
    $role = $u['role'];
    $base = BASE_URL;
    $pend = ($role === 'admin') ? pendingCount() : 0;

    $navLinks = ($role === 'admin') ? [
        ['href' => $base . '/admin/dashboard.php',  'icon' => 'grid-2x2',        'label' => 'Dashboard'],
        ['href' => $base . '/admin/students.php',   'icon' => 'users',            'label' => 'Students'],
        ['href' => $base . '/admin/logs.php',        'icon' => 'book-open',        'label' => 'All Logs'],
        ['href' => $base . '/admin/pending.php',     'icon' => 'clock',            'label' => 'Pending', 'badge' => $pend],
    ] : [
        ['href' => $base . '/student/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        ['href' => $base . '/student/log.php',        'icon' => 'timer',            'label' => 'Log Hours'],
        ['href' => $base . '/student/history.php',    'icon' => 'calendar',         'label' => 'My History'],
    ];

    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title) ?> · OJT Tracker</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.min.css">
  <link rel="stylesheet" href="<?= $base ?>/assets/css/app.css">
  <style>
    .btn-signout {
    display: inline-flex;       /* keeps it inline but flexible */
    align-items: center;        /* vertical alignment */
    gap: 6px;                   /* space between icon & text */
    
    padding: 6px 12px;
    background: #e74c3c;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;

    transition: 0.3s ease;
}

/* Glow on hover */
.btn-signout:hover {
    background: #c0392b;
    box-shadow: 0 0 8px rgba(231, 76, 60, 0.7);
}

/* Icon alignment fix */
.btn-signout i {
    font-size: 14px;
    display: inline-block;
}
    </style>
</head>
<body data-role="<?= $role ?>">

<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="brand-mark">OJT</span>
    <span class="brand-sub">Tracker</span>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($navLinks as $l): ?>
    <?php $active = str_contains($current, basename($l['href'])); ?>
    <a href="<?= e($l['href']) ?>" class="nav-item<?= $active ? ' active' : '' ?>">
      <i class="icon-<?= $l['icon'] ?>"></i>
      <span><?= e($l['label']) ?></span>
      <?php if (!empty($l['badge']) && $l['badge'] > 0): ?>
        <em class="nav-badge"><?= $l['badge'] ?></em>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-user">
    <div class="user-avatar"><?= e($u['initials']) ?></div>
    <div class="user-info">
      <span class="user-name"><?= e($u['name']) ?></span>
      <span class="user-role"><?= ucfirst($role) ?></span>
    </div>
<a class="btn btn-signout" href="<?= BASE_URL ?>/auth/signout.php">
    <i class="icon-log-out"></i> Sign out
</a>
    </a>
  </div>
</aside>

<div class="layout-main">
  <header class="topbar">
    <div class="topbar-title">
      <h1><?= e($title) ?></h1>
      <span class="topbar-date"><?= date('l, F j Y') ?></span>
    </div>
    <button class="mobile-menu-btn" id="menuBtn"><i class="icon-menu"></i></button>
  </header>
  <main class="page-body">
    <?php
    $flash = popFlash();
    if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
      <?= e($flash['msg']) ?>
      <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>
<?php
}

function renderFoot(): void
{
    echo '</main></div>';
    ?>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
<?php
}