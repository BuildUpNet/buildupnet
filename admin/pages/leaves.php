<?php
// Leave: everybody applies for their own; owner/manager approve or reject (never their own request).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('attendance.self');

$me = currentUserId(); $isMgr = can('attendance.manage'); $today = date('Y-m-d');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $act = (string)($_POST['action'] ?? '');

    if ($act === 'apply') {
        $from = attDateOrNull($_POST['from_date'] ?? ''); $to = attDateOrNull($_POST['to_date'] ?? '') ?? $from;
        $type = isset(LEAVE_TYPES[$_POST['leave_type'] ?? '']) ? $_POST['leave_type'] : 'casual';
        $reason = mb_substr(plainText((string)($_POST['reason'] ?? '')), 0, 500);
        if (!$from || !$to) $errors[] = 'Choose valid dates.';
        elseif ($to < $from) $errors[] = '"To" date cannot be before "From" date.';
        elseif ($from < date('Y-m-d', strtotime('-30 days'))) $errors[] = 'Leave older than 30 days cannot be requested — ask your manager to mark it.';
        elseif ((strtotime($to) - strtotime($from)) / 86400 > 60) $errors[] = 'One request can cover at most 61 days.';
        elseif ($reason === '') $errors[] = 'Please write a short reason.';
        elseif (dbRow("SELECT id FROM leave_requests WHERE user_id = ? AND status IN ('pending','approved') AND from_date <= ? AND to_date >= ?", 'iss', [$me, $to, $from]))
            $errors[] = 'You already have a leave request covering some of these days.';
        if (!$errors) {
            $status = currentRole() === 'owner' ? 'approved' : 'pending';          // nobody is above the owner
            $by = $status === 'approved' ? $me : null; $at = $status === 'approved' ? date('Y-m-d H:i:s') : null;
            $st = $conn->prepare("INSERT INTO leave_requests (user_id, from_date, to_date, leave_type, reason, status, decided_by_id, decided_at) VALUES (?,?,?,?,?,?,?,?)");
            $st->bind_param('isssssis', $me, $from, $to, $type, $reason, $status, $by, $at); $st->execute();
            flashMessage('success', $status === 'approved' ? 'Leave recorded.' : 'Leave request sent. You will see the decision here.');
            header('Location: leaves.php'); exit;
        }
    } elseif ($act === 'cancel') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $conn->prepare("UPDATE leave_requests SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending','approved') AND to_date >= ?"); $st->bind_param('iis', $id, $me, $today); $st->execute();
        flashMessage($st->affected_rows ? 'success' : 'error', $st->affected_rows ? 'Leave cancelled.' : 'This request cannot be cancelled.');
        header('Location: leaves.php'); exit;
    } elseif ($act === 'approve' || $act === 'reject') {
        requirePermission('attendance.manage');
        $id = (int)($_POST['id'] ?? 0);
        $req = dbRow("SELECT l.*, u.role AS user_role FROM leave_requests l JOIN admin_users u ON u.id = l.user_id WHERE l.id = ?", 'i', [$id]);
        if (!$req || $req['status'] !== 'pending') flashMessage('error', 'This request is no longer pending.');
        elseif ((int)$req['user_id'] === $me || !attCanManage($req['user_role'])) flashMessage('error', 'You cannot decide this request (not your own, and only the owner decides for the owner).');
        else {
            $status = $act === 'approve' ? 'approved' : 'rejected'; $note = mb_substr(plainText((string)($_POST['decision_note'] ?? '')), 0, 255); $now = date('Y-m-d H:i:s');
            $st = $conn->prepare("UPDATE leave_requests SET status = ?, decided_by_id = ?, decided_at = ?, decision_note = ? WHERE id = ? AND status = 'pending'");
            $st->bind_param('sissi', $status, $me, $now, $note, $id); $st->execute();
            flashMessage('success', 'Request ' . $status . '.');
        }
        header('Location: leaves.php'); exit;
    }
}

$mine    = dbRows("SELECT l.*, d.name AS decided_by FROM leave_requests l LEFT JOIN admin_users d ON d.id = l.decided_by_id WHERE l.user_id = ? ORDER BY l.from_date DESC LIMIT 30", 'i', [$me]);
$pending = $isMgr ? dbRows("SELECT l.*, u.name, u.role AS user_role FROM leave_requests l JOIN admin_users u ON u.id = l.user_id WHERE l.status = 'pending' ORDER BY l.from_date", '', []) : [];
$recent  = $isMgr ? dbRows("SELECT l.*, u.name, d.name AS decided_by FROM leave_requests l JOIN admin_users u ON u.id = l.user_id LEFT JOIN admin_users d ON d.id = l.decided_by_id WHERE l.status <> 'pending' ORDER BY l.created_at DESC LIMIT 30") : [];
$badge = ['pending' => 'abadge-orange', 'approved' => 'abadge-green', 'rejected' => 'abadge-red', 'cancelled' => 'abadge-grey'];
$span  = fn($r) => date('j M', strtotime($r['from_date'])) . ($r['to_date'] !== $r['from_date'] ? ' – ' . date('j M Y', strtotime($r['to_date'])) : ' ' . date('Y', strtotime($r['from_date'])));
$days  = fn($r) => (int)((strtotime($r['to_date']) - strtotime($r['from_date'])) / 86400) + 1;

$pageTitle = 'Leave';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php foreach ($errors as $err): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($err); ?></div><?php endforeach; ?>
<?php renderFlash(); ?>
<?php if ($isMgr) echo attTabs('leaves'); ?>
<div data-region="leaves">

