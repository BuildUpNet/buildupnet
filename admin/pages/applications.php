<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrfCheck();
    $did = (int)($_POST['id'] ?? 0);
    $row = $conn->prepare("SELECT resume_file FROM job_applications WHERE id = ?"); $row->bind_param('i', $did); $row->execute();
    $old = $row->get_result()->fetch_assoc();
    if (!empty($old['resume_file']) && preg_match('/^resume_[a-f0-9]+\.(pdf|doc|docx)$/', $old['resume_file'])) { @unlink(__DIR__ . '/../uploads/resumes/' . $old['resume_file']); }
    $st = $conn->prepare("DELETE FROM job_applications WHERE id = ?"); $st->bind_param('i', $did); $st->execute();
    flashMessage('success', 'Application deleted.');
    header('Location: applications.php'); exit;
}

$viewing = null;
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $st = $conn->prepare("UPDATE job_applications SET is_read = 1 WHERE id = ?"); $st->bind_param('i', $vid); $st->execute();
    $st = $conn->prepare("SELECT * FROM job_applications WHERE id = ?"); $st->bind_param('i', $vid); $st->execute();
    $viewing = $st->get_result()->fetch_assoc();
}

$apps = $conn->query("SELECT * FROM job_applications ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Job Applications';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>

<?php if ($viewing): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h2>Application — <?php echo clean($viewing['name']); ?></h2>
        <a href="applications.php" class="abtn abtn-outline abtn-sm">← Back</a>
    </div>
    <div class="card-body">
        <div class="form-grid" style="margin-bottom:20px;">
            <div><strong>Name:</strong><br><?php echo clean($viewing['name']); ?></div>
            <div><strong>Email:</strong><br><a href="mailto:<?php echo clean($viewing['email']); ?>"><?php echo clean($viewing['email']); ?></a></div>
            <div><strong>Phone:</strong><br><?php echo clean($viewing['phone']); ?></div>
            <div><strong>Position:</strong><br><?php echo clean($viewing['position']); ?></div>
            <div><strong>Experience:</strong><br><?php echo clean($viewing['experience'] ?? '—'); ?></div>
            <div><strong>Portfolio:</strong><br>
                <?php if (safeUrl($viewing['portfolio_url'])): ?>
                <a href="<?php echo clean(safeUrl($viewing['portfolio_url'])); ?>" target="_blank" rel="noopener noreferrer"><?php echo clean($viewing['portfolio_url']); ?></a>
                <?php elseif (!empty($viewing['portfolio_url'])): echo clean($viewing['portfolio_url']); ?>
                <?php else: ?>—<?php endif; ?>
            </div>
            <?php if (!empty($viewing['resume_file'])): ?>
            <div><strong>Resume:</strong><br>
                <a href="../download-resume.php?file=<?php echo urlencode($viewing['resume_file']); ?>" class="abtn abtn-outline abtn-sm">Download Resume</a>
            </div>
            <?php endif; ?>
            <div><strong>Applied:</strong><br><?php echo date('d M Y, h:i A', strtotime($viewing['created_at'])); ?></div>
        </div>
        <?php if (!empty($viewing['message'])): ?>
        <strong>Cover Message:</strong>
        <div class="msg-detail" style="margin-top:8px;"><?php echo nl2br(clean($viewing['message'])); ?></div>
        <?php endif; ?>
        <div style="margin-top:16px;">
            <a href="mailto:<?php echo clean($viewing['email']); ?>" class="abtn abtn-primary">Reply via Email</a>
            <?php echo postButton('applications.php', ['action'=>'delete','id'=>$viewing['id']], 'Delete', 'abtn abtn-danger', 'Delete this application?'); ?>
        </div>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-header">
        <h2>All Applications (<?php echo count($apps); ?>)</h2>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Position</th><th>Experience</th><th>Applied</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($apps)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <h3>No applications yet</h3>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($apps as $a): ?>
                <tr style="<?php echo !$a['is_read'] ? 'font-weight:600;' : ''; ?>">
                    <td class="td-title"><?php echo clean($a['name']); ?></td>
                    <td class="td-muted"><?php echo clean($a['email']); ?></td>
                    <td class="td-muted"><?php echo clean($a['position']); ?></td>
                    <td class="td-muted"><?php echo clean($a['experience'] ?? '—'); ?></td>
                    <td class="td-muted"><?php echo timeAgo($a['created_at']); ?></td>
                    <td><span class="abadge <?php echo $a['is_read'] ? 'abadge-grey' : 'abadge-green'; ?>"><?php echo $a['is_read'] ? 'Reviewed' : 'New'; ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="applications.php?view=<?php echo $a['id']; ?>" class="abtn abtn-outline abtn-sm">View</a>
                            <?php echo postButton('applications.php', ['action'=>'delete','id'=>$a['id']], 'Delete', 'abtn abtn-danger abtn-sm', 'Delete this application?'); ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
