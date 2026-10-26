<?php
// One project: details, team, money (price only for roles that may see it), and the progress timeline.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if (!can('projects.view_all')) requirePermission('projects.view_own');

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$project = $id > 0 ? dbRow("SELECT p.*, b.name AS brought_by, c.name AS created_by
                            FROM client_projects p
                            LEFT JOIN admin_users b ON b.id = p.brought_by_id
                            LEFT JOIN admin_users c ON c.id = p.created_by_id WHERE p.id = ?", 'i', [$id]) : null;
// A developer asking for a project that is not assigned to them gets exactly the same answer as for a project that does not exist.
if (!$project || !canSeeProject($id)) {
    flashMessage('error', 'Project not found.');
    header('Location: projects.php'); exit;
}

// ---- post a progress update (owner, manager, and developers on this project) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    requirePermission('projects.update');
    $note   = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 3000);
    $newSt  = (string)($_POST['status'] ?? $project['status']);
    $allowed = can('projects.edit') ? array_keys(PROJECT_STATUSES) : DEVELOPER_STATUSES;
    if (!in_array($newSt, $allowed, true)) $newSt = $project['status'];      // developers cannot jump to e.g. "delivered"
    $changed = $newSt !== $project['status'];

    if ($note === '' && !$changed) {
        flashMessage('error', 'Write an update or choose a new status.');
    } else {
        $conn->begin_transaction();
        try {
            if ($changed) {
                $delivered = $newSt === 'delivered' ? date('Y-m-d') : null;
                $st = $conn->prepare("UPDATE client_projects SET status = ?, delivered_on = ?, updated_at = NOW() WHERE id = ?");
                $st->bind_param('ssi', $newSt, $delivered, $id); $st->execute();
            }
            $uid = currentUserId();
            $text = $note !== '' ? $note : 'Status changed.';
            $old = $changed ? $project['status'] : null; $new = $changed ? $newSt : null;
            $lg = $conn->prepare("INSERT INTO project_updates (project_id, user_id, note, old_status, new_status) VALUES (?,?,?,?,?)");
            $lg->bind_param('iisss', $id, $uid, $text, $old, $new); $lg->execute();
            $conn->commit();
            flashMessage('success', 'Update added.');
        } catch (Throwable $e) {
            $conn->rollback(); error_log('project update failed: ' . $e->getMessage());
            flashMessage('error', 'Could not save the update. Please try again.');
        }
    }
    header('Location: project-view.php?id=' . $id); exit;
}

$developers = dbRows("SELECT u.name FROM project_developers pd JOIN admin_users u ON u.id = pd.user_id WHERE pd.project_id = ? ORDER BY u.name", 'i', [$id]);
$updates    = dbRows("SELECT pu.*, u.name AS who FROM project_updates pu LEFT JOIN admin_users u ON u.id = pu.user_id WHERE pu.project_id = ? ORDER BY pu.created_at DESC, pu.id DESC LIMIT 200", 'i', [$id]);
$showPrice  = can('projects.price');
$isDev      = !can('projects.view_all');          // developers see a trimmed view: no client contact, no money, no internal notes
$statusOpts = can('projects.edit') ? array_keys(PROJECT_STATUSES) : array_values(array_unique(array_merge([$project['status']], DEVELOPER_STATUSES)));
$due        = max(0, (float)$project['price'] - (float)$project['advance_paid']);
$overdue    = $project['deadline'] && $project['deadline'] < date('Y-m-d') && !in_array($project['status'], ['delivered', 'cancelled'], true);

$pageTitle = $project['name'];
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>

