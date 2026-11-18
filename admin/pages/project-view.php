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

/** Ends a POST: JSON for the no-reload buttons, otherwise flash + redirect. */
function projDone(bool $ok, string $msg, int $id): void {
    if (isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['ok' => $ok, 'message' => $msg]); exit; }
    flashMessage($ok ? 'success' : 'error', $msg);
    header('Location: project-view.php?id=' . $id); exit;
}
function projDate(string $k): ?string { $v = trim((string)($_POST[$k] ?? '')); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null; }

$isMgr = can('projects.edit'); $me = currentUserId(); $today = date('Y-m-d'); $flow = projFlowReady();
$validDevs = array_map('intval', array_column(staffList(['developer']), 'id'));
$validMgrs = array_map('intval', array_column(staffList(['manager', 'owner']), 'id'));   // a project's manager: manager or owner

// ---- every change is a POST: CSRF + role + current-status checks ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    requirePermission('projects.update');
    $act  = (string)($_POST['action'] ?? '');
    $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 3000);
    $st0  = $project['status']; $err = null; $okMsg = 'Done.';

    try {
    switch ($act) {
        case '':      // the plain "add a note / change status" box
            $newSt = (string)($_POST['status'] ?? $st0);
            $allowed = $isMgr ? array_keys(PROJECT_STATUSES) : DEVELOPER_STATUSES;
            if (!in_array($newSt, $allowed, true)) $newSt = $st0;           // developers cannot jump to e.g. "delivered"
            if ($note === '' && $newSt === $st0) { $err = 'Write an update or choose a new status.'; break; }
            $conn->begin_transaction();
            if ($newSt !== $st0) $err = projectMove($project, $newSt, $note);
            else { $lg = $conn->prepare("INSERT INTO project_updates (project_id, user_id, note) VALUES (?,?,?)"); $lg->bind_param('iis', $id, $me, $note); $lg->execute(); }
            $err ? $conn->rollback() : $conn->commit(); $okMsg = 'Update added.';
            break;

        case 'confirm':       // client confirmed the quotation: pick the developers (one or many), dates, start work
            if (!$isMgr) { $err = 'Only the owner or a manager can confirm a project.'; break; }
            if (!in_array($st0, ['lead', 'quoted'], true)) { $err = 'Only a project that is still at quotation stage can be confirmed.'; break; }
            $devIds = array_values(array_intersect(array_map('intval', (array)($_POST['developers'] ?? [])), $validDevs));
            $mgrIds = array_values(array_intersect(array_map('intval', (array)($_POST['managers'] ?? [])), $validMgrs)); $teamAll = array_values(array_unique(array_merge($mgrIds, $devIds)));
            $start = projDate('start_date'); $dead = projDate('deadline'); $now = !empty($_POST['start_now']);
            if (!$mgrIds) { $err = 'Choose at least one manager (a manager or the owner).'; break; }
            if (!$devIds) { $err = 'Choose at least one developer.'; break; }
            if ($start && $dead && $dead < $start) { $err = 'Deadline cannot be before the start date.'; break; }
            $conn->begin_transaction();
            $conn->query("DELETE FROM project_developers WHERE project_id = " . (int)$id);
            $pd = $conn->prepare("INSERT INTO project_developers (project_id, user_id) VALUES (?, ?)"); foreach ($teamAll as $d) { $pd->bind_param('ii', $id, $d); $pd->execute(); }
            $up = $conn->prepare("UPDATE client_projects SET start_date = COALESCE(?, start_date, ?), deadline = COALESCE(?, deadline) WHERE id = ?"); $up->bind_param('sssi', $start, $today, $dead, $id); $up->execute();
            $names = implode(', ', array_column(dbRows("SELECT name FROM admin_users WHERE id IN (" . implode(',', $teamAll) . ") ORDER BY name"), 'name'));
            $err = projectMove($project, $now ? 'in_progress' : 'confirmed', 'Quotation confirmed by the client. Team: ' . $names . '.' . ($note !== '' ? "\n" . $note : ''));
            $err ? $conn->rollback() : $conn->commit(); $okMsg = $now ? 'Confirmed. Work has started — developers can send daily reports.' : 'Confirmed. A developer can now press "Start work".';
            break;

        case 'assign_team':   // add / remove developers any time before the project is finished
            if (!$isMgr) { $err = 'Only the owner or a manager can change the team.'; break; }
            if (in_array($st0, PROJECT_FINISHED, true)) { $err = 'This project is finished.'; break; }
            $devIds = array_values(array_intersect(array_map('intval', (array)($_POST['developers'] ?? [])), $validDevs));
            $mgrIds = array_values(array_intersect(array_map('intval', (array)($_POST['managers'] ?? [])), $validMgrs)); $teamAll = array_values(array_unique(array_merge($mgrIds, $devIds)));
            if ((!$devIds || !$mgrIds) && in_array($st0, PROJECT_NEEDS_TEAM, true)) { $err = 'A running project needs at least one manager and at least one developer.'; break; }
            $conn->begin_transaction();
            $conn->query("DELETE FROM project_developers WHERE project_id = " . (int)$id);
            if ($teamAll) { $pd = $conn->prepare("INSERT INTO project_developers (project_id, user_id) VALUES (?, ?)"); foreach ($teamAll as $d) { $pd->bind_param('ii', $id, $d); $pd->execute(); } }
            $names = $teamAll ? implode(', ', array_column(dbRows("SELECT name FROM admin_users WHERE id IN (" . implode(',', $teamAll) . ") ORDER BY name"), 'name')) : 'nobody';
            $lg = $conn->prepare("INSERT INTO project_updates (project_id, user_id, note) VALUES (?,?,?)"); $t = 'Team updated: ' . $names . '.'; $lg->bind_param('iis', $id, $me, $t); $lg->execute();
            $conn->commit(); $okMsg = 'Team saved.';
            break;

        case 'start':
            if ($st0 !== 'confirmed') { $err = 'Work can start only on a confirmed project.'; break; }
            $err = projectMove($project, 'in_progress', $note !== '' ? $note : 'Work started.'); $okMsg = 'Work started.';
            break;

        case 'daily':        // each developer's daily progress report (one per person per day, saving again today replaces it)
            if (!$flow) { $err = 'Run upgrade-project-flow.sql first (daily reports need it).'; break; }
            if (!in_array($st0, ['in_progress', 'review'], true)) { $err = 'Daily reports are for projects that are in progress.'; break; }
            $work = mb_substr(trim((string)($_POST['work'] ?? '')), 0, 2000);
            $hours = trim((string)($_POST['hours'] ?? '')) === '' ? null : round((float)$_POST['hours'], 1);
            $pct = trim((string)($_POST['pct'] ?? '')) === '' ? null : (int)$_POST['pct'];
            if ($work === '') { $err = 'Write what you worked on today.'; break; }
            if ($hours !== null && ($hours < 0 || $hours > 24)) { $err = 'Hours must be between 0 and 24.'; break; }
            if ($pct !== null && ($pct < 0 || $pct > 100)) { $err = 'Progress must be between 0 and 100.'; break; }
            $conn->begin_transaction();
            $q = $conn->prepare("INSERT INTO project_updates (project_id, user_id, note, kind, progress_pct, hours, report_date) VALUES (?,?,?,'daily',?,?,?)
                                 ON DUPLICATE KEY UPDATE note = VALUES(note), progress_pct = VALUES(progress_pct), hours = VALUES(hours)");
            $q->bind_param('iisids', $id, $me, $work, $pct, $hours, $today); $q->execute();
            if ($pct !== null) { $up = $conn->prepare("UPDATE client_projects SET progress_pct = ?, updated_at = NOW() WHERE id = ?"); $up->bind_param('ii', $pct, $id); $up->execute(); }
            $conn->commit(); $okMsg = 'Daily report saved.';
            break;

        case 'submit_review':
            if ($st0 !== 'in_progress') { $err = 'Only a project in progress can be sent for final review.'; break; }
            $err = projectMove($project, 'review', $note !== '' ? $note : 'Work finished, sent for final review.'); $okMsg = 'Sent for final review.';
            break;

        case 'send_back':
            if (!$isMgr) { $err = 'Only the owner or a manager can send a project back.'; break; }
            if ($st0 !== 'review') { $err = 'Only a project in final review can be sent back.'; break; }
            if ($note === '') { $err = 'Write what the developers must fix.'; break; }
            $err = projectMove($project, 'in_progress', 'Sent back: ' . $note); $okMsg = 'Sent back to the developers.';
            break;

        case 'deliver':
            if (!$isMgr) { $err = 'Only the owner or a manager can mark a project as delivered.'; break; }
            if ($st0 !== 'review') { $err = 'A project must be in final review before it is delivered.'; break; }
            $live = trim((string)($_POST['live_url'] ?? ''));
            if ($live !== '' && safeUrl($live) === '') { $err = 'Live URL must start with http:// or https://'; break; }
            $conn->begin_transaction();
            if ($live !== '') { $lu = $conn->prepare("UPDATE client_projects SET live_url = ? WHERE id = ?"); $lu->bind_param('si', $live, $id); $lu->execute(); }
            $err = projectMove($project, 'delivered', $note !== '' ? $note : 'Final work approved and delivered to the client.');
            $err ? $conn->rollback() : $conn->commit(); $okMsg = 'Marked as delivered.';
            break;

        case 'close':
            if (!$isMgr) { $err = 'Only the owner or a manager can close a project.'; break; }
            if ($st0 !== 'delivered') { $err = 'Only a delivered project can be closed.'; break; }
            $err = projectMove($project, 'closed', $note !== '' ? $note : 'Project closed.'); $okMsg = 'Project closed.';
            break;

        case 'reopen':
            if (currentRole() !== 'owner') { $err = 'Only the owner can reopen a finished project.'; break; }
            if (!in_array($st0, ['delivered', 'closed'], true)) { $err = 'This project is not finished.'; break; }
            $err = projectMove($project, 'in_progress', 'Reopened. ' . $note); $okMsg = 'Project reopened.';
            break;

        default: $err = 'Unknown action.';
    }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        error_log('project action failed: ' . $e->getMessage());
        $err = 'Could not save. Please try again.';
    }
    projDone(!$err, $err ?? $okMsg, $id);
}

