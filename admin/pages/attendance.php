<?php
// My attendance: check in / lunch / check out for today + my month. Everyone (owner, manager, developer) has one.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/attendance.php';
requireLogin();
requirePermission('attendance.self');

$uid   = currentUserId();
$me    = dbRow("SELECT id, name, created_at FROM admin_users WHERE id = ?", 'i', [$uid]);
$sched = attSchedule($uid);
$today = date('Y-m-d');

// ---- punch (always for the logged-in person only; the time is the server's clock, never the browser's) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $act = (string)($_POST['action'] ?? '');
    $now = date('H:i:s');
    $cur = attToday($uid); $rec = $cur['rec']; $state = $cur['state'];
    $msg = null; $err = null;

    if ($act === 'check_in') {
        if ($rec && $rec['check_in']) $err = 'You have already checked in today.';
        elseif ($rec && $rec['status'] === 'leave') $err = 'You are marked on leave today. Ask your manager to correct it first.';
        else {
            $st = $conn->prepare("INSERT INTO attendance (user_id, att_date, status, check_in, marked_by_id) VALUES (?, ?, 'present', ?, ?)
                                  ON DUPLICATE KEY UPDATE status = 'present', check_in = VALUES(check_in), marked_by_id = VALUES(marked_by_id)");
            $st->bind_param('issi', $uid, $today, $now, $uid); $st->execute();
            $late = attLate(['check_in' => $now], $sched);
            $msg = 'Checked in at ' . substr($now, 0, 5) . ($late ? " — you are $late minutes late." : '. Have a good day!');
        }
    } elseif ($state === 'in') {
        $err = 'Please check in first.';
    } elseif ($state === 'done') {
        $err = 'You have already checked out today.';
    } elseif ($act === 'lunch_out') {
        if ($state === 'lunch') $err = 'Lunch already started.'; elseif ($state === 'break') $err = 'You are out right now — tap "I\'m back" first.';
        elseif ($rec['lunch_out']) $err = 'Lunch was already taken today.';
        else { $st = $conn->prepare("UPDATE attendance SET lunch_out = ? WHERE id = ?"); $st->bind_param('si', $now, $rec['id']); $st->execute(); $msg = 'Lunch started at ' . substr($now, 0, 5) . '.'; }
    } elseif ($act === 'lunch_in') {
        if ($state !== 'lunch') $err = 'Start lunch first.';
        else { $st = $conn->prepare("UPDATE attendance SET lunch_in = ? WHERE id = ?"); $st->bind_param('si', $now, $rec['id']); $st->execute(); $msg = 'Back from lunch at ' . substr($now, 0, 5) . '.'; }
    } elseif ($act === 'break_out') {
        $kind = ($_POST['kind'] ?? '') === 'office' ? 'office' : 'personal';
        $reason = mb_substr(plainText((string)($_POST['reason'] ?? '')), 0, 255);
        if ($state === 'lunch') $err = 'You are on lunch — tap "Back from lunch" first.'; elseif ($state === 'break') $err = 'You are already marked out.';
        elseif (mb_strlen($reason) < 3) $err = 'Please write a short reason (for example: bank work, client visit).';
        else {
            $st = $conn->prepare("INSERT INTO attendance_breaks (user_id, att_date, out_at, kind, reason, created_by_id) VALUES (?,?,?,?,?,?)");
            $st->bind_param('issssi', $uid, $today, $now, $kind, $reason, $uid); $st->execute();
            $msg = 'Marked out at ' . substr($now, 0, 5) . ($kind === 'personal' ? ' — this time will be deducted from your working hours.' : ' — counted as office work.');
        }
    } elseif ($act === 'break_in') {
        if ($state !== 'break' || !$cur['open']) $err = 'You are not marked out.';
        else {
            $st = $conn->prepare("UPDATE attendance_breaks SET in_at = ? WHERE id = ? AND user_id = ?"); $st->bind_param('sii', $now, $cur['open']['id'], $uid); $st->execute();
            $m = attMin($now) - attMin($cur['open']['out_at']);
            $msg = 'Welcome back! You were out for ' . attDur($m) . ($cur['open']['kind'] === 'personal' ? ' (deducted from working hours).' : ' (counted as office work).');
        }
    } elseif ($act === 'check_out') {
        if ($state === 'lunch') $err = 'You are still on lunch — tap "Back from lunch" first.';
        elseif ($state === 'break') $err = 'You are marked out — tap "I\'m back" first.';
        else {
            $full = array_merge($rec, ['check_out' => $now]);
            $worked = attWorked($full, $sched);
            // Less than half of the planned hours = half day (a manager can change it)
            $status = ($worked !== null && $worked < attPlanned($sched) / 2) ? 'half_day' : 'present';
            $st = $conn->prepare("UPDATE attendance SET check_out = ?, status = ? WHERE id = ?"); $st->bind_param('ssi', $now, $status, $rec['id']); $st->execute();
            $msg = 'Checked out at ' . substr($now, 0, 5) . '. Worked ' . attDur($worked) . ($status === 'half_day' ? ' (counted as half day).' : '.');
        }
    } else $err = 'Unknown action.';

    if (isAjax()) {                                    // page JavaScript: answer in JSON, the page then refreshes only its own blocks
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => !$err, 'message' => $err ?? $msg]); exit;
    }
    flashMessage($err ? 'error' : 'success', $err ?? $msg);
    header('Location: attendance.php'); exit;
}

