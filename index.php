<?php
// index.php
require_once __DIR__ . '/includes/app.php';

if (isLoggedIn()) {
    $r = currentUser()['role'];
    redirect(BASE_URL . ($r === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
}
redirect(BASE_URL . '/login.php');