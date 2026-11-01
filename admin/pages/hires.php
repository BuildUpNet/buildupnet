<?php
// All hire requests. Owner sees what needs approval first.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('hiring.manage');

$tabs = ['open' => ['In progress', HIRE_OPEN], 'pending' => ['Waiting for owner', ['pending']], 'joined' => ['Joined', ['joined']],
         'closed' => ['Closed', ['rejected', 'declined', 'cancelled']], 'all' => ['All', array_keys(HIRE_STATUSES)]];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : (can('hiring.approve') && hirePendingCount() > 0 ? 'pending' : 'open');
$q   = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80);

$in = "'" . implode("','", array_map(fn($s) => preg_replace('/[^a-z_]/', '', $s), $tabs[$tab][1])) . "'";     // statuses come from the constant list above
$sql = "SELECT h.id, h.full_name, h.position, h.role, h.status, h.salary_monthly, h.joining_date, h.created_at, c.name AS creator,
               (SELECT COUNT(*) FROM hire_projects hp WHERE hp.hire_id = h.id) AS projects
        FROM hire_requests h JOIN admin_users c ON c.id = h.created_by_id WHERE h.status IN ($in)";
$types = ''; $params = [];
if ($q !== '') { $sql .= " AND (h.full_name LIKE ? OR h.position LIKE ?)"; $types = 'ss'; $like = '%' . addcslashes($q, '%_\\') . '%'; $params = [$like, $like]; }
$rows = dbRows($sql . " ORDER BY FIELD(h.status,'pending','approved','offer_issued','accepted','draft','joined','rejected','declined','cancelled'), h.created_at DESC LIMIT 200", $types, $params);
$counts = [];
foreach (dbRows("SELECT status, COUNT(*) AS c FROM hire_requests GROUP BY status") as $r) $counts[$r['status']] = (int)$r['c'];
$tabCount = fn($k) => array_sum(array_map(fn($s) => $counts[$s] ?? 0, $tabs[$k][1]));

$pageTitle = 'Hiring';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<div class="tabs"><?php foreach ($tabs as $k => [$label]): ?>
    <a href="hires.php?tab=<?php echo $k; ?>" class="<?php echo $k === $tab ? 'active' : ''; ?>"><?php echo clean($label); ?> (<?php echo $tabCount($k); ?>)</a><?php endforeach; ?></div>

<div class="card">
    <div class="card-header"><h2><?php echo clean($tabs[$tab][0]); ?></h2><a href="hire-edit.php" class="abtn abtn-primary">+ New hire</a></div>
    <form method="GET" class="filter-bar" style="padding:14px 20px;"><input type="hidden" name="tab" value="<?php echo clean($tab); ?>">
        <input type="search" name="q" value="<?php echo clean($q); ?>" placeholder="Search name or position"><button class="abtn abtn-outline abtn-sm" type="submit">Search</button></form>
    <div style="overflow-x:auto;"><table class="admin-table table-cards">
        <thead><tr><th>Candidate</th><th>Role</th><th>Status</th><th>Salary</th><th>Joining</th><th>Projects</th><th>Made by</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="td-muted" style="text-align:center;padding:24px;">Nothing here.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="Candidate" class="td-title"><a href="hire-view.php?id=<?php echo (int)$r['id']; ?>"><?php echo clean($r['full_name']); ?></a><div class="td-muted"><?php echo clean($r['position']); ?></div></td>
                <td data-label="Role"><?php echo clean(HIRE_ROLES[$r['role']] ?? $r['role']); ?></td>
                <td data-label="Status"><?php echo hireBadge($r['status']); ?></td>
                <td data-label="Salary"><?php echo clean(inr($r['salary_monthly'])); ?></td>
                <td data-label="Joining" class="td-muted"><?php echo clean(fmtDate($r['joining_date'])); ?></td>
                <td data-label="Projects" class="td-muted"><?php echo (int)$r['projects']; ?></td>
                <td data-label="Made by" class="td-muted"><?php echo clean($r['creator']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
