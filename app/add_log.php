<?php
include 'db.php';

$user = $_SESSION['user'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    $hours = (strtotime($time_out) - strtotime($time_in)) / 3600;

    $conn->query("INSERT INTO logs (user_id, date, time_in, time_out, total_hours)
                  VALUES ('{$user['id']}', '$date', '$time_in', '$time_out', '$hours')");

    echo "Log added!";
}
?>

<form method="POST">
    <h3>Add OJT Log</h3>
    Date: <input type="date" name="date"><br>
    Time In: <input type="time" name="time_in"><br>
    Time Out: <input type="time" name="time_out"><br>
    <button type="submit">Submit</button>
</form>