<?php
// includes/app.php — Bootstrap

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

function fmtTime(?string $t): string
{
    return $t ? date('g:i A', strtotime($t)) : '—';
}

function fmtDate(?string $d): string
{
    return $d ? date('M j, Y', strtotime($d)) : '—';
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
    $start = strtotime($in);
    $end   = strtotime($out);

    $hours = ($end - $start) / 3600;

    // Deduct 1 hour if work period covers 12:00–1:00
    $lunchStart = strtotime(date('Y-m-d 12:00:00', $start));
    $lunchEnd   = strtotime(date('Y-m-d 13:00:00', $start));

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