<div class="project-head">
    <div>
        <div class="td-muted"><?php echo clean($project['project_code'] ?? ''); ?> · <?php echo clean($project['project_type']); ?><?php echo $project['priority'] === 'high' ? ' · <span style="color:var(--a-danger);font-weight:600;">High priority</span>' : ''; ?></div>
        <h2 style="margin:4px 0 8px;"><?php echo clean($project['name']); ?></h2>
        <?php echo statusBadge($project['status']); ?>
    </div>
    <div class="btn-group">
        <a href="projects.php" class="abtn abtn-outline abtn-sm">← All projects</a>
        <?php if (can('projects.edit')): ?><a href="project-edit.php?id=<?php echo $id; ?>" class="abtn abtn-primary abtn-sm">Edit</a><?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h2>Details</h2></div>
            <div class="card-body">
                <dl class="facts">
                    <div><dt>Type</dt><dd><?php echo clean($project['project_type']); ?></dd></div>
                    <div><dt>Tech stack</dt><dd><?php
                        $stack = array_filter(array_map('trim', explode(',', (string)$project['tech_stack'])));
                        echo $stack ? implode(' ', array_map(fn($t) => '<span class="abadge abadge-blue">' . clean($t) . '</span>', $stack)) : '—'; ?></dd></div>
                    <div><dt>Developers</dt><dd><?php echo $developers ? clean(implode(', ', array_column($developers, 'name'))) : '—'; ?></dd></div>
                    <div><dt>Start date</dt><dd><?php echo fmtDate($project['start_date']); ?></dd></div>
                    <div><dt>Deadline</dt><dd style="<?php echo $overdue ? 'color:var(--a-danger);font-weight:600;' : ''; ?>"><?php echo fmtDate($project['deadline']); ?><?php echo $overdue ? ' (overdue)' : ''; ?></dd></div>
                    <?php if ($project['delivered_on']): ?><div><dt>Delivered on</dt><dd><?php echo fmtDate($project['delivered_on']); ?></dd></div><?php endif; ?>
                    <?php if (safeUrl($project['live_url'])): ?><div><dt>Live / staging</dt><dd><a href="<?php echo clean(safeUrl($project['live_url'])); ?>" target="_blank" rel="noopener noreferrer" style="color:var(--a-blue);"><?php echo clean($project['live_url']); ?></a></dd></div><?php endif; ?>
                </dl>
                <?php if (trim((string)$project['description']) !== ''): ?>
                    <h4 style="margin:18px 0 6px;">Requirements</h4>
                    <div class="msg-detail"><?php echo nl2br(clean($project['description'])); ?></div>
                <?php endif; ?>
                <?php if (!$isDev && trim((string)$project['hosting_notes']) !== ''): ?>
                    <h4 style="margin:18px 0 6px;">Domain &amp; hosting</h4>
                    <div class="msg-detail"><?php echo nl2br(clean($project['hosting_notes'])); ?></div>
                <?php endif; ?>
                <?php if (!$isDev && trim((string)$project['notes']) !== ''): ?>
                    <h4 style="margin:18px 0 6px;">Internal notes</h4>
                    <div class="msg-detail"><?php echo nl2br(clean($project['notes'])); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Progress &amp; updates</h2></div>
            <div class="card-body">
                <?php if (can('projects.update')): ?>
                <form method="POST" class="update-form" autocomplete="off">
                    <?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>">
                    <textarea name="note" rows="3" maxlength="3000" placeholder="What was done? Any problem, client feedback, next step…"></textarea>
                    <div class="update-actions">
                        <select name="status" aria-label="Status">
                            <?php foreach ($statusOpts as $k): ?><option value="<?php echo $k; ?>" <?php echo $project['status'] === $k ? 'selected' : ''; ?>><?php echo clean(PROJECT_STATUSES[$k][0]); ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="abtn abtn-primary">Add update</button>
                    </div>
                </form>
                <?php endif; ?>
                <ol class="timeline">
                    <?php foreach ($updates as $u): ?>
                    <li>
                        <div class="tl-meta"><strong><?php echo clean($u['who'] ?: 'Former user'); ?></strong> · <?php echo clean(date('d M Y, h:i A', strtotime($u['created_at']))); ?>
                            <?php if ($u['new_status']): ?> · <?php echo $u['old_status'] ? statusBadge($u['old_status']) . ' → ' : ''; ?><?php echo statusBadge($u['new_status']); ?><?php endif; ?></div>
                        <div class="tl-note"><?php echo nl2br(clean($u['note'])); ?></div>
                    </li>
                    <?php endforeach; ?>
                    <?php if (!$updates): ?><li class="td-muted">No updates yet.</li><?php endif; ?>
                </ol>
            </div>
        </div>
    </div>

    <div>
        <?php if ($showPrice): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h2>Money</h2></div>
            <div class="card-body"><dl class="facts one-col">
                <div><dt>Project price</dt><dd style="font-size:1.25rem;font-weight:700;"><?php echo inr($project['price']); ?></dd></div>
                <div><dt>Received</dt><dd><?php echo inr($project['advance_paid']); ?></dd></div>
                <div><dt>Balance due</dt><dd style="color:<?php echo $due > 0 ? 'var(--a-warning)' : 'var(--a-success)'; ?>;font-weight:600;"><?php echo $due > 0 ? inr($due) : 'Fully paid'; ?></dd></div>
            </dl></div>
        </div>
        <?php endif; ?>

        <?php if (!$isDev): ?>
        <div class="card">
            <div class="card-header"><h2>Who gave it</h2></div>
            <div class="card-body"><dl class="facts one-col">
                <div><dt>Source</dt><dd><?php echo $project['source_type'] === 'owner_direct' ? 'Direct from owner' : 'Client'; ?></dd></div>
                <div><dt><?php echo $project['source_type'] === 'owner_direct' ? 'Owner' : 'Name'; ?></dt><dd><?php echo clean($project['client_name'] ?: '—'); ?></dd></div>
                <?php if ($project['source_type'] === 'client'): ?>
                    <?php if ($project['client_company']): ?><div><dt>Company</dt><dd><?php echo clean($project['client_company']); ?></dd></div><?php endif; ?>
                    <?php if ($project['client_phone']): ?><div><dt>Phone</dt><dd><a href="tel:<?php echo clean(preg_replace('/[^0-9+]/', '', $project['client_phone'])); ?>" style="color:var(--a-blue);"><?php echo clean($project['client_phone']); ?></a></dd></div><?php endif; ?>
                    <?php if ($project['client_email']): ?><div><dt>Email</dt><dd><a href="mailto:<?php echo clean($project['client_email']); ?>" style="color:var(--a-blue);"><?php echo clean($project['client_email']); ?></a></dd></div><?php endif; ?>
                <?php endif; ?>
                <div><dt>Brought by</dt><dd><?php echo clean($project['brought_by'] ?: '—'); ?></dd></div>
                <div><dt>Added by</dt><dd><?php echo clean($project['created_by'] ?: '—'); ?>, <?php echo fmtDate($project['created_at']); ?></dd></div>
            </dl></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
