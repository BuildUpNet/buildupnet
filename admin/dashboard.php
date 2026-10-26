<?php
// ==========================================================
// Buildupnet Admin — Dashboard
// Place at: /admin/dashboard.php
// ==========================================================
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Dashboard';

// Stats
$totalPosts    = $conn->query("SELECT COUNT(*) as c FROM blog_posts")->fetch_assoc()['c'];
$publishedPosts= $conn->query("SELECT COUNT(*) as c FROM blog_posts WHERE status='published'")->fetch_assoc()['c'];
$totalJobs     = $conn->query("SELECT COUNT(*) as c FROM job_openings WHERE status='active'")->fetch_assoc()['c'];
$totalEnquiries= $conn->query("SELECT COUNT(*) as c FROM enquiries")->fetch_assoc()['c'];
$unreadEnq     = $conn->query("SELECT COUNT(*) as c FROM enquiries WHERE is_read=0")->fetch_assoc()['c'];
$totalApps     = $conn->query("SELECT COUNT(*) as c FROM job_applications")->fetch_assoc()['c'];
$newApps       = $conn->query("SELECT COUNT(*) as c FROM job_applications WHERE is_read=0")->fetch_assoc()['c'];

// Recent enquiries
$recentEnq = $conn->query("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Recent applications
$recentApps = $conn->query("SELECT * FROM job_applications ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/includes/admin-header.php';
?>

<?php renderFlash(); ?>

<!-- Stat Cards -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card-icon blue">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
            <div class="stat-card-num"><?php echo $publishedPosts; ?></div>
            <div class="stat-card-label">Published Posts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </div>
        <div>
            <div class="stat-card-num"><?php echo $totalJobs; ?></div>
            <div class="stat-card-label">Active Job Openings</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
            <div class="stat-card-num"><?php echo $unreadEnq; ?></div>
            <div class="stat-card-label">Unread Enquiries</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon red">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div>
            <div class="stat-card-num"><?php echo $newApps; ?></div>
            <div class="stat-card-label">New Applications</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    <!-- Recent Enquiries -->
    <div class="card">
        <div class="card-header">
            <h2>Recent Enquiries</h2>
            <a href="pages/enquiries.php" class="abtn abtn-outline abtn-sm">View All</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Type</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($recentEnq)): ?>
                    <tr><td colspan="4" class="td-muted" style="text-align:center; padding:20px;">No enquiries yet</td></tr>
                <?php else: ?>
                    <?php foreach ($recentEnq as $e): ?>
                    <tr>
                        <td class="td-title"><?php echo clean($e['name']); ?></td>
                        <td><span class="abadge <?php echo $e['form_type'] === 'quote' ? 'abadge-blue' : 'abadge-grey'; ?>"><?php echo clean($e['form_type']); ?></span></td>
                        <td class="td-muted"><?php echo timeAgo($e['created_at']); ?></td>
                        <td><span class="abadge <?php echo $e['is_read'] ? 'abadge-grey' : 'abadge-orange'; ?>"><?php echo $e['is_read'] ? 'Read' : 'New'; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="card">
        <div class="card-header">
            <h2>Recent Applications</h2>
            <a href="pages/applications.php" class="abtn abtn-outline abtn-sm">View All</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Position</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($recentApps)): ?>
                    <tr><td colspan="4" class="td-muted" style="text-align:center; padding:20px;">No applications yet</td></tr>
                <?php else: ?>
                    <?php foreach ($recentApps as $a): ?>
                    <tr>
                        <td class="td-title"><?php echo clean($a['name']); ?></td>
                        <td class="td-muted"><?php echo clean($a['position']); ?></td>
                        <td class="td-muted"><?php echo timeAgo($a['created_at']); ?></td>
                        <td><span class="abadge <?php echo $a['is_read'] ? 'abadge-grey' : 'abadge-green'; ?>"><?php echo $a['is_read'] ? 'Reviewed' : 'New'; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Quick Links -->
<div class="card" style="margin-top:24px;">
    <div class="card-header"><h2>Quick Actions</h2></div>
    <div class="card-body" style="display:flex; gap:12px; flex-wrap:wrap;">
        <a href="pages/post-edit.php" class="abtn abtn-primary">+ New Blog Post</a>
        <a href="pages/recent-work-edit.php" class="abtn abtn-blue">+ Add Project</a>
        <a href="pages/job-edit.php" class="abtn abtn-blue">+ New Job Opening</a>
        <a href="pages/enquiries.php" class="abtn abtn-outline">View Enquiries</a>
        <a href="pages/applications.php" class="abtn abtn-outline">View Applications</a>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>