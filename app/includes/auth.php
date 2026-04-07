<?php
// includes/auth.php — Session bootstrap and auth helpers

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('SESSION_TTL', 7200); // 2 hours

/* ── Guards ──────────────────────────────────────────────────── */

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php?e=session');
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        redirect(BASE_URL . '/login.php?e=access');
    }
}

/* ── State checks ────────────────────────────────────────────── */

function isLoggedIn(): bool
{
    if (empty($_SESSION['uid'])) return false;

    // Sliding session expiry
    if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > SESSION_TTL) {
        sessionDestroy();
        return false;
    }
    $_SESSION['last_active'] = time();
    return true;
}

function currentUser(): array
{
    return [
        'id'             => $_SESSION['uid']            ?? 0,
        'name'           => $_SESSION['name']           ?? '',
        'email'          => $_SESSION['email']          ?? '',
        'role'           => $_SESSION['role']           ?? '',
        'student_number' => $_SESSION['student_number'] ?? '',
        'initials'       => mb_strtoupper(mb_substr($_SESSION['name'] ?? 'U', 0, 1)),
    ];
}

/* ── Login / Logout ──────────────────────────────────────────── */

function attemptLogin(string $email, string $password): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['ok' => false, 'msg' => 'Invalid email or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['uid']            = $user['id'];
    $_SESSION['name']           = $user['full_name'];
    $_SESSION['email']          = $user['email'];
    $_SESSION['role']           = $user['role'];
    $_SESSION['student_number'] = $user['student_number'] ?? '';
    $_SESSION['last_active']    = time();

    return ['ok' => true, 'role' => $user['role']];
}

function sessionDestroy(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

/* ── Flash messages ──────────────────────────────────────────── */

function flash(string $msg, string $type = 'info'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function popFlash(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/* ── Utility ─────────────────────────────────────────────────── */

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}