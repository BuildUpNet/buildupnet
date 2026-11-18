<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('content');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $pid = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $st = $conn->prepare("DELETE FROM blog_posts WHERE id = ?"); $st->bind_param('i', $pid); $st->execute();
        flashMessage('success', 'Blog post deleted.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $st = $conn->prepare("UPDATE blog_posts SET status = IF(status='published','draft','published'), updated_at = NOW() WHERE id = ?"); $st->bind_param('i', $pid); $st->execute();
    }
    header('Location: posts.php'); exit;
}

$posts = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Blog Posts';
include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2>All Blog Posts (<?php echo count($posts); ?>)</h2>
        <a href="post-edit.php" class="abtn abtn-primary">+ New Post</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($posts)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <h3>No blog posts yet</h3>
                        <p><a href="post-edit.php" style="color:var(--a-blue);">Create your first post →</a></p>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td class="td-title"><?php echo clean($p['title']); ?></td>
                    <td class="td-muted"><?php echo clean($p['category']); ?></td>
                    <td class="td-muted"><?php echo clean($p['author']); ?></td>
                    <td>
                        <span class="abadge <?php echo $p['status'] === 'published' ? 'abadge-green' : 'abadge-grey'; ?>">
                            <?php echo clean($p['status']); ?>
                        </span>
                    </td>
                    <td class="td-muted"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="post-edit.php?id=<?php echo $p['id']; ?>" class="abtn abtn-outline abtn-sm">Edit</a>
                            <?php echo postButton('posts.php', ['action'=>'toggle','id'=>$p['id']], $p['status'] === 'published' ? 'Unpublish' : 'Publish'); ?>
                            <?php echo postButton('posts.php', ['action'=>'delete','id'=>$p['id']], 'Delete', 'abtn abtn-danger abtn-sm', 'Delete this post permanently?'); ?>
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
