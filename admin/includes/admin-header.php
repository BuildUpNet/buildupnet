<?php
// ==========================================================
// Buildupnet Admin — Header / Sidebar
// Place at: /admin/includes/admin-header.php
// ==========================================================
if (!isset($pageTitle)) $pageTitle = 'Admin — Buildupnet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo adminUrl('assets/css/admin.css') . '?v=' . (int)@filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <script src="<?php echo adminUrl('assets/js/sidebar-init.js') . '?v=' . (int)@filemtime(__DIR__ . '/../assets/js/sidebar-init.js'); ?>"></script>
</head>
<body>
<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
<symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></symbol>
<symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></symbol>
<symbol id="i-plus" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></symbol>
<symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></symbol>
<symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="3" y1="11" x2="21" y2="11"/></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
<symbol id="i-sliders" viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></symbol>
<symbol id="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></symbol>
<symbol id="i-userplus" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></symbol>
<symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></symbol>
<symbol id="i-edit" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></symbol>
<symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></symbol>
<symbol id="i-briefcase" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></symbol>
<symbol id="i-mail" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,6 12,13 2,6"/></symbol>
<symbol id="i-inbox" viewBox="0 0 24 24"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.5 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.5-6.9A2 2 0 0 0 16.7 4H7.3a2 2 0 0 0-1.8 1.1z"/></symbol>
<symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></symbol>
<symbol id="i-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/></symbol>
<symbol id="i-key" viewBox="0 0 24 24"><circle cx="8" cy="15" r="4"/><line x1="10.8" y1="12.2" x2="21" y2="2"/><line x1="17" y1="6" x2="20" y2="9"/></symbol>
<symbol id="i-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></symbol>
<symbol id="i-x" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol>
<symbol id="i-menu" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></symbol>
</defs></svg>

<div class="admin-wrap">

    <!-- ===== Sidebar ===== -->
    <aside class="sidebar" id="sidebar" aria-label="Main menu">
        <div class="sidebar-logo">
            <span class="sidebar-logo-mark">B</span>
            <span class="sb-text">Buildup<strong>net</strong></span>
            <button type="button" class="sb-close" id="sbClose" aria-label="Close menu"><svg class="ni" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><use href="#i-x"/></svg></button>
        </div>

        <nav class="sidebar-nav">
