<?php
// Owner / manager: correct or mark one person's day (present, half day, absent, leave, exact times).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('attendance.manage');

$uid  = (int)($_GET['user'] ?? $_POST['user'] ?? 0);
$date = attDateOrNull($_GET['date'] ?? $_POST['date'] ?? '') ?? '';
$target = $uid ? dbRow("SELECT id, name, role FROM admin_users WHERE id = ?", 'i', [$uid]) : null;
// Same answer for "does not exist" and "not yours to change" — and no future dates
if (!$target || $date === '' || $date > date('Y-m-d') || !attCanManage($target['role'])) {
    flashMessage('error', 'That record cannot be opened.'); header('Location: attendance-manage.php'); exit;
}
$sched = attSchedule($uid);
$rec   = dbRow("SELECT * FROM attendance WHERE user_id = ? AND att_date = ?", 'is', [$uid, $date]);
$back  = 'attendance-manage.php?view=user&user=' . $uid . '&month=' . substr($date, 0, 7);
$errors = [];
$in = $rec ?? ['status' => 'present', 'check_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'check_out' => null, 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $me = currentUserId();
    $act = (string)($_POST['action'] ?? '');
    if ($act === 'break_add' || $act === 'break_delete') {            // exits during the day ("marked out for urgent work")
        $self = 'attendance-edit.php?user=' . $uid . '&date=' . $date;
        if ($act === 'break_delete') {
            $bid = (int)($_POST['break_id'] ?? 0);
            $st = $conn->prepare("DELETE FROM attendance_breaks WHERE id = ? AND user_id = ? AND att_date = ?"); $st->bind_param('iis', $bid, $uid, $date); $st->execute();
            flashMessage('success', 'Exit removed.');
        } else {
            $out = attTimeOrNull($_POST['out_at'] ?? ''); $inn = trim((string)($_POST['in_at'] ?? '')) === '' ? null : attTimeOrNull($_POST['in_at']);
            $kind = ($_POST['kind'] ?? '') === 'office' ? 'office' : 'personal'; $reason = mb_substr(plainText((string)($_POST['reason'] ?? '')), 0, 255);
            if (!$out) flashMessage('error', 'Enter a valid "out" time.');
            elseif (trim((string)($_POST['in_at'] ?? '')) !== '' && !$inn) flashMessage('error', 'The "back" time is not valid.');
            elseif ($inn && attMin($inn) <= attMin($out)) flashMessage('error', '"Back" time must be after "out" time.');
            elseif (mb_strlen($reason) < 3) flashMessage('error', 'Write a short reason.');
            else {
                $st = $conn->prepare("INSERT INTO attendance_breaks (user_id, att_date, out_at, in_at, kind, reason, created_by_id) VALUES (?,?,?,?,?,?,?)");
                $st->bind_param('isssssi', $uid, $date, $out, $inn, $kind, $reason, $me); $st->execute();
                flashMessage('success', 'Exit added.');
            }
        }
        header('Location: ' . $self); exit;
    }
    if ($act === 'clear') {                       // back to automatic (absent / off / holiday / leave worked out by the system)
        $st = $conn->prepare("DELETE FROM attendance WHERE user_id = ? AND att_date = ?"); $st->bind_param('is', $uid, $date); $st->execute();
        flashMessage('success', 'Record removed — the day is now worked out automatically.');
        header('Location: ' . $back); exit;
    }
    $status = (string)($_POST['status'] ?? '');
    if (!isset(ATT_STATUS[$status])) $errors[] = 'Choose a status.';
    $t = [];
    foreach (['check_in', 'lunch_out', 'lunch_in', 'check_out'] as $f) {
        $raw = trim((string)($_POST[$f] ?? ''));
        $t[$f] = $raw === '' ? null : attTimeOrNull($raw);
        if ($raw !== '' && $t[$f] === null) $errors[] = 'Time "' . $raw . '" is not valid (use HH:MM).';
    }
    $note = mb_substr(plainText((string)($_POST['note'] ?? '')), 0, 255);
    if (!$errors) {
        $m = fn($k) => attMin($t[$k]);
        if (in_array($status, ['present', 'half_day'], true) && $t['check_in'] === null) $errors[] = 'Check-in time is needed for present / half day.';
        if ($t['check_out'] !== null && $t['check_in'] !== null && $m('check_out') <= $m('check_in')) $errors[] = 'Check-out must be after check-in.';
        if (($t['lunch_out'] === null) !== ($t['lunch_in'] === null)) $errors[] = 'Enter both lunch times or neither.';
        if ($t['lunch_out'] !== null && $m('lunch_in') <= $m('lunch_out')) $errors[] = 'Lunch end must be after lunch start.';
        if (in_array($status, ['absent', 'leave'], true)) $t = array_fill_keys(array_keys($t), null);    // no times on an absent / leave day
    }
    $in = array_merge($in, $t, ['status' => $status, 'note' => $note]);
    if (!$errors) {
        $st = $conn->prepare("INSERT INTO attendance (user_id, att_date, status, check_in, lunch_out, lunch_in, check_out, note, marked_by_id) VALUES (?,?,?,?,?,?,?,?,?)
                              ON DUPLICATE KEY UPDATE status=VALUES(status), check_in=VALUES(check_in), lunch_out=VALUES(lunch_out), lunch_in=VALUES(lunch_in), check_out=VALUES(check_out), note=VALUES(note), marked_by_id=VALUES(marked_by_id)");
        $st->bind_param('isssssssi', $uid, $date, $status, $t['check_in'], $t['lunch_out'], $t['lunch_in'], $t['check_out'], $note, $me);
        $st->execute();
        flashMessage('success', 'Attendance saved for ' . $target['name'] . ' — ' . date('j M Y', strtotime($date)) . '.');
        header('Location: ' . $back); exit;
    }
}

$pageTitle = 'Edit attendance';
$val = fn($k) => clean(substr((string)($in[$k] ?? ''), 0, 5));
include __DIR__ . '/../includes/admin-header.php';
?>
<?php foreach ($errors as $err): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($err); ?></div><?php endforeach; ?>
<div class="card">
    <div class="card-header"><h2><?php echo clean($target['name']); ?> — <?php echo clean(date('l, j F Y', strtotime($date))); ?></h2><a href="<?php echo clean($back); ?>" class="abtn abtn-outline abtn-sm">← Back</a></div>
    <div class="card-body">
        <p class="td-muted">Timing: <?php echo attHm($sched['work_start']); ?>–<?php echo attHm($sched['work_end']); ?>, lunch <?php echo attHm($sched['lunch_start']); ?>–<?php echo attHm($sched['lunch_end']); ?>.
            <?php if ($rec && $rec['marked_by_id']): $mb = dbRow("SELECT name FROM admin_users WHERE id = ?", 'i', [$rec['marked_by_id']]); ?> Last changed by <?php echo clean($mb['name'] ?? '—'); ?>.<?php endif; ?></p>
        <form method="POST"><?php echo csrfField(); ?><input type="hidden" name="user" value="<?php echo $uid; ?>"><input type="hidden" name="date" value="<?php echo clean($date); ?>">
            <div class="form-grid">
                <div class="form-group"><label>Status *</label>
                    <select name="status"><?php foreach (ATT_STATUS as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($in['status'] ?? '') === $k ? 'selected' : ''; ?>><?php echo clean($l[0]); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Note</label><input type="text" name="note" maxlength="255" value="<?php echo clean((string)($in['note'] ?? '')); ?>" placeholder="e.g. forgot to check out, client visit"></div>
                <div class="form-group"><label>Check in</label><input type="time" name="check_in" value="<?php echo $val('check_in'); ?>"></div>
                <div class="form-group"><label>Check out</label><input type="time" name="check_out" value="<?php echo $val('check_out'); ?>"></div>
                <div class="form-group"><label>Lunch start</label><input type="time" name="lunch_out" value="<?php echo $val('lunch_out'); ?>"></div>
                <div class="form-group"><label>Lunch end</label><input type="time" name="lunch_in" value="<?php echo $val('lunch_in'); ?>"></div>
            </div>
            <p class="hint" style="margin-top:12px;">Absent / Leave days keep no times. If lunch times are empty, the planned lunch is subtracted from worked hours.</p>
            <div class="form-actions" style="margin-top:18px;"><button class="abtn abtn-primary" type="submit">Save</button><a class="abtn abtn-outline" href="<?php echo clean($back); ?>">Cancel</a></div>
        </form>
        <?php if ($rec): ?>
        <div style="margin-top:22px;border-top:1px solid var(--a-border);padding-top:16px;">
            <?php echo postButton('attendance-edit.php?user=' . $uid . '&date=' . $date, ['action' => 'clear', 'user' => $uid, 'date' => $date], 'Remove this record (back to automatic)', 'abtn abtn-danger abtn-sm', 'Remove the saved record for this day?'); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $brk = attBreakMap($uid, $date, $date)[$date] ?? ['rows' => [], 'personal' => 0, 'office' => 0, 'count' => 0, 'open' => false]; ?>
<div class="card" style="margin-top:24px;">
    <div class="card-header"><h2>Exits during the day (urgent work)</h2><span class="td-muted"><?php echo clean(attBreakText($brk['count'] ? $brk : null)); ?></span></div>
    <div class="card-body">
        <p class="hint" style="margin:0 0 12px;">Personal = time is deducted from worked hours. Office work = counted as worked. Staff mark these themselves from My Attendance; use this to add a forgotten one or fix a mistake.</p>
        <?php if ($brk['rows']): ?>
        <div style="overflow-x:auto;"><table class="admin-table table-cards"><thead><tr><th>Out</th><th>Back</th><th>Time</th><th>Type</th><th>Reason</th><th></th></tr></thead><tbody>
        <?php foreach ($brk['rows'] as $b): ?>
            <tr><td data-label="Out"><?php echo attHm($b['out_at']); ?></td><td data-label="Back"><?php echo $b['in_at'] ? attHm($b['in_at']) : '<span class="abadge abadge-orange">still out</span>'; ?></td>
                <td data-label="Time"><?php echo $b['in_at'] ? attDur(attMin($b['in_at']) - attMin($b['out_at'])) : '—'; ?></td>
                <td data-label="Type"><span class="abadge <?php echo $b['kind'] === 'office' ? 'abadge-blue' : 'abadge-grey'; ?>"><?php echo $b['kind'] === 'office' ? 'Office work' : 'Personal'; ?></span></td>
                <td data-label="Reason" class="td-muted"><?php echo clean((string)$b['reason']); ?></td>
                <td data-label=""><?php echo postButton('attendance-edit.php?user=' . $uid . '&date=' . $date, ['action' => 'break_delete', 'break_id' => $b['id'], 'user' => $uid, 'date' => $date], 'Delete', 'abtn abtn-danger abtn-sm', 'Delete this exit?'); ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
        <form method="POST" style="margin-top:16px;"><?php echo csrfField(); ?><input type="hidden" name="action" value="break_add"><input type="hidden" name="user" value="<?php echo $uid; ?>"><input type="hidden" name="date" value="<?php echo clean($date); ?>">
            <div class="form-grid">
                <div class="form-group"><label>Out at</label><input type="time" name="out_at" required></div>
                <div class="form-group"><label>Back at (empty = still out)</label><input type="time" name="in_at"></div>
                <div class="form-group"><label>Type</label><select name="kind"><?php foreach (BREAK_KINDS as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo clean($l); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Reason</label><input type="text" name="reason" maxlength="255" required placeholder="e.g. bank work"></div>
            </div>
            <div class="form-actions" style="margin-top:12px;"><button class="abtn abtn-outline" type="submit">Add exit</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