$cur    = attToday($uid); $rec = $cur['rec']; $state = $cur['state']; $open = $cur['open']; $todayBreaks = $cur['breaks'];
$ym     = attMonthParam($_GET['month'] ?? '');
$days   = attMonth($me, $ym, $sched);
$totals = attTotals($days);
$prev   = date('Y-m', strtotime($ym . '-01 -1 month')); $next = date('Y-m', strtotime($ym . '-01 +1 month'));
$onLeave = $rec && $rec['status'] === 'leave';
$nowSecs = (int)date('G') * 3600 + (int)date('i') * 60 + (int)date('s');

$pageTitle = 'My Attendance';
include __DIR__ . '/../includes/admin-header.php';
$ajaxBtn = fn($act, $label, $cls) => postButton('attendance.php', ['action' => $act], $label, $cls, '', '', true);
?>
<?php renderFlash(); ?>

<div data-region="today">
<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>Today — <?php echo clean(date('l, j F Y')); ?></h2><span class="td-muted" id="liveClock" data-secs="<?php echo $nowSecs; ?>"><?php echo date('H:i'); ?></span></div>
    <div class="card-body">
        <p class="td-muted" style="margin:0 0 14px;">Your timing: <b><?php echo attHm($sched['work_start']); ?> – <?php echo attHm($sched['work_end']); ?></b> · Lunch <b><?php echo attHm($sched['lunch_start']); ?> – <?php echo attHm($sched['lunch_end']); ?></b> · Grace <?php echo (int)$sched['grace_minutes']; ?> min</p>

        <?php if ($state === 'break' && $open): ?>
            <div class="out-banner <?php echo $open['kind']; ?>">
                <div><b>You are marked OUT</b> since <?php echo attHm($open['out_at']); ?> — <span class="timer" data-elapsed="<?php echo max(0, $nowSecs - attMin($open['out_at']) * 60); ?>">…</span>
                    <div class="td-muted"><?php echo clean((string)$open['reason']); ?> · <?php echo $open['kind'] === 'personal' ? 'time is deducted from working hours' : 'counted as office work'; ?></div></div>
            </div>
        <?php elseif ($state === 'lunch'): ?>
            <div class="out-banner lunch"><div><b>On lunch</b> since <?php echo attHm($rec['lunch_out']); ?> — <span class="timer" data-elapsed="<?php echo max(0, $nowSecs - attMin($rec['lunch_out']) * 60); ?>">…</span></div></div>
        <?php endif; ?>

        <div class="punch-row">
            <?php if ($onLeave): ?>
                <span class="abadge abadge-blue">You are on leave today</span>
            <?php else: ?>
                <?php echo $state === 'in'      ? $ajaxBtn('check_in', 'Check in', 'abtn abtn-primary punch') : ''; ?>
                <?php echo $state === 'working' ? $ajaxBtn('lunch_out', 'Start lunch', 'abtn abtn-blue punch') . $ajaxBtn('check_out', 'Check out', 'abtn abtn-primary punch') : ''; ?>
                <?php echo $state === 'lunch'   ? $ajaxBtn('lunch_in', 'Back from lunch', 'abtn abtn-blue punch') : ''; ?>
                <?php echo $state === 'break'   ? $ajaxBtn('break_in', "I'm back", 'abtn abtn-primary punch') : ''; ?>
                <?php if ($state === 'done'): ?><span class="abadge abadge-green">Day complete — worked <?php echo attDur(attWorked($rec, $sched)); ?></span><?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($state === 'working'): ?>
        <form method="POST" action="attendance.php" data-ajax class="out-form" id="out"><?php echo csrfField(); ?><input type="hidden" name="action" value="break_out">
            <strong>Going out for a while? (urgent work, bank, family…)</strong>
            <div class="out-grid">
                <select name="kind"><?php foreach (BREAK_KINDS as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo clean($l); ?></option><?php endforeach; ?></select>
                <input type="text" name="reason" maxlength="255" placeholder="Reason — e.g. bank work, client meeting" required>
                <button class="abtn abtn-outline" type="submit">Mark me out</button>
            </div>
        </form>
        <?php endif; ?>

        <dl class="facts" style="margin-top:18px;">
            <div><dt>Check in</dt><dd><?php echo attHm($rec['check_in'] ?? null); ?><?php $l = $rec ? attLate($rec, $sched) : 0; echo $l ? ' <span class="abadge abadge-red">' . $l . ' min late</span>' : ''; ?></dd></div>
            <div><dt>Lunch</dt><dd><?php echo attHm($rec['lunch_out'] ?? null); ?> – <?php echo attHm($rec['lunch_in'] ?? null); ?></dd></div>
            <div><dt>Out today</dt><dd><?php echo clean(attBreakText($todayBreaks['count'] ? $todayBreaks : null)); ?></dd></div>
            <div><dt>Check out</dt><dd><?php echo attHm($rec['check_out'] ?? null); ?></dd></div>
            <div><dt>Status</dt><dd><?php echo $rec ? clean(ATT_STATUS[$rec['status']][0] ?? $rec['status']) : 'Not checked in'; ?></dd></div>
        </dl>

        <?php if ($todayBreaks['count']): ?>
        <div style="overflow-x:auto;margin-top:12px;"><table class="admin-table table-cards"><thead><tr><th>Out</th><th>Back</th><th>Time</th><th>Type</th><th>Reason</th></tr></thead><tbody>
        <?php foreach ($todayBreaks['rows'] as $b): ?>
            <tr><td data-label="Out"><?php echo attHm($b['out_at']); ?></td><td data-label="Back"><?php echo $b['in_at'] ? attHm($b['in_at']) : '<span class="abadge abadge-orange">still out</span>'; ?></td>
                <td data-label="Time"><?php echo $b['in_at'] ? attDur(attMin($b['in_at']) - attMin($b['out_at'])) : '—'; ?></td>
                <td data-label="Type"><span class="abadge <?php echo $b['kind'] === 'office' ? 'abadge-blue' : 'abadge-grey'; ?>"><?php echo $b['kind'] === 'office' ? 'Office work' : 'Personal'; ?></span></td>
                <td data-label="Reason" class="td-muted"><?php echo clean((string)$b['reason']); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </div>
</div>
</div>

<div data-region="month">
<div class="card">
    <div class="card-header">
        <h2><?php echo clean(date('F Y', strtotime($ym . '-01'))); ?></h2>
        <div class="btn-group"><a class="abtn abtn-outline abtn-sm" data-nav href="?month=<?php echo $prev; ?>">‹ Prev</a><a class="abtn abtn-outline abtn-sm" data-nav href="?month=<?php echo date('Y-m'); ?>">This month</a><a class="abtn abtn-outline abtn-sm" data-nav href="?month=<?php echo $next; ?>">Next ›</a></div>
    </div>
    <div class="att-totals">
        <span><b><?php echo $totals['P']; ?></b> Present</span><span><b><?php echo $totals['H']; ?></b> Half day</span><span><b><?php echo $totals['A']; ?></b> Absent</span>
        <span><b><?php echo $totals['L']; ?></b> Leave</span><span><b><?php echo $totals['late']; ?></b> Late</span><span><b><?php echo attDur($totals['worked']); ?></b> Worked</span>
        <span><b><?php echo attDur($totals['break']); ?></b> Personal out</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr><th>Date</th><th>Status</th><th>In</th><th>Lunch</th><th>Out</th><th>Went out</th><th>Worked</th><th>Note</th></tr></thead>
            <tbody>
            <?php foreach (array_reverse($days) as $d): if ($d['code'] === '') continue; $r = $d['rec']; ?>
                <tr>
                    <td data-label="Date" class="td-title"><?php echo clean(date('D, j M', strtotime($d['date']))); ?></td>
                    <td data-label="Status"><span class="abadge <?php echo $d['class']; ?>"><?php echo clean($d['label']); ?></span><?php echo $d['late'] ? ' <span class="td-muted">' . $d['late'] . 'm late</span>' : ''; ?></td>
                    <td data-label="In"><?php echo attHm($r['check_in'] ?? null); ?></td>
                    <td data-label="Lunch"><?php echo attHm($r['lunch_out'] ?? null); ?> – <?php echo attHm($r['lunch_in'] ?? null); ?></td>
                    <td data-label="Out"><?php echo attHm($r['check_out'] ?? null); ?></td>
                    <td data-label="Went out" class="td-muted"><?php echo clean(attBreakText($d['br'])); ?></td>
                    <td data-label="Worked"><?php echo attDur($d['worked']); ?></td>
                    <td data-label="Note" class="td-muted"><?php echo clean((string)($r['note'] ?? ($d['holiday'] ?? ''))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
