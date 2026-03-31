<?php
// index.php
require_once __DIR__ . '/app/includes/app.php';

if (isLoggedIn()) {
    $r = currentUser()['role'];
    redirect(BASE_URL . ($r === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
}
redirect(BASE_URL . '/app/login.php');