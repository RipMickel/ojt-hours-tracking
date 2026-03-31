<?php
// includes/app.php — Bootstrap

// ── Timezone (set FIRST, before any date/time calls) ─────────
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/database.php';

// ── Auto-detect base URL ──────────────────────────────────────
(function () {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $parts  = explode('/', trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'));
    $drop   = ['admin', 'student', 'includes', 'actions', 'config'];
    while (!empty($parts) && in_array(end($parts), $drop, true)) {
        array_pop($parts);
    }
    $path = implode('/', $parts);
    define('BASE_URL', $scheme . '://' . $host . ($path ? '/' . $path : ''));
})();

require_once __DIR__ . '/auth.php';

// ── Formatting helpers ────────────────────────────────────────

function fmtHours(float $h): string
{
    $hrs  = floor($h);
    $mins = (int) round(($h - $hrs) * 60);
    return $mins > 0 ? "{$hrs}h {$mins}m" : "{$hrs}h";
}

/**
 * Format a TIME column value (e.g. "08:00:00") as "8:00 AM".
 * TIME columns are timezone-neutral — anchor to today in Asia/Manila.
 */
function fmtTime(?string $t): string
{
    if (!$t) return '—';
    $dt = new DateTime('today ' . $t, new DateTimeZone('Asia/Manila'));
    return $dt->format('g:i A');
}

/**
 * Format a DATE column value (e.g. "2026-03-31") as "Mar 31, 2026".
 */
function fmtDate(?string $d): string
{
    if (!$d) return '—';
    $dt = new DateTime($d . ' 00:00:00', new DateTimeZone('Asia/Manila'));
    return $dt->format('M j, Y');
}

/**
 * Format a DATE column value as a full label e.g. "Tuesday, March 31, 2026".
 */
function fmtDateFull(?string $d): string
{
    if (!$d) return '—';
    $dt = new DateTime($d . ' 00:00:00', new DateTimeZone('Asia/Manila'));
    return $dt->format('l, F j, Y');
}

/**
 * Format a TIMESTAMP/DATETIME column stored in UTC.
 * Converts UTC → Asia/Manila before display.
 */
function fmtDatetime(?string $ts): string
{
    if (!$ts) return '—';
    $dt = new DateTime($ts, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
    return $dt->format('M j, Y g:i A');
}

function statusBadge(string $status): string
{
    $map = [
        'pending'  => ['label' => 'Pending',  'class' => 'badge-pending'],
        'approved' => ['label' => 'Approved', 'class' => 'badge-approved'],
        'rejected' => ['label' => 'Rejected', 'class' => 'badge-rejected'],
    ];
    $b = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-pending'];
    return '<span class="badge ' . $b['class'] . '">' . $b['label'] . '</span>';
}

function pct(float $done, int $req): int
{
    return $req > 0 ? min(100, (int) round($done / $req * 100)) : 0;
}

function calcHours(string $in, string $out): float
{
    $tz    = new DateTimeZone('Asia/Manila');
    $start = new DateTime('today ' . $in,  $tz);
    $end   = new DateTime('today ' . $out, $tz);

    $hours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;

    // Deduct 1 hour if the work period overlaps the 12:00–13:00 lunch window
    $lunchStart = new DateTime('today 12:00:00', $tz);
    $lunchEnd   = new DateTime('today 13:00:00', $tz);

    if ($start < $lunchEnd && $end > $lunchStart) {
        $hours -= 1;
    }

    return $hours > 0 ? round($hours, 2) : 0.0;
}

// ── Query helpers ─────────────────────────────────────────────

function studentApprovedHours(int $uid): float
{
    $s = db()->prepare(
        "SELECT COALESCE(SUM(hours_rendered),0) FROM ojt_logs
         WHERE user_id=? AND status='approved'"
    );
    $s->execute([$uid]);
    return (float) $s->fetchColumn();
}

function pendingCount(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM ojt_logs WHERE status='pending'")->fetchColumn();
}