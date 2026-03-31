<?php
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
}

$user = $_SESSION['user'];

echo "<h2>Welcome " . $user['name'] . "</h2>";
echo "<a href='logout.php'>Logout</a><br>";

if ($user['role'] == 'student') {
    echo "<a href='add_log.php'>Add Log</a><br>";
}

if ($user['role'] == 'admin') {
    echo "<a href='admin.php'>Admin Panel</a><br>";
}
?>