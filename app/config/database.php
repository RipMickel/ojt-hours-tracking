<?php
// includes/db.php — PDO connection (singleton)

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'ojt_tracker');
define('DB_USER',    'root');       // ← change to your MySQL username
define('DB_PASS',    '');           // ← change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '+08:00'"); // Asia/Manila (UTC+8)
        } catch (PDOException $e) {
            // Pretty error for development — remove in production
            http_response_code(500);
            echo '<pre style="font:13px monospace;padding:20px;background:#1e1e2e;color:#f38ba8;border-radius:8px;margin:40px auto;max-width:700px">';
            echo '<strong>⚠ Database connection failed</strong>' . PHP_EOL . PHP_EOL;
            echo htmlspecialchars($e->getMessage()) . PHP_EOL . PHP_EOL;
            echo 'Check your credentials in <strong>includes/db.php</strong>';
            echo '</pre>';
            exit;
        }
    }

    return $pdo;
}