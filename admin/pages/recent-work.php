<?php
// ==========================================================
// Buildupnet Admin — Recent Work / Portfolio
// Place at: /admin/pages/recent-work.php
// ==========================================================
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id     = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $st = $conn->prepare("SELECT image FROM portfolio_items WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
        $item = $st->get_result()->fetch_assoc();
        if ($item) deleteProjectImage($item['image']);
        $st = $conn->prepare("DELETE FROM portfolio_items WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
        flashMessage('success', 'Project deleted.');
    } elseif ($action === 'toggle') {
        $st = $conn->prepare("UPDATE portfolio_items SET status = IF(status='active','hidden','active') WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
    } elseif ($action === 'up' || $action === 'down') {
        $st = $conn->prepare("SELECT sort_order FROM portfolio_items WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if ($row) {
            $cur = (int)$row['sort_order'];
            $q = $action === 'up'
                ? "SELECT id, sort_order FROM portfolio_items WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1"
                : "SELECT id, sort_order FROM portfolio_items WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1";
            $st = $conn->prepare($q); $st->bind_param('i', $cur); $st->execute();
            $swap = $st->get_result()->fetch_assoc();
            if ($swap) {
                $sid = (int)$swap['id']; $sso = (int)$swap['sort_order'];
                $u = $conn->prepare("UPDATE portfolio_items SET sort_order = ? WHERE id = ?");
                $u->bind_param('ii', $sso, $id); $u->execute();
                $u->bind_param('ii', $cur, $sid); $u->execute();
            }
        }
    }
    header('Location: recent-work.php'); exit;
}

$items = $conn->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Recent Work';
include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2>Portfolio / Recent Work (<?php echo count($items); ?> projects)</h2>
        <a href="recent-work-edit.php" class="abtn abtn-primary">+ Add Project</a>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Thumbnail</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Live URL</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        <h3>No projects yet</h3>
                        <p><a href="recent-work-edit.php" style="color:var(--a-blue);">Add your first project →</a></p>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <?php echo postButton('recent-work.php', ['action'=>'up','id'=>$item['id']], '↑', 'abtn abtn-outline abtn-sm', '', 'Move Up'); ?>
                            <?php echo postButton('recent-work.php', ['action'=>'down','id'=>$item['id']], '↓', 'abtn abtn-outline abtn-sm', '', 'Move Down'); ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo '../../' . clean($item['image']); ?>"
                                 alt="<?php echo clean($item['title']); ?>"
                                 style="width:80px; height:54px; object-fit:cover; border-radius:6px; border:1px solid var(--a-border);"
                                 onerror="this.style.display='none'">
                        <?php else: ?>
                            <div style="width:80px; height:54px; background:var(--a-bg); border-radius:6px; border:1px solid var(--a-border);"></div>
                        <?php endif; ?>
                    </td>
                    <td class="td-title"><?php echo clean($item['title']); ?></td>
                    <td><span class="abadge abadge-blue"><?php echo clean($item['category']); ?></span></td>
                    <td class="td-muted">
                        <?php if (safeUrl($item['live_url'])): ?>
                            <a href="<?php echo clean(safeUrl($item['live_url'])); ?>" target="_blank" rel="noopener noreferrer"
                               style="color:var(--a-blue); font-size:0.82rem;">
                                <?php echo clean(parse_url($item['live_url'], PHP_URL_HOST)); ?> ↗
                            </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <span class="abadge <?php echo $item['status'] === 'active' ? 'abadge-green' : 'abadge-grey'; ?>">
                            <?php echo clean($item['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="recent-work-edit.php?id=<?php echo $item['id']; ?>" class="abtn abtn-outline abtn-sm">Edit</a>
                            <?php echo postButton('recent-work.php', ['action'=>'toggle','id'=>$item['id']], $item['status'] === 'active' ? 'Hide' : 'Show'); ?>
                            <?php echo postButton('recent-work.php', ['action'=>'delete','id'=>$item['id']], 'Delete', 'abtn abtn-danger abtn-sm', "Delete '" . $item['title'] . "'?"); ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body" style="color:var(--a-muted); font-size:0.85rem; line-height:1.8;">
        <strong style="color:var(--a-text);">Tips:</strong><br>
        • Use ↑ ↓ arrows to reorder projects — order here = order on portfolio page.<br>
        • "Hide" removes a project from the public portfolio page without deleting it.<br>
        • Thumbnail should be at least <strong>800×600px</strong> for best display.
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