<?php if ($isMgr): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>Waiting for a decision (<?php echo count($pending); ?>)</h2></div>
    <div style="overflow-x:auto;"><table class="admin-table table-cards"><thead><tr><th>Person</th><th>Dates</th><th>Type</th><th>Reason</th><th>Decision</th></tr></thead><tbody>
    <?php if (!$pending): ?><tr><td colspan="5" class="td-muted" style="text-align:center;padding:20px;">No pending requests</td></tr><?php endif; ?>
    <?php foreach ($pending as $r): $can = (int)$r['user_id'] !== $me && attCanManage($r['user_role']); ?>
        <tr><td data-label="Person" class="td-title"><?php echo clean($r['name']); ?></td>
            <td data-label="Dates"><?php echo clean($span($r)); ?> <span class="td-muted">(<?php echo $days($r); ?> day<?php echo $days($r) > 1 ? 's' : ''; ?>)</span></td>
            <td data-label="Type"><?php echo clean(LEAVE_TYPES[$r['leave_type']] ?? $r['leave_type']); ?></td>
            <td data-label="Reason" class="td-muted" style="white-space:normal;"><?php echo clean((string)$r['reason']); ?></td>
            <td data-label="Decision"><?php if ($can): ?>
                <form method="POST" class="decide" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <input type="text" name="decision_note" maxlength="255" placeholder="Note (optional)">
                    <div class="btn-group"><button class="abtn abtn-primary abtn-sm" name="action" value="approve" type="submit">Approve</button><button class="abtn abtn-danger abtn-sm" name="action" value="reject" type="submit">Reject</button></div>
                </form><?php else: ?><span class="td-muted"><?php echo (int)$r['user_id'] === $me ? 'Your own request' : 'Owner decides'; ?></span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>Apply for leave</h2></div>
    <div class="card-body">
        <form method="POST" data-ajax><?php echo csrfField(); ?><input type="hidden" name="action" value="apply">
            <div class="form-grid">
                <div class="form-group"><label>From *</label><input type="date" name="from_date" required min="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" value="<?php echo clean((string)($_POST['from_date'] ?? $today)); ?>"></div>
                <div class="form-group"><label>To *</label><input type="date" name="to_date" required min="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" value="<?php echo clean((string)($_POST['to_date'] ?? $today)); ?>"></div>
                <div class="form-group"><label>Type</label><select name="leave_type"><?php foreach (LEAVE_TYPES as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo clean($l); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Reason *</label><input type="text" name="reason" maxlength="500" required value="<?php echo clean((string)($_POST['reason'] ?? '')); ?>"></div>
            </div>
            <p class="hint" style="margin-top:10px;">Weekly offs and holidays inside the dates are not counted as leave. <?php echo currentRole() === 'owner' ? 'Owner leave is approved automatically.' : 'Your manager / owner will approve or reject.'; ?></p>
            <div class="form-actions" style="margin-top:14px;"><button class="abtn abtn-primary" type="submit">Send request</button></div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>My leave requests</h2></div>
    <div style="overflow-x:auto;"><table class="admin-table table-cards"><thead><tr><th>Dates</th><th>Type</th><th>Status</th><th>Decision</th><th></th></tr></thead><tbody>
    <?php if (!$mine): ?><tr><td colspan="5" class="td-muted" style="text-align:center;padding:20px;">No requests yet</td></tr><?php endif; ?>
    <?php foreach ($mine as $r): ?>
        <tr><td data-label="Dates" class="td-title"><?php echo clean($span($r)); ?> <span class="td-muted">(<?php echo $days($r); ?>d)</span><div class="td-muted"><?php echo clean((string)$r['reason']); ?></div></td>
            <td data-label="Type"><?php echo clean(LEAVE_TYPES[$r['leave_type']] ?? ''); ?></td>
            <td data-label="Status"><span class="abadge <?php echo $badge[$r['status']] ?? 'abadge-grey'; ?>"><?php echo clean(ucfirst($r['status'])); ?></span></td>
            <td data-label="Decision" class="td-muted"><?php echo $r['decided_by'] ? clean($r['decided_by']) . ($r['decision_note'] ? ': ' . clean($r['decision_note']) : '') : '—'; ?></td>
            <td data-label=""><?php if (in_array($r['status'], ['pending', 'approved'], true) && $r['to_date'] >= $today) echo postButton('leaves.php', ['action' => 'cancel', 'id' => $r['id']], 'Cancel', 'abtn abtn-outline abtn-sm', 'Cancel this leave?', '', true); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>

<?php if ($isMgr): ?>
<div class="card">
    <div class="card-header"><h2>Recent decisions (team)</h2></div>
    <div style="overflow-x:auto;"><table class="admin-table table-cards"><thead><tr><th>Person</th><th>Dates</th><th>Type</th><th>Status</th><th>By</th></tr></thead><tbody>
    <?php foreach ($recent as $r): ?>
        <tr><td data-label="Person" class="td-title"><?php echo clean($r['name']); ?></td><td data-label="Dates"><?php echo clean($span($r)); ?></td><td data-label="Type"><?php echo clean(LEAVE_TYPES[$r['leave_type']] ?? ''); ?></td>
            <td data-label="Status"><span class="abadge <?php echo $badge[$r['status']] ?? 'abadge-grey'; ?>"><?php echo clean(ucfirst($r['status'])); ?></span></td><td data-label="By" class="td-muted"><?php echo clean((string)$r['decided_by']) ?: '—'; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