<?php
(function () {      // own scope, so the menu's helper variables never clash with a page's variables
$nav = function (string $href, string $icon, string $label, bool $active, int $count = 0): void {
    echo '<a href="' . adminUrl($href) . '" class="' . ($active ? 'active' : '') . '" title="' . htmlspecialchars($label) . '"' . ($active ? ' aria-current="page"' : '') . '>'
       . '<svg class="ni" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="#i-' . $icon . '"/></svg><span class="sb-text">' . $label . '</span>'
       . ($count > 0 ? '<span class="sidebar-count">' . $count . '</span>' : '') . '</a>';
};
$pg = currentAdminPage();
$nav('dashboard.php', 'grid', 'Dashboard', $pg === 'dashboard.php');

if (can('projects.view_all') || can('projects.view_own')) {
    echo '<span class="sidebar-section-label"><span class="sb-text">Projects</span></span>';
    $nav('pages/projects.php', 'folder', can('projects.view_all') ? 'All Projects' : 'My Projects', in_array($pg, ['projects.php', 'project-view.php'], true));
    if (can('projects.create')) $nav('pages/project-edit.php', 'plus', 'New Project', $pg === 'project-edit.php');
}
if (can('attendance.self')) {
    echo '<span class="sidebar-section-label"><span class="sb-text">Attendance</span></span>';
    $nav('pages/attendance.php', 'clock', 'My Attendance', $pg === 'attendance.php');
    $pl = 0; if (can('attendance.manage')) { $pl = (int)(dbRow("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'pending'")['c'] ?? 0); }
    $nav('pages/leaves.php', 'calendar', 'Leave', $pg === 'leaves.php', $pl);
    if (can('attendance.manage')) {
        $nav('pages/attendance-manage.php', 'users', 'Team Attendance', in_array($pg, ['attendance-manage.php', 'attendance-edit.php'], true));
        $nav('pages/schedules.php', 'sliders', 'Work Timings', $pg === 'schedules.php');
        $nav('pages/holidays.php', 'sun', 'Holidays', $pg === 'holidays.php');
    }
}
if (can('hiring.manage')) {
    echo '<span class="sidebar-section-label"><span class="sb-text">Hiring</span></span>';
    $nav('pages/hires.php', 'userplus', 'Hiring', in_array($pg, ['hires.php', 'hire-view.php'], true), hirePendingCount());
    $nav('pages/hire-edit.php', 'plus', 'Hire a Person', $pg === 'hire-edit.php');
    if (can('hiring.templates')) $nav('pages/letter-templates.php', 'file', 'Letter Templates', $pg === 'letter-templates.php');
}
if (can('content') || can('jobs')) {
    echo '<span class="sidebar-section-label"><span class="sb-text">Website</span></span>';
    if (can('content')) {
        $nav('pages/posts.php', 'edit', 'Blog Posts', $pg === 'posts.php');
        $nav('pages/recent-work.php', 'image', 'Recent Work', $pg === 'recent-work.php');
    }
    if (can('jobs')) $nav('pages/jobs.php', 'briefcase', 'Job Openings', $pg === 'jobs.php');
}
if (can('enquiries') || can('applications')) {
    echo '<span class="sidebar-section-label"><span class="sb-text">Inbox</span></span>';
    if (can('enquiries'))    $nav('pages/enquiries.php', 'mail', 'Enquiries', $pg === 'enquiries.php', (int)(dbRow("SELECT COUNT(*) AS c FROM enquiries WHERE is_read = 0")['c'] ?? 0));
    if (can('applications')) $nav('pages/applications.php', 'inbox', 'Applications', $pg === 'applications.php', (int)(dbRow("SELECT COUNT(*) AS c FROM job_applications WHERE is_read = 0")['c'] ?? 0));
}
if (can('users')) {
    echo '<span class="sidebar-section-label"><span class="sb-text">Team</span></span>';
    $nav('pages/users.php', 'shield', 'Users & Roles', in_array($pg, ['users.php', 'user-edit.php'], true));
}
})();
?>
        </nav>

        <div class="sidebar-footer">
            <div class="sb-user" title="<?php echo clean($_SESSION['admin_name'] ?? 'Admin'); ?>">
                <span class="sb-avatar"><?php echo clean(mb_strtoupper(mb_substr((string)($_SESSION['admin_name'] ?? 'A'), 0, 1))); ?></span>
                <span class="sb-text sb-user-text"><b><?php echo clean($_SESSION['admin_name'] ?? 'Admin'); ?></b><small><?php echo clean(ROLE_LABELS[currentRole()] ?? ''); ?></small></span>
            </div>
            <a href="<?php echo SITE_URL; ?>/" target="_blank" rel="noopener" class="sidebar-view-site" title="View Website"><svg class="ni" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><use href="#i-globe"/></svg><span class="sb-text">View Website</span></a>
            <a href="<?php echo adminUrl('pages/password.php'); ?>" class="sidebar-view-site <?php echo currentAdminPage() === 'password.php' ? 'on' : ''; ?>" title="Change Password"><svg class="ni" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><use href="#i-key"/></svg><span class="sb-text">Change Password</span></a>
            <a href="<?php echo adminUrl('logout.php'); ?>" class="sidebar-logout" title="Logout"><svg class="ni" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><use href="#i-logout"/></svg><span class="sb-text">Logout</span></a>
        </div>
    </aside>
    <div class="sb-overlay" id="sbOverlay"></div>

    <!-- ===== Main Content Area ===== -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Show or hide menu" aria-controls="sidebar" title="Menu"><svg class="ni" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><use href="#i-menu"/></svg></button>
            <h1 class="admin-page-title"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h1>
            <div class="admin-topbar-right">
                <span class="admin-user">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php echo clean($_SESSION['admin_name'] ?? 'Admin'); ?> · <?php echo clean(ROLE_LABELS[currentRole()] ?? ''); ?>
                </span>
            </div>
        </header>

        <div class="admin-content" data-live="page" data-title="<?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?>">