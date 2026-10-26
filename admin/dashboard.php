<?php
// ==========================================================
// Buildupnet Admin — Dashboard
// Place at: /admin/dashboard.php
// ==========================================================
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pageTitle = 'Dashboard';

$isDev     = !can('projects.view_all') && can('projects.view_own');
$showInbox = can('enquiries') || can('applications');
$showBlog  = can('content');

// Stats are only fetched for what this role may see
$publishedPosts = $showBlog ? (int)dbRow("SELECT COUNT(*) c FROM blog_posts WHERE status='published'")['c'] : 0;
$totalJobs      = can('jobs') ? (int)dbRow("SELECT COUNT(*) c FROM job_openings WHERE status='active'")['c'] : 0;
$unreadEnq      = can('enquiries') ? (int)dbRow("SELECT COUNT(*) c FROM enquiries WHERE is_read=0")['c'] : 0;
$newApps        = can('applications') ? (int)dbRow("SELECT COUNT(*) c FROM job_applications WHERE is_read=0")['c'] : 0;
$recentEnq      = can('enquiries') ? dbRows("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 5") : [];
$recentApps     = can('applications') ? dbRows("SELECT * FROM job_applications ORDER BY created_at DESC LIMIT 5") : [];

// Projects: everyone with a project permission sees a summary; developers only their own
$myProjects = [];
$activeProjects = 0;
if (can('projects.view_all') || can('projects.view_own')) {
    if ($isDev) {
        $myProjects = dbRows("SELECT p.id, p.project_code, p.name, p.project_type, p.status, p.deadline FROM client_projects p
                              JOIN project_developers pd ON pd.project_id = p.id AND pd.user_id = ?
                              ORDER BY FIELD(p.status,'in_progress','review','planning','lead','on_hold','delivered','cancelled'), p.deadline IS NULL, p.deadline LIMIT 8", 'i', [currentUserId()]);
    } else {
        $myProjects = dbRows("SELECT id, project_code, name, project_type, status, deadline FROM client_projects ORDER BY updated_at DESC LIMIT 8");
    }
    $activeProjects = $isDev ? count(array_filter($myProjects, fn($r) => !in_array($r['status'], ['delivered', 'cancelled'], true)))
                             : (int)dbRow("SELECT COUNT(*) c FROM client_projects WHERE status NOT IN ('delivered','cancelled')")['c'];
}

include __DIR__ . '/includes/admin-header.php';
?>

<?php renderFlash(); ?>

<?php
$ico = [
 'proj' => '<path d="M3 7h18v13H3z"/><path d="M8 7V4h8v3"/>',
 'doc'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
 'job'  => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>',
 'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
 'user' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
];
$cards = [];
if (can('projects.view_all') || can('projects.view_own')) $cards[] = ['blue',  'proj', $activeProjects, $isDev ? 'My Active Projects' : 'Active Projects'];
if ($showBlog)             $cards[] = ['green', 'doc',  $publishedPosts, 'Published Posts'];
if (can('jobs'))           $cards[] = ['green', 'job',  $totalJobs,      'Active Job Openings'];
if (can('enquiries'))      $cards[] = ['orange','mail', $unreadEnq,      'Unread Enquiries'];
if (can('applications'))   $cards[] = ['red',   'user', $newApps,        'New Applications'];
?>
<div class="stat-cards">
<?php foreach ($cards as [$colour, $icon, $num, $label]): ?>
    <div class="stat-card">
        <div class="stat-card-icon <?php echo $colour; ?>"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><?php echo $ico[$icon]; ?></svg></div>
        <div><div class="stat-card-num"><?php echo (int)$num; ?></div><div class="stat-card-label"><?php echo clean($label); ?></div></div>
    </div>
<?php endforeach; ?>
</div>

<?php if (can('projects.view_all') || can('projects.view_own')): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h2><?php echo $isDev ? 'My Projects' : 'Recent Projects'; ?></h2>
        <div class="btn-group">
            <?php if (can('projects.create')): ?><a href="pages/project-edit.php" class="abtn abtn-primary abtn-sm">+ New Project</a><?php endif; ?>
            <a href="pages/projects.php" class="abtn abtn-outline abtn-sm">View All</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr><th>Project</th><th>Type</th><th>Status</th><th>Deadline</th></tr></thead>
            <tbody>
            <?php if (!$myProjects): ?>
                <tr><td colspan="4" class="td-muted" style="text-align:center; padding:20px;"><?php echo $isDev ? 'No project is assigned to you yet.' : 'No projects yet'; ?></td></tr>
            <?php else: foreach ($myProjects as $pr): ?>
                <tr>
                    <td data-label="Project" class="td-title"><a href="pages/project-view.php?id=<?php echo (int)$pr['id']; ?>"><?php echo clean($pr['name']); ?></a><div class="td-muted"><?php echo clean($pr['project_code']); ?></div></td>
                    <td data-label="Type" class="td-muted"><?php echo clean($pr['project_type']); ?></td>
                    <td data-label="Status"><?php echo statusBadge($pr['status']); ?></td>
                    <td data-label="Deadline" class="td-muted"><?php echo $pr['deadline'] ? clean(fmtDate($pr['deadline'])) : '—'; ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($showInbox): ?>
<div class="dash-grid">
<?php if (can('enquiries')): ?>
    <div class="card">
        <div class="card-header"><h2>Recent Enquiries</h2><a href="pages/enquiries.php" class="abtn abtn-outline abtn-sm">View All</a></div>
        <div style="overflow-x:auto;">
            <table class="admin-table table-cards">
                <thead><tr><th>Name</th><th>Type</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($recentEnq)): ?>
                    <tr><td colspan="4" class="td-muted" style="text-align:center; padding:20px;">No enquiries yet</td></tr>
                <?php else: foreach ($recentEnq as $e): ?>
                    <tr>
                        <td data-label="Name" class="td-title"><?php echo clean($e['name']); ?></td>
                        <td data-label="Type"><span class="abadge <?php echo $e['form_type'] === 'quote' ? 'abadge-blue' : 'abadge-grey'; ?>"><?php echo clean($e['form_type']); ?></span></td>
                        <td data-label="Time" class="td-muted"><?php echo clean(timeAgo($e['created_at'])); ?></td>
                        <td data-label="Status"><span class="abadge <?php echo $e['is_read'] ? 'abadge-grey' : 'abadge-orange'; ?>"><?php echo $e['is_read'] ? 'Read' : 'New'; ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php if (can('applications')): ?>
    <div class="card">
        <div class="card-header"><h2>Recent Applications</h2><a href="pages/applications.php" class="abtn abtn-outline abtn-sm">View All</a></div>
        <div style="overflow-x:auto;">
            <table class="admin-table table-cards">
                <thead><tr><th>Name</th><th>Position</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($recentApps)): ?>
                    <tr><td colspan="4" class="td-muted" style="text-align:center; padding:20px;">No applications yet</td></tr>
                <?php else: foreach ($recentApps as $a): ?>
                    <tr>
                        <td data-label="Name" class="td-title"><?php echo clean($a['name']); ?></td>
                        <td data-label="Position" class="td-muted"><?php echo clean($a['position']); ?></td>
                        <td data-label="Time" class="td-muted"><?php echo clean(timeAgo($a['created_at'])); ?></td>
                        <td data-label="Status"><span class="abadge <?php echo $a['is_read'] ? 'abadge-grey' : 'abadge-green'; ?>"><?php echo $a['is_read'] ? 'Reviewed' : 'New'; ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>
