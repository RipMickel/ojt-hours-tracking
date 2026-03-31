<?php

// Start session FIRST
session_start();

// Bootstrap: DB
require_once __DIR__ . '/../config/database.php';

// Capture user ID before destroying session
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

// Clear session data
$_SESSION = [];

// Delete session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: /index.php");
exit;

?>