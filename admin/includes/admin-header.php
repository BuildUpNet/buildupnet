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
    <link rel="stylesheet" href="<?php echo adminUrl('assets/css/admin.css'); ?>">
</head>
<body>

<div class="admin-wrap">

    <!-- ===== Sidebar ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <span class="sidebar-logo-mark">B</span>
            <span>Buildup<strong>net</strong></span>
            <span class="sidebar-badge"><?php echo clean(ROLE_LABELS[currentRole()] ?? 'Admin'); ?></span>
        </div>

        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Main</span>
            <a href="<?php echo adminUrl('dashboard.php'); ?>" class="<?php echo isActivePage('dashboard.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
            </a>

            <?php if (can('projects.view_all') || can('projects.view_own')): ?>
            <span class="sidebar-section-label">Projects</span>
            <a href="<?php echo adminUrl('pages/projects.php'); ?>" class="<?php echo in_array(currentAdminPage(), ['projects.php', 'project-view.php']) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> <?php echo can('projects.view_all') ? 'All Projects' : 'My Projects'; ?>
            </a>
            <?php if (can('projects.create')): ?>
            <a href="<?php echo adminUrl('pages/project-edit.php'); ?>" class="<?php echo isActivePage('project-edit.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> New Project
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (can('attendance.self')): ?>
            <span class="sidebar-section-label">Attendance</span>
            <a href="<?php echo adminUrl('pages/attendance.php'); ?>" class="<?php echo isActivePage('attendance.php'); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg> My Attendance</a>
            <a href="<?php echo adminUrl('pages/leaves.php'); ?>" class="<?php echo isActivePage('leaves.php'); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg> Leave <?php if (can('attendance.manage')) { $pl = (int)(dbRow("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'pending'")['c'] ?? 0); if ($pl > 0) echo '<span class="sidebar-count">' . $pl . '</span>'; } ?></a>
            <?php if (can('attendance.manage')): ?>
            <a href="<?php echo adminUrl('pages/attendance-manage.php'); ?>" class="<?php echo in_array(currentAdminPage(), ['attendance-manage.php', 'attendance-edit.php']) ? 'active' : ''; ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg> Team Attendance</a>
            <a href="<?php echo adminUrl('pages/schedules.php'); ?>" class="<?php echo isActivePage('schedules.php'); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg> Work Timings</a>
            <a href="<?php echo adminUrl('pages/holidays.php'); ?>" class="<?php echo isActivePage('holidays.php'); ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg> Holidays</a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (can('content') || can('jobs')): ?>
            <span class="sidebar-section-label">Website</span>
            <?php if (can('content')): ?>
            <a href="<?php echo adminUrl('pages/posts.php'); ?>" class="<?php echo isActivePage('posts.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Blog Posts
            </a>
            <a href="<?php echo adminUrl('pages/recent-work.php'); ?>" class="<?php echo isActivePage('recent-work.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Recent Work
            </a>
            <?php endif; ?>
            <?php if (can('jobs')): ?>
            <a href="<?php echo adminUrl('pages/jobs.php'); ?>" class="<?php echo isActivePage('jobs.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg> Job Openings
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (can('enquiries') || can('applications')): ?>
            <span class="sidebar-section-label">Inbox</span>
            <?php if (can('enquiries')): ?>
            <a href="<?php echo adminUrl('pages/enquiries.php'); ?>" class="<?php echo isActivePage('enquiries.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Enquiries
                <?php $unread = (int)(dbRow("SELECT COUNT(*) AS c FROM enquiries WHERE is_read = 0")['c'] ?? 0);
                if ($unread > 0) echo '<span class="sidebar-count">' . $unread . '</span>'; ?>
            </a>
            <?php endif; ?>
            <?php if (can('applications')): ?>
            <a href="<?php echo adminUrl('pages/applications.php'); ?>" class="<?php echo isActivePage('applications.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Applications
                <?php $newapps = (int)(dbRow("SELECT COUNT(*) AS c FROM job_applications WHERE is_read = 0")['c'] ?? 0);
                if ($newapps > 0) echo '<span class="sidebar-count">' . $newapps . '</span>'; ?>
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (can('users')): ?>
            <span class="sidebar-section-label">Team</span>
            <a href="<?php echo adminUrl('pages/users.php'); ?>" class="<?php echo in_array(currentAdminPage(), ['users.php', 'user-edit.php']) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Users &amp; Roles
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?php echo SITE_URL; ?>/" target="_blank" rel="noopener" class="sidebar-view-site">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Website
            </a>
            <a href="<?php echo adminUrl('pages/password.php'); ?>" class="<?php echo isActivePage('password.php'); ?>" style="display:block;padding:8px 20px;font-size:0.85rem;">Change Password</a>
            <a href="<?php echo adminUrl('logout.php'); ?>" class="sidebar-logout">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- ===== Main Content Area ===== -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h1 class="admin-page-title"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h1>
            <div class="admin-topbar-right">
                <span class="admin-user">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php echo clean($_SESSION['admin_name'] ?? 'Admin'); ?> · <?php echo clean(ROLE_LABELS[currentRole()] ?? ''); ?>
                </span>
            </div>
        </header>

        <div class="admin-content" data-live="page" data-title="<?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?>">