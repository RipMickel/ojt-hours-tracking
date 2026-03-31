<?php
include 'db.php';

$id = $_GET['id'];
$conn->query("UPDATE logs SET status='approved' WHERE id=$id");

header("Location: admin.php");
?>