$teamRows   = dbRows("SELECT u.id, u.name, u.role FROM project_developers pd JOIN admin_users u ON u.id = pd.user_id WHERE pd.project_id = ? ORDER BY u.name", 'i', [$id]);
$mgrRows    = array_values(array_filter($teamRows, fn($r) => in_array($r['role'], ['manager', 'owner'], true)));
$devRows    = array_values(array_filter($teamRows, fn($r) => $r['role'] === 'developer'));      // developers owe the daily reports
$developers = dbRows("SELECT u.name FROM project_developers pd JOIN admin_users u ON u.id = pd.user_id WHERE pd.project_id = ? ORDER BY u.name", 'i', [$id]);
$updates    = dbRows("SELECT pu.*, u.name AS who FROM project_updates pu LEFT JOIN admin_users u ON u.id = pu.user_id WHERE pu.project_id = ? ORDER BY pu.created_at DESC, pu.id DESC LIMIT 200", 'i', [$id]);
$todayRep   = [];                                   // user_id => today's daily report
if ($flow) foreach (dbRows("SELECT * FROM project_updates WHERE project_id = ? AND kind = 'daily' AND report_date = ?", 'is', [$id, $today]) as $r) $todayRep[(int)$r['user_id']] = $r;
$myRep      = $todayRep[$me] ?? null;
$inTeam     = in_array($me, array_map('intval', array_column($teamRows, 'id')), true);
$teamIds    = array_map('intval', array_column($teamRows, 'id'));
$allDevs    = staffList(['developer']);
$allMgrs    = staffList(['manager', 'owner']);
$showPrice  = can('projects.price');
$isDev      = !can('projects.view_all');          // developers see a trimmed view: no client contact, no money, no internal notes
$statusOpts = can('projects.edit') ? array_keys(PROJECT_STATUSES) : array_values(array_unique(array_merge([$project['status']], DEVELOPER_STATUSES)));
$due        = max(0, (float)$project['price'] - (float)$project['advance_paid']);
$overdue    = $project['deadline'] && $project['deadline'] < date('Y-m-d') && !in_array($project['status'], PROJECT_FINISHED, true);

