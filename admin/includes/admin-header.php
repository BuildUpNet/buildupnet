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
            <span class="sidebar-badge">Admin</span>
        </div>

        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Main</span>
            <a href="<?php echo adminUrl('dashboard.php'); ?>" class="<?php echo isActivePage('dashboard.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>

            <span class="sidebar-section-label">Content</span>
            <a href="<?php echo adminUrl('pages/posts.php'); ?>" class="<?php echo isActivePage('posts.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Blog Posts
            </a>
            <a href="<?php echo adminUrl('pages/recent-work.php'); ?>" class="<?php echo isActivePage('recent-work.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Recent Work
            </a>
            <a href="<?php echo adminUrl('pages/jobs.php'); ?>" class="<?php echo isActivePage('jobs.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                Job Openings
            </a>

            <span class="sidebar-section-label">Inbox</span>
            <a href="<?php echo adminUrl('pages/enquiries.php'); ?>" class="<?php echo isActivePage('enquiries.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Enquiries
                <?php
                // Show unread count badge
                if (isset($conn)) {
                    $unread = $conn->query("SELECT COUNT(*) as c FROM enquiries WHERE is_read = 0")->fetch_assoc()['c'] ?? 0;
                    if ($unread > 0) echo '<span class="sidebar-count">' . $unread . '</span>';
                }
                ?>
            </a>
            <a href="<?php echo adminUrl('pages/applications.php'); ?>" class="<?php echo isActivePage('applications.php'); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Applications
                <?php
                if (isset($conn)) {
                    $newapps = $conn->query("SELECT COUNT(*) as c FROM job_applications WHERE is_read = 0")->fetch_assoc()['c'] ?? 0;
                    if ($newapps > 0) echo '<span class="sidebar-count">' . $newapps . '</span>';
                }
                ?>
            </a>
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
                    <?php echo clean($_SESSION['admin_name'] ?? 'Admin'); ?>
                </span>
            </div>
        </header>

        <div class="admin-content">