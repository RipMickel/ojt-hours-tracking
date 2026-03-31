<?php
include 'db.php';

$id = $_GET['id'];
$conn->query("UPDATE logs SET status='rejected' WHERE id=$id");

header("Location: admin.php");
?>