$aj = fn($act, $label, $cls, $confirm = '') => postButton('project-view.php?id=' . $id, ['action' => $act, 'id' => $id], $label, $cls, $confirm, '', true);
$stepKeys = array_keys(PROJECT_STEPS); $curStep = array_search($project['status'] === 'lead' ? 'quoted' : $project['status'], $stepKeys, true);
$pct = (int)($project['progress_pct'] ?? 0);
$teamChecks = function() use ($allDevs, $teamIds) { foreach ($allDevs as $d): ?><label><input type="checkbox" name="developers[]" value="<?php echo (int)$d['id']; ?>" <?php echo in_array((int)$d['id'], $teamIds, true) ? 'checked' : ''; ?>> <?php echo clean($d['name']); ?></label><?php endforeach; };
$mgrChecks = function() use ($allMgrs, $teamIds) { foreach ($allMgrs as $d): ?><label><input type="checkbox" name="managers[]" value="<?php echo (int)$d['id']; ?>" <?php echo in_array((int)$d['id'], $teamIds, true) ? 'checked' : ''; ?>> <?php echo clean($d['name']); ?> <span class="td-muted">(<?php echo clean(ROLE_LABELS[$d['role']] ?? ''); ?>)</span></label><?php endforeach; };

$pageTitle = $project['name'];
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<div data-region="proj">

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

