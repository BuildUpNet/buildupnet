<?php
// Client projects — list. Owner/manager see all; a developer sees only projects assigned to them.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
if (!can('projects.view_all')) requirePermission('projects.view_own');

// Delete (owner only, POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrfCheck();
    requirePermission('projects.delete');
    $st = $conn->prepare("DELETE FROM client_projects WHERE id = ?");
    $did = (int)($_POST['id'] ?? 0); $st->bind_param('i', $did); $st->execute();
    flashMessage('success', 'Project deleted.');
    header('Location: projects.php'); exit;
}

// ---- filters ----
$fStatus = isset(PROJECT_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$fType   = in_array($_GET['type'] ?? '', PROJECT_TYPES, true) ? $_GET['type'] : '';
$fQuery  = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);

$where = []; $types = ''; $params = [];
if (!can('projects.view_all')) { $where[] = "EXISTS (SELECT 1 FROM project_developers x WHERE x.project_id = p.id AND x.user_id = ?)"; $types .= 'i'; $params[] = currentUserId(); }
if ($fStatus !== '') { $where[] = "p.status = ?"; $types .= 's'; $params[] = $fStatus; }
if ($fType !== '')   { $where[] = "p.project_type = ?"; $types .= 's'; $params[] = $fType; }
if ($fQuery !== '')  { $where[] = "(p.name LIKE ? OR p.client_name LIKE ? OR p.client_company LIKE ? OR p.project_code LIKE ? OR p.tech_stack LIKE ?)"; $types .= 'sssss'; $like = '%' . $fQuery . '%'; array_push($params, $like, $like, $like, $like, $like); }

$sql = "SELECT p.*, u.name AS brought_by,
               (SELECT GROUP_CONCAT(du.name ORDER BY du.name SEPARATOR ', ') FROM project_developers pd JOIN admin_users du ON du.id = pd.user_id WHERE pd.project_id = p.id) AS developers
        FROM client_projects p LEFT JOIN admin_users u ON u.id = p.brought_by_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY (p.status IN ('delivered','cancelled')), p.deadline IS NULL, p.deadline, p.id DESC LIMIT 300";
$projects = dbRows($sql, $types, $params);

// ---- summary numbers (for the rows the user may see) ----
$sum = ['active' => 0, 'leads' => 0, 'delivered' => 0, 'value' => 0.0, 'due' => 0.0];
foreach ($projects as $p) {
    if (in_array($p['status'], ['in_progress', 'review'], true)) $sum['active']++;
    if (in_array($p['status'], ['lead', 'quoted'], true))        $sum['leads']++;
    if ($p['status'] === 'delivered')                             $sum['delivered']++;
    if ($p['status'] !== 'cancelled') { $sum['value'] += (float)$p['price']; $sum['due'] += max(0, (float)$p['price'] - (float)$p['advance_paid']); }
}
$showPrice = can('projects.price');
$pageTitle = can('projects.view_all') ? 'All Projects' : 'My Projects';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>