<?php endif; ?>

<div class="card" style="margin-top:24px;">
    <div class="card-header"><h2>Quick Actions</h2></div>
    <div class="card-body" style="display:flex; gap:12px; flex-wrap:wrap;">
        <?php if (can('projects.create')): ?><a href="pages/project-edit.php" class="abtn abtn-primary">+ New Project</a><?php endif; ?>
        <?php if (can('content')): ?><a href="pages/post-edit.php" class="abtn abtn-blue">+ New Blog Post</a><a href="pages/recent-work-edit.php" class="abtn abtn-blue">+ Add Portfolio Item</a><?php endif; ?>
        <?php if (can('jobs')): ?><a href="pages/job-edit.php" class="abtn abtn-blue">+ New Job Opening</a><?php endif; ?>
        <?php if (can('enquiries')): ?><a href="pages/enquiries.php" class="abtn abtn-outline">Enquiries</a><?php endif; ?>
        <?php if (can('applications')): ?><a href="pages/applications.php" class="abtn abtn-outline">Applications</a><?php endif; ?>
        <?php if (can('users')): ?><a href="pages/users.php" class="abtn abtn-outline">Users &amp; Roles</a><?php endif; ?>
        <a href="pages/password.php" class="abtn abtn-outline">Change my password</a>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
