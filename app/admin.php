<?php
include 'db.php';

$user = $_SESSION['user'];

if ($user['role'] != 'admin') {
    die("Access denied");
}

$result = $conn->query("
    SELECT logs.*, users.name 
    FROM logs 
    JOIN users ON logs.user_id = users.id
");

while ($row = $result->fetch_assoc()) {
    echo "<p>
        {$row['name']} | {$row['date']} | {$row['total_hours']} hrs | {$row['status']}
        <a href='approve.php?id={$row['id']}'>Approve</a>
        <a href='reject.php?id={$row['id']}'>Reject</a>
    </p>";
}
?>