<ol class="steps"><?php foreach (PROJECT_STEPS as $k => $lbl): $i = array_search($k, $stepKeys, true); $cls = $curStep === false ? '' : ($i < $curStep ? 'done' : ($i === $curStep ? 'now' : '')); ?>
    <li class="<?php echo $cls; ?>"><?php echo clean($lbl); ?></li><?php endforeach; ?></ol>
<?php if ($flow && !in_array($project['status'], ['lead', 'quoted', 'cancelled'], true)): ?>
<div class="pbar-wrap"><div class="pbar"><i style="width:<?php echo $pct; ?>%"></i></div><span class="td-muted"><?php echo $pct; ?>% complete</span></div>
<?php elseif (!$flow && $isMgr): ?><div class="flash flash-error" style="background:#FFF6E5;border-left:4px solid #E6A23C;color:#7a5200;">Daily reports and progress % are switched off until you run <b>upgrade-project-flow.sql</b> in phpMyAdmin.</div><?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>Next step</h2></div>
    <div class="card-body">
    <?php switch ($project['status']):
        case 'lead': case 'quoted': ?>
            <?php if ($isMgr): ?>
            <p><b>Client said yes?</b> Confirm the project, choose the manager(s) and developer(s) — at least one of each — and the dates.</p>
            <form method="POST" action="project-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="confirm">
                <label style="font-weight:600;">Managers * <span class="hint">(at least one — manager or owner)</span></label>
                <div class="check-list" style="margin:6px 0 14px;"><?php $mgrChecks(); ?></div>
                <label style="font-weight:600;">Developers * <span class="hint">(at least one — more is fine)</span></label>
                <?php if (!$allDevs): ?><p class="hint">No developer accounts yet. Add them in Users &amp; Roles or hire one from Hiring.</p><?php endif; ?>
                <div class="check-list" style="margin:6px 0 14px;"><?php $teamChecks(); ?></div>
                <div class="form-grid">
                    <div class="form-group"><label>Start date</label><input type="date" name="start_date" value="<?php echo clean((string)($project['start_date'] ?: $today)); ?>"></div>
                    <div class="form-group"><label>Deadline</label><input type="date" name="deadline" value="<?php echo clean((string)$project['deadline']); ?>"></div>
                </div>
                <label class="chk-line"><input type="checkbox" name="start_now" value="1" checked> Start work immediately</label>
                <div class="form-actions" style="margin-top:12px;"><button class="abtn abtn-primary" type="submit" data-confirm="Confirm this project and assign the selected developers?">Confirm project &amp; assign developers</button></div>
            </form>
            <?php else: ?><p class="td-muted">Waiting for the client to confirm the quotation.</p><?php endif; ?>
        <?php break;
        case 'confirmed': ?>
            <p>Confirmed<?php echo !empty($project['confirmed_on']) ? ' on ' . clean(fmtDate($project['confirmed_on'])) : ''; ?>. The team can start now.</p>
            <?php echo $aj('start', 'Start work', 'abtn abtn-primary'); ?>
        <?php break;
        case 'in_progress': ?>
            <p>Work is on. Every developer sends a short <b>daily report</b> below. When everything is finished, send it for final review.</p>
            <?php echo $aj('submit_review', 'Work finished — send for final review', 'abtn abtn-primary', 'Send this project for final review?'); ?>
        <?php break;
        case 'review': ?>
            <?php if ($isMgr): ?>
            <p>The developers say it is finished. Check it, then deliver it to the client — or send it back with what to fix.</p>
            <form method="POST" action="project-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="form-grid">
                    <div class="form-group"><label>Live URL <span class="hint">(optional)</span></label><input type="url" name="live_url" maxlength="300" value="<?php echo clean((string)$project['live_url']); ?>" placeholder="https://"></div>
                    <div class="form-group"><label>Note <span class="hint">(needed when sending back)</span></label><input type="text" name="note" maxlength="500"></div>
                </div>
                <div class="btn-group" style="margin-top:12px;"><button class="abtn abtn-primary" name="action" value="deliver" type="submit" data-confirm="Mark this project as delivered to the client?">Final OK — delivered</button>
                    <button class="abtn abtn-outline" name="action" value="send_back" type="submit">Send back for fixes</button></div>
            </form>
            <?php else: ?><p class="td-muted">Waiting for the manager's final check.</p><?php endif; ?>
        <?php break;
        case 'delivered': ?>
            <p>Delivered<?php echo $project['delivered_on'] ? ' on ' . clean(fmtDate($project['delivered_on'])) : ''; ?>. Once payment and handover are done, close the project.</p>
            <?php if ($isMgr): ?>
            <form method="POST" action="project-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="close">
                <div class="form-group"><label>Closing note <span class="hint">(optional)</span></label><input type="text" name="note" maxlength="500" placeholder="Payment received, files handed over…"></div>
                <div class="form-actions" style="margin-top:12px;"><button class="abtn abtn-primary" type="submit" data-confirm="Close this project?">Close project</button></div>
            </form><?php endif; ?>
        <?php break;
        case 'closed': ?>
            <p>Closed<?php echo !empty($project['closed_on']) ? ' on ' . clean(fmtDate($project['closed_on'])) : ''; ?>.</p>
            <?php if (currentRole() === 'owner') echo $aj('reopen', 'Reopen project', 'abtn abtn-outline abtn-sm', 'Reopen this project?'); ?>
        <?php break;
        default: ?><p class="td-muted">Status: <?php echo clean(PROJECT_STATUSES[$project['status']][0] ?? $project['status']); ?>. <?php echo $isMgr ? 'Use Edit or the status box below to move it on.' : ''; ?></p>
    <?php endswitch; ?>
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
                    <div><dt>Managers</dt><dd><?php echo $mgrRows ? clean(implode(', ', array_column($mgrRows, 'name'))) : '—'; ?></dd></div>
                    <div><dt>Developers</dt><dd><?php echo $devRows ? clean(implode(', ', array_column($devRows, 'name'))) : '—'; ?></dd></div>
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

        <?php if ($flow && in_array($project['status'], ['in_progress', 'review'], true) && can('projects.update') && ($inTeam || $isMgr)): ?>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h2>Daily report — <?php echo clean(date('d M Y')); ?></h2><?php echo $myRep ? '<span class="abadge abadge-green">Sent today</span>' : '<span class="abadge abadge-orange">Not sent yet</span>'; ?></div>
            <div class="card-body">
                <form method="POST" action="project-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="daily">
                    <div class="form-group"><label>What did you do today? *</label><textarea name="work" rows="3" maxlength="2000" required placeholder="Pages finished, bugs fixed, what is pending, any problem…"><?php echo clean((string)($myRep['note'] ?? '')); ?></textarea></div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group"><label>Hours worked on this project</label><input type="number" name="hours" min="0" max="24" step="0.5" value="<?php echo clean((string)($myRep['hours'] ?? '')); ?>"></div>
                        <div class="form-group"><label>Whole project complete (%)</label><input type="number" name="pct" min="0" max="100" step="1" value="<?php echo clean((string)($myRep['progress_pct'] ?? $pct)); ?>"></div>
                    </div>
                    <div class="form-actions" style="margin-top:12px;"><button class="abtn abtn-primary" type="submit"><?php echo $myRep ? 'Update today\'s report' : 'Send daily report'; ?></button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

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
                            <?php if (($u['kind'] ?? '') === 'daily'): ?> · <span class="abadge abadge-blue">Daily report</span><?php echo $u['hours'] !== null ? ' ' . clean((string)(float)$u['hours']) . ' h' : ''; ?><?php echo $u['progress_pct'] !== null ? ' · ' . (int)$u['progress_pct'] . '%' : ''; ?><?php endif; ?>
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
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h2>Team (<?php echo count($teamRows); ?>)</h2></div>
            <div class="card-body">
                <?php if ($isMgr && !in_array($project['status'], PROJECT_FINISHED, true)): ?>
                <form method="POST" action="project-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="assign_team">
                    <h4 style="margin:0 0 6px;">Managers <span class="hint">(at least 1)</span></h4>
                    <div class="check-list" style="grid-template-columns:1fr;"><?php $mgrChecks(); ?></div>
                    <h4 style="margin:14px 0 6px;">Developers <span class="hint">(at least 1)</span></h4>
                    <div class="check-list" style="grid-template-columns:1fr;"><?php $teamChecks(); ?></div>
                    <p class="hint" style="margin:8px 0;">A project can have many managers and many developers, and one person can be on many projects.</p>
                    <button class="abtn abtn-outline abtn-sm" type="submit">Save team</button>
                </form>
                <?php else: ?>
                    <p style="margin:0 0 6px;"><b>Managers:</b> <?php echo $mgrRows ? clean(implode(', ', array_column($mgrRows, 'name'))) : '<span class="td-muted">none</span>'; ?></p>
                    <p style="margin:0;"><b>Developers:</b> <?php echo $devRows ? clean(implode(', ', array_column($devRows, 'name'))) : '<span class="td-muted">none</span>'; ?></p><?php endif; ?>
                <?php if ($flow && in_array($project['status'], ['in_progress', 'review'], true) && $devRows): ?>
                <h4 style="margin:16px 0 6px;">Today's reports</h4>
                <ul style="list-style:none;margin:0;padding:0;"><?php foreach ($devRows as $d): $r = $todayRep[(int)$d['id']] ?? null; ?>
                    <li style="display:flex;justify-content:space-between;gap:8px;padding:4px 0;"><span><?php echo clean($d['name']); ?></span><?php echo $r ? '<span class="abadge abadge-green">Sent</span>' : '<span class="abadge abadge-orange">Pending</span>'; ?></li><?php endforeach; ?></ul>
                <?php endif; ?>
            </div>
        </div>
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
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
