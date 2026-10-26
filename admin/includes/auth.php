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

/**
 * Every protected page calls this first. Besides checking the session it re-reads the account from the
 * database, so a deactivated user or a changed role takes effect on the very next click.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . getAdminBase() . '/index.php?error=session');
        exit;
    }
    $u = dbRow("SELECT name, role, is_active, must_change_password FROM admin_users WHERE id = ?", 'i', [(int)$_SESSION['admin_id']]);
    if (!$u || !(int)$u['is_active'] || !isset(ROLE_PERMISSIONS[$u['role']])) {
        destroyAdminSession();
        header('Location: ' . getAdminBase() . '/index.php?error=session');
        exit;
    }
    $_SESSION['admin_role'] = $u['role'];
    $_SESSION['admin_name'] = $u['name'];

    // A temporary password (new account / reset by the owner) must be replaced before anything else.
    if ((int)$u['must_change_password'] === 1 && currentAdminPage() !== 'password.php') {
        header('Location: ' . adminUrl('pages/password.php'));
        exit;
    }
}

// ---------- Roles & permissions (edit this table to change who can do what) ----------
//   owner     : everything
//   manager   : creates/edits client projects (sees prices), handles enquiries, job applications and job openings
//   developer : sees only the projects assigned to them, can post progress updates (no prices)
const ROLE_PERMISSIONS = [
    'owner'     => ['*'],
    'manager'   => ['projects.view_all', 'projects.create', 'projects.edit', 'projects.price', 'projects.update', 'enquiries', 'applications', 'jobs'],
    'developer' => ['projects.view_own', 'projects.update'],
];
const ROLE_LABELS = ['owner' => 'Owner', 'manager' => 'Manager', 'developer' => 'Developer'];

function currentRole(): string { return (string)($_SESSION['admin_role'] ?? ''); }
function currentUserId(): int  { return (int)($_SESSION['admin_id'] ?? 0); }

function can(string $permission): bool {
    $perms = ROLE_PERMISSIONS[currentRole()] ?? [];
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

/** Stops the request with a 403 page when the logged-in role may not do this. */
function requirePermission(string $permission): void {
    if (can($permission)) return;
    http_response_code(403);
    $pageTitle = 'Not allowed';
    include __DIR__ . '/admin-header.php';
    echo '<div class="card"><div class="card-body"><div class="empty-state"><h3>You do not have access to this page</h3>'
       . '<p>Your role (' . htmlspecialchars(ROLE_LABELS[currentRole()] ?? '') . ') cannot open it. Ask the owner if you need access.</p>'
       . '<p><a href="' . htmlspecialchars(adminUrl('dashboard.php')) . '" style="color:var(--a-blue);">Back to dashboard</a></p></div></div></div>';
    include __DIR__ . '/admin-footer.php';
    exit;
}

function loginAdmin(array $user): void {
    session_regenerate_id(true);                 // blocks session fixation
    $_SESSION = [];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id']        = (int)$user['id'];
    $_SESSION['admin_name']      = (string)$user['name'];
    $_SESSION['admin_role']      = (string)$user['role'];
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