<div class="stat-cards">
    <div class="stat-card"><div class="stat-card-icon blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div><div class="stat-card-num"><?php echo $sum['active']; ?></div><div class="stat-card-label">In progress / review</div></div></div>
    <div class="stat-card"><div class="stat-card-icon orange"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div><div class="stat-card-num"><?php echo $sum['leads']; ?></div><div class="stat-card-label">Leads / quoted</div></div></div>
    <div class="stat-card"><div class="stat-card-icon green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div><div class="stat-card-num"><?php echo $sum['delivered']; ?></div><div class="stat-card-label">Delivered</div></div></div>
    <?php if ($showPrice): ?>
    <div class="stat-card"><div class="stat-card-icon red"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <div><div class="stat-card-num" style="font-size:1.35rem;"><?php echo inr($sum['due']); ?></div><div class="stat-card-label">Payment due (of <?php echo inr($sum['value']); ?>)</div></div></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2><?php echo count($projects); ?> project<?php echo count($projects) === 1 ? '' : 's'; ?></h2>
        <?php if (can('projects.create')): ?><a href="project-edit.php" class="abtn abtn-primary">+ New Project</a><?php endif; ?>
    </div>
    <form method="GET" class="filter-bar">
        <input type="search" name="q" value="<?php echo clean($fQuery); ?>" placeholder="Search name, client, code, tech…">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (PROJECT_STATUSES as $k => $v): ?><option value="<?php echo $k; ?>" <?php echo $fStatus === $k ? 'selected' : ''; ?>><?php echo clean($v[0]); ?></option><?php endforeach; ?>
        </select>
        <select name="type">
            <option value="">All types</option>
            <?php foreach (PROJECT_TYPES as $t): ?><option <?php echo $fType === $t ? 'selected' : ''; ?>><?php echo clean($t); ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="abtn abtn-outline">Filter</button>
        <?php if ($fStatus || $fType || $fQuery): ?><a href="projects.php" class="abtn abtn-outline">Clear</a><?php endif; ?>
    </form>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr>
                <th>Project</th><th>Type</th><th>Given by</th><th>Brought by</th><th>Developers</th><th>Status</th><th>Deadline</th>
                <?php if ($showPrice): ?><th>Price</th><?php endif; ?><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if (!$projects): ?>
                <tr><td colspan="<?php echo $showPrice ? 9 : 8; ?>"><div class="empty-state"><h3>No projects found</h3>
                    <?php if (can('projects.create')): ?><p><a href="project-edit.php" style="color:var(--a-blue);">Add the first project →</a></p><?php else: ?><p>Projects assigned to you will appear here.</p><?php endif; ?></div></td></tr>
            <?php endif; ?>
            <?php foreach ($projects as $p):
                $overdue = $p['deadline'] && $p['deadline'] < date('Y-m-d') && !in_array($p['status'], ['delivered', 'cancelled'], true); ?>
                <tr>
                    <td class="td-title"><a href="project-view.php?id=<?php echo (int)$p['id']; ?>" style="color:inherit;"><?php echo clean($p['name']); ?></a>
                        <div class="td-muted"><?php echo clean($p['project_code']); ?><?php echo $p['priority'] === 'high' ? ' · <span style="color:var(--a-danger);">High priority</span>' : ''; ?></div></td>
                    <td><span class="abadge abadge-blue"><?php echo clean($p['project_type']); ?></span></td>
                    <td class="td-muted"><?php echo $p['source_type'] === 'owner_direct' ? 'Direct — ' . clean($p['client_name']) . ' (owner)' : clean($p['client_name'] ?: '—') . ($p['client_company'] ? '<br>' . clean($p['client_company']) : ''); ?></td>
                    <td class="td-muted"><?php echo clean($p['brought_by'] ?: '—'); ?></td>
                    <td class="td-muted"><?php echo clean($p['developers'] ?: '—'); ?></td>
                    <td><?php echo statusBadge($p['status']); ?></td>
                    <td class="td-muted" style="<?php echo $overdue ? 'color:var(--a-danger);font-weight:600;' : ''; ?>"><?php echo fmtDate($p['deadline']); ?><?php echo $overdue ? '<br>overdue' : ''; ?></td>
                    <?php if ($showPrice): ?><td class="td-muted"><?php echo inr($p['price']); ?><?php $due = max(0, $p['price'] - $p['advance_paid']); if ($due > 0 && $p['status'] !== 'cancelled') echo '<br><span style="color:var(--a-warning);">due ' . inr($due) . '</span>'; ?></td><?php endif; ?>
                    <td><div class="btn-group">
                        <a href="project-view.php?id=<?php echo (int)$p['id']; ?>" class="abtn abtn-outline abtn-sm">View</a>
                        <?php if (can('projects.edit')): ?><a href="project-edit.php?id=<?php echo (int)$p['id']; ?>" class="abtn abtn-outline abtn-sm">Edit</a><?php endif; ?>
                        <?php if (can('projects.delete')) echo postButton('projects.php', ['action' => 'delete', 'id' => $p['id']], 'Delete', 'abtn abtn-danger abtn-sm', 'Delete this project and its history?'); ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
