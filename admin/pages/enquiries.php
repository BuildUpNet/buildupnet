<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrfCheck();
    $did = (int)($_POST['id'] ?? 0);
    $st = $conn->prepare("DELETE FROM enquiries WHERE id = ?"); $st->bind_param('i', $did); $st->execute();
    flashMessage('success', 'Enquiry deleted.');
    header('Location: enquiries.php'); exit;
}

// View single enquiry — mark as read
$viewing = null;
if (isset($_GET['view'])) {
    $vid = (int)$_GET['view'];
    $st = $conn->prepare("UPDATE enquiries SET is_read = 1 WHERE id = ?"); $st->bind_param('i', $vid); $st->execute();
    $st = $conn->prepare("SELECT * FROM enquiries WHERE id = ?"); $st->bind_param('i', $vid); $st->execute();
    $viewing = $st->get_result()->fetch_assoc();
}

$enquiries = $conn->query("SELECT * FROM enquiries ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Enquiries';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>

<?php if ($viewing): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h2>Message from <?php echo clean($viewing['name']); ?></h2>
        <a href="enquiries.php" class="abtn abtn-outline abtn-sm">← Back to List</a>
    </div>
    <div class="card-body">
        <div class="form-grid" style="margin-bottom:20px;">
            <div><strong>Name:</strong><br><?php echo clean($viewing['name']); ?></div>
            <div><strong>Email:</strong><br><a href="mailto:<?php echo clean($viewing['email']); ?>"><?php echo clean($viewing['email']); ?></a></div>
            <div><strong>Phone:</strong><br><?php echo clean($viewing['phone'] ?? '—'); ?></div>
            <div><strong>Type:</strong><br><span class="abadge abadge-blue"><?php echo clean($viewing['form_type']); ?></span></div>
            <?php if ($viewing['form_type'] === 'quote'): ?>
            <div><strong>Service:</strong><br><?php echo clean($viewing['service'] ?? '—'); ?></div>
            <div><strong>Budget:</strong><br><?php echo clean($viewing['budget'] ?? '—'); ?></div>
            <div><strong>Timeline:</strong><br><?php echo clean($viewing['timeline'] ?? '—'); ?></div>
            <?php endif; ?>
            <div><strong>Received:</strong><br><?php echo date('d M Y, h:i A', strtotime($viewing['created_at'])); ?></div>
        </div>
        <strong>Message:</strong>
        <div class="msg-detail" style="margin-top:8px;"><?php echo nl2br(clean($viewing['message'] ?? $viewing['details'] ?? '')); ?></div>
        <div style="margin-top:16px;">
            <a href="mailto:<?php echo clean($viewing['email']); ?>" class="abtn abtn-primary">Reply via Email</a>
            <?php echo postButton('enquiries.php', ['action'=>'delete','id'=>$viewing['id']], 'Delete', 'abtn abtn-danger', 'Delete this enquiry?'); ?>
        </div>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-header">
        <h2>All Enquiries (<?php echo count($enquiries); ?>)</h2>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Type</th><th>Service</th><th>Received</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($enquiries)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <h3>No enquiries yet</h3>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($enquiries as $e): ?>
                <tr style="<?php echo !$e['is_read'] ? 'font-weight:600;' : ''; ?>">
                    <td class="td-title"><?php echo clean($e['name']); ?></td>
                    <td class="td-muted"><?php echo clean($e['email']); ?></td>
                    <td><span class="abadge <?php echo $e['form_type'] === 'quote' ? 'abadge-blue' : 'abadge-grey'; ?>"><?php echo clean($e['form_type']); ?></span></td>
                    <td class="td-muted"><?php echo clean($e['service'] ?? '—'); ?></td>
                    <td class="td-muted"><?php echo timeAgo($e['created_at']); ?></td>
                    <td><span class="abadge <?php echo $e['is_read'] ? 'abadge-grey' : 'abadge-orange'; ?>"><?php echo $e['is_read'] ? 'Read' : 'New'; ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="enquiries.php?view=<?php echo $e['id']; ?>" class="abtn abtn-outline abtn-sm">View</a>
                            <?php echo postButton('enquiries.php', ['action'=>'delete','id'=>$e['id']], 'Delete', 'abtn abtn-danger abtn-sm', 'Delete this enquiry?'); ?>
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
