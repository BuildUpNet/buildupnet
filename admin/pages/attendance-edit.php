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
    if (($_POST['action'] ?? '') === 'clear') {                       // back to automatic (absent / off / holiday / leave worked out by the system)
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
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
