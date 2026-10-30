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
<?php
// ---- Today's attendance (everyone) ----
$attToday = date('Y-m-d');
$attCur   = attToday(currentUserId());
$attRec   = $attCur['rec']; $attState = $attCur['state']; $attOpen = $attCur['open'];
$attSched = attSchedule(currentUserId());
$nowSecs  = (int)date('G') * 3600 + (int)date('i') * 60 + (int)date('s');
$teamNow  = null;
if (can('attendance.manage')) {
    $teamNow = ['P' => 0, 'A' => 0, 'L' => 0, 'late' => 0, 'out' => 0];
    $ymNow = substr($attToday, 0, 7); $holNow = attHolidays($ymNow . '-01', date('Y-m-t', strtotime($ymNow . '-01')));
    foreach (attStaff(true) as $su) {
        $dd = attMonth($su, $ymNow, attSchedule((int)$su['id']), $holNow)[$attToday];
        if (in_array($dd['code'], ['P', 'H'], true)) $teamNow['P']++; elseif ($dd['code'] === 'A') $teamNow['A']++; elseif ($dd['code'] === 'L') $teamNow['L']++;
        if ($dd['late'] > 0) $teamNow['late']++;
        if (($dd['br']['open'] ?? false) && $dd['rec'] && !$dd['rec']['check_out']) $teamNow['out']++;
    }
}
$punch = fn($act, $label, $cls) => postButton('pages/attendance.php', ['action' => $act], $label, $cls, '', '', true);
?>
<div data-region="punch">
<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>My attendance today</h2><a href="pages/attendance.php" class="abtn abtn-outline abtn-sm">Details</a></div>
    <div class="card-body">
        <p class="td-muted" style="margin:0 0 12px;">Your timing <?php echo attHm($attSched['work_start']); ?>–<?php echo attHm($attSched['work_end']); ?> · Lunch <?php echo attHm($attSched['lunch_start']); ?>–<?php echo attHm($attSched['lunch_end']); ?>
            <?php if ($attRec && $attRec['check_in']): ?> · In <b><?php echo attHm($attRec['check_in']); ?></b><?php echo $attRec['check_out'] ? ' · Out <b>' . attHm($attRec['check_out']) . '</b>' : ''; ?><?php endif; ?></p>
        <?php if ($attState === 'break' && $attOpen): ?>
            <div class="out-banner <?php echo clean($attOpen['kind']); ?>"><div><b>You are marked OUT</b> since <?php echo attHm($attOpen['out_at']); ?> — <span class="timer" data-elapsed="<?php echo max(0, $nowSecs - attMin($attOpen['out_at']) * 60); ?>">…</span><div class="td-muted"><?php echo clean((string)$attOpen['reason']); ?></div></div></div>
        <?php endif; ?>
        <div class="punch-row">
            <?php if ($attState === 'in')      echo $punch('check_in', 'Check in', 'abtn abtn-primary punch'); ?>
            <?php if ($attState === 'working') echo $punch('lunch_out', 'Start lunch', 'abtn abtn-blue punch') . $punch('check_out', 'Check out', 'abtn abtn-primary punch') . '<a class="abtn abtn-outline punch" href="pages/attendance.php#out">Going out for urgent work…</a>'; ?>
            <?php if ($attState === 'lunch')   echo $punch('lunch_in', 'Back from lunch', 'abtn abtn-blue punch'); ?>
            <?php if ($attState === 'break')   echo $punch('break_in', "I'm back", 'abtn abtn-primary punch'); ?>
            <?php if ($attState === 'done'):   ?><span class="abadge abadge-green">Day complete</span><?php endif; ?>
        </div>
        <?php if ($teamNow): ?><p class="td-muted" style="margin:16px 0 0;">Team today: <b><?php echo $teamNow['P']; ?></b> present · <b><?php echo $teamNow['A']; ?></b> absent · <b><?php echo $teamNow['L']; ?></b> on leave · <b><?php echo $teamNow['late']; ?></b> late<?php echo $teamNow['out'] ? ' · <b>' . $teamNow['out'] . '</b> out right now' : ''; ?> — <a href="pages/attendance-manage.php" style="color:var(--a-blue);">Team attendance</a></p><?php endif; ?>
    </div>
</div>
</div>

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
