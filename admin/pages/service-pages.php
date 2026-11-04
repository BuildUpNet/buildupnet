<?php
// ==========================================================
// Buildupnet Admin — Service pages (the pages at /services/<name>)
// Place at: /admin/pages/service-pages.php
// ==========================================================
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('content');

$tableMissing = false;
try { $conn->query("SELECT 1 FROM service_pages LIMIT 1"); } catch (mysqli_sql_exception $e) { $tableMissing = true; }

if (!$tableMissing && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id     = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $st = $conn->prepare("DELETE FROM service_pages WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
        flashMessage('success', 'Service page deleted.');
    } elseif ($action === 'toggle') {
        $st = $conn->prepare("UPDATE service_pages SET status = IF(status='published','hidden','published') WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
    } elseif ($action === 'up' || $action === 'down') {
        $st = $conn->prepare("SELECT sort_order FROM service_pages WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if ($row) {
            $cur = (int)$row['sort_order'];
            $q = $action === 'up'
                ? "SELECT id, sort_order FROM service_pages WHERE sort_order < ? OR (sort_order = ? AND id < ?) ORDER BY sort_order DESC, id DESC LIMIT 1"
                : "SELECT id, sort_order FROM service_pages WHERE sort_order > ? OR (sort_order = ? AND id > ?) ORDER BY sort_order ASC, id ASC LIMIT 1";
            $st = $conn->prepare($q); $st->bind_param('iii', $cur, $cur, $id); $st->execute();
            $swap = $st->get_result()->fetch_assoc();
            if ($swap) {
                $sid = (int)$swap['id']; $sso = (int)$swap['sort_order'];
                if ($sso === $cur) { $sso = $action === 'up' ? $cur - 1 : $cur + 1; }   // equal order numbers: just step past the neighbour
                $u = $conn->prepare("UPDATE service_pages SET sort_order = ? WHERE id = ?");
                if ((int)$swap['sort_order'] === $cur) { $u->bind_param('ii', $sso, $id); $u->execute(); }
                else { $u->bind_param('ii', $sso, $id); $u->execute(); $u->bind_param('ii', $cur, $sid); $u->execute(); }
            }
        }
    }
    header('Location: service-pages.php'); exit;
}

$items = $tableMissing ? [] : $conn->query("SELECT id, slug, nav_label, title, status, blog_category FROM service_pages ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Service Pages';
include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<?php if ($tableMissing): ?>
<div class="card"><div class="card-body">
    <div class="empty-state">
        <h3>Service pages are not set up yet</h3>
        <p>Open phpMyAdmin, select your database, go to the SQL tab, paste the contents of <code>upgrade-service-pages.sql</code> and press Go. Then reload this page.</p>
    </div>
</div></div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <h2>Service Pages (<?php echo count($items); ?>)</h2>
        <a href="service-page-edit.php" class="abtn abtn-primary">+ Add Service Page</a>
    </div>
    <p class="hint" style="padding:0 20px;">Each page opens at <code>/services/&lt;address&gt;</code>. The order here is the order in the footer and blog boxes. Hidden pages are removed from the site and from the footer.</p>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead><tr><th>Order</th><th>Name</th><th>Address</th><th>Blog category</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="6"><div class="empty-state"><h3>No service pages yet</h3><p><a href="service-page-edit.php" style="color:var(--a-blue);">Add the first one →</a></p></div></td></tr>
            <?php else: foreach ($items as $it): ?>
                <tr>
                    <td>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <?php echo postButton('service-pages.php', ['action'=>'up','id'=>$it['id']], '↑', 'abtn abtn-outline abtn-sm', '', 'Move Up'); ?>
                            <?php echo postButton('service-pages.php', ['action'=>'down','id'=>$it['id']], '↓', 'abtn abtn-outline abtn-sm', '', 'Move Down'); ?>
                        </div>
                    </td>
                    <td class="td-title"><?php echo clean($it['nav_label']); ?><div class="td-muted" style="font-size:0.8rem;"><?php echo clean($it['title']); ?></div></td>
                    <td class="td-muted"><code>/services/<?php echo clean($it['slug']); ?></code></td>
                    <td><?php echo $it['blog_category'] !== null && $it['blog_category'] !== '' ? '<span class="abadge abadge-blue">' . clean($it['blog_category']) . '</span>' : '—'; ?></td>
                    <td><span class="abadge <?php echo $it['status'] === 'published' ? 'abadge-green' : 'abadge-grey'; ?>"><?php echo clean($it['status']); ?></span></td>
                    <td>
                        <div class="btn-group">
                            <a href="service-page-edit.php?id=<?php echo (int)$it['id']; ?>" class="abtn abtn-outline abtn-sm">Edit</a>
                            <?php if ($it['status'] === 'published'): ?><a href="<?php echo clean(SITE_URL . '/services/' . rawurlencode($it['slug'])); ?>" target="_blank" rel="noopener" class="abtn abtn-outline abtn-sm">View ↗</a><?php endif; ?>
                            <?php echo postButton('service-pages.php', ['action'=>'toggle','id'=>$it['id']], $it['status'] === 'published' ? 'Hide' : 'Publish'); ?>
                            <?php echo postButton('service-pages.php', ['action'=>'delete','id'=>$it['id']], 'Delete', 'abtn abtn-danger abtn-sm', "Delete the page '" . $it['nav_label'] . "'? Its address will stop working."); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
