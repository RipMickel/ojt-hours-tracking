<?php
// debug_tz.php — place this in your /app root and open it in browser
// DELETE this file after debugging!

echo '<pre>';

// 1. What does PHP think the timezone is right now?
echo "PHP default timezone (before set): " . date_default_timezone_get() . "\n";

// 2. Force it
date_default_timezone_set('Asia/Manila');
echo "PHP default timezone (after set):  " . date_default_timezone_get() . "\n\n";

// 3. What does date() return?
echo "date('Y-m-d H:i:s'):               " . date('Y-m-d H:i:s') . "\n";
echo "date('l, F j, Y'):                 " . date('l, F j, Y') . "\n\n";

// 4. What does DateTime return?
$dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
echo "DateTime Asia/Manila:              " . $dt->format('l, F j, Y H:i:s') . "\n\n";

// 5. What does the server think the time is in UTC?
$utc = new DateTime('now', new DateTimeZone('UTC'));
echo "DateTime UTC:                      " . $utc->format('l, F j, Y H:i:s') . "\n\n";

// 6. Check php.ini timezone setting
echo "php.ini date.timezone:             " . ini_get('date.timezone') . "\n";
echo "Server time() unix timestamp:      " . time() . "\n";
echo "Human readable timestamp:          " . date('r') . "\n";

echo '</pre>';