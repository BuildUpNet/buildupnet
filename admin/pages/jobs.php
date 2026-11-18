<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('jobs');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $jid = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $st = $conn->prepare("DELETE FROM job_openings WHERE id = ?"); $st->bind_param('i', $jid); $st->execute();
        flashMessage('success', 'Job opening deleted.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $st = $conn->prepare("UPDATE job_openings SET status = IF(status='active','closed','active') WHERE id = ?"); $st->bind_param('i', $jid); $st->execute();
    }
    header('Location: jobs.php'); exit;
}

$jobs = $conn->query("SELECT * FROM job_openings ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Job Openings';
include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2>Job Openings (<?php echo count($jobs); ?>)</h2>
        <a href="job-edit.php" class="abtn abtn-primary">+ New Job</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th>Title</th><th>Type</th><th>Location</th><th>Status</th><th>Posted</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($jobs)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        <h3>No job openings yet</h3>
                        <p><a href="job-edit.php" style="color:var(--a-blue);">Create your first job →</a></p>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($jobs as $j): ?>
                <tr>
                    <td class="td-title"><?php echo clean($j['title']); ?></td>
                    <td><span class="abadge abadge-blue"><?php echo clean($j['type']); ?></span></td>
                    <td class="td-muted"><?php echo clean($j['location']); ?></td>
                    <td><span class="abadge <?php echo $j['status'] === 'active' ? 'abadge-green' : 'abadge-grey'; ?>"><?php echo clean($j['status']); ?></span></td>
                    <td class="td-muted"><?php echo date('d M Y', strtotime($j['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="job-edit.php?id=<?php echo $j['id']; ?>" class="abtn abtn-outline abtn-sm">Edit</a>
                            <?php echo postButton('jobs.php', ['action'=>'toggle','id'=>$j['id']], $j['status'] === 'active' ? 'Close' : 'Reopen'); ?>
                            <?php echo postButton('jobs.php', ['action'=>'delete','id'=>$j['id']], 'Delete', 'abtn abtn-danger abtn-sm', 'Delete this job opening?'); ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
