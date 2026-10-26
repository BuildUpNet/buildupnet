<?php
// ==========================================================
// Buildupnet Admin — Auth guard, secure session, CSRF
// Included by every admin page BEFORE any output.
// ==========================================================
require_once __DIR__ . '/../../includes/security.php';

if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'auth.php') { http_response_code(403); exit; }

const ADMIN_IDLE_TIMEOUT = 7200;   // 2 hours without activity -> logged out

function startSecureSession(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('BUNADMINSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
startSecureSession();

// Never cache admin pages; never index them.
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
}

function isLoggedIn(): bool {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) return false;
    if (time() - (int)($_SESSION['last_activity'] ?? 0) > ADMIN_IDLE_TIMEOUT) {
        destroyAdminSession();
        startSecureSession();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . getAdminBase() . '/index.php?error=session');
        exit;
    }
}

function loginAdmin(array $user): void {
    session_regenerate_id(true);                 // blocks session fixation
    $_SESSION = [];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id']        = (int)$user['id'];
    $_SESSION['admin_name']      = (string)$user['name'];
    $_SESSION['last_activity']   = time();
}

function destroyAdminSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

// ---------- CSRF ----------
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}
/** Call at the top of every POST handler. Stops the request if the token is missing/wrong. */
function csrfCheck(): void {
    $sent = (string)($_POST['csrf'] ?? '');
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Security check failed (invalid or expired form). Go back, refresh the page and try again.');
    }
}

function getAdminBase(): string {
    $script   = $_SERVER['SCRIPT_NAME'];
    $adminPos = strpos($script, '/admin/');
    return $adminPos !== false ? substr($script, 0, $adminPos) . '/admin' : '/admin';
}
function adminUrl(string $page = ''): string {
    return getAdminBase() . ($page ? '/' . ltrim($page, '/') : '');
}
function currentAdminPage(): string { return basename($_SERVER['SCRIPT_NAME']); }
function isActivePage(string $page): string { return currentAdminPage() === $page ? 'active' : ''; }
