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
    $rec = dbRow("SELECT * FROM attendance WHERE user_id = ? AND att_date = ?", 'is', [$uid, $today]);
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
    } elseif (!$rec || !$rec['check_in']) {
        $err = 'Please check in first.';
    } elseif ($act === 'lunch_out') {
        if ($rec['lunch_out']) $err = 'Lunch already started.'; elseif ($rec['check_out']) $err = 'You have already checked out.';
        else { $st = $conn->prepare("UPDATE attendance SET lunch_out = ? WHERE id = ?"); $st->bind_param('si', $now, $rec['id']); $st->execute(); $msg = 'Lunch started at ' . substr($now, 0, 5) . '.'; }
    } elseif ($act === 'lunch_in') {
        if (!$rec['lunch_out']) $err = 'Start lunch first.'; elseif ($rec['lunch_in']) $err = 'Lunch already ended.';
        else { $st = $conn->prepare("UPDATE attendance SET lunch_in = ? WHERE id = ?"); $st->bind_param('si', $now, $rec['id']); $st->execute(); $msg = 'Back from lunch at ' . substr($now, 0, 5) . '.'; }
    } elseif ($act === 'check_out') {
        if ($rec['check_out']) $err = 'You have already checked out.';
        elseif ($rec['lunch_out'] && !$rec['lunch_in']) $err = 'You are still on lunch — tap "Back from lunch" first.';
        else {
            $full = array_merge($rec, ['check_out' => $now]);
            $worked = attWorked($full, $sched);
            // Less than half of the planned hours = half day (a manager can change it)
            $status = ($worked !== null && $worked < attPlanned($sched) / 2) ? 'half_day' : 'present';
            $st = $conn->prepare("UPDATE attendance SET check_out = ?, status = ? WHERE id = ?"); $st->bind_param('ssi', $now, $status, $rec['id']); $st->execute();
            $msg = 'Checked out at ' . substr($now, 0, 5) . '. Worked ' . attDur($worked) . ($status === 'half_day' ? ' (counted as half day).' : '.');
        }
    } else $err = 'Unknown action.';

    flashMessage($err ? 'error' : 'success', $err ?? $msg);
    header('Location: attendance.php'); exit;
}

$rec    = dbRow("SELECT * FROM attendance WHERE user_id = ? AND att_date = ?", 'is', [$uid, $today]);
$ym     = attMonthParam($_GET['month'] ?? '');
$days   = attMonth($me, $ym, $sched);
$totals = attTotals($days);
$prev   = date('Y-m', strtotime($ym . '-01 -1 month')); $next = date('Y-m', strtotime($ym . '-01 +1 month'));
$state  = !$rec || !$rec['check_in'] ? 'in' : ($rec['check_out'] ? 'done' : ($rec['lunch_out'] && !$rec['lunch_in'] ? 'lunch' : 'working'));
$onLeave = $rec && $rec['status'] === 'leave';

$pageTitle = 'My Attendance';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>Today — <?php echo clean(date('l, j F Y')); ?></h2><span class="td-muted" id="liveClock"><?php echo date('H:i'); ?></span></div>
    <div class="card-body">
        <p class="td-muted" style="margin:0 0 14px;">Your timing: <b><?php echo attHm($sched['work_start']); ?> – <?php echo attHm($sched['work_end']); ?></b> · Lunch <b><?php echo attHm($sched['lunch_start']); ?> – <?php echo attHm($sched['lunch_end']); ?></b> · Grace <?php echo (int)$sched['grace_minutes']; ?> min</p>
        <div class="punch-row">
            <?php if ($onLeave): ?>
                <span class="abadge abadge-blue">You are on leave today</span>
            <?php else: ?>
                <?php echo $state === 'in'      ? postButton('attendance.php', ['action' => 'check_in'],  'Check in',  'abtn abtn-primary punch') : ''; ?>
                <?php echo $state === 'working' ? postButton('attendance.php', ['action' => 'lunch_out'], 'Start lunch', 'abtn abtn-blue punch') . postButton('attendance.php', ['action' => 'check_out'], 'Check out', 'abtn abtn-primary punch') : ''; ?>
                <?php echo $state === 'lunch'   ? postButton('attendance.php', ['action' => 'lunch_in'], 'Back from lunch', 'abtn abtn-blue punch') : ''; ?>
                <?php if ($state === 'done'): ?><span class="abadge abadge-green">Day complete — worked <?php echo attDur(attWorked($rec, $sched)); ?></span><?php endif; ?>
            <?php endif; ?>
        </div>
        <dl class="facts" style="margin-top:18px;">
            <div><dt>Check in</dt><dd><?php echo attHm($rec['check_in'] ?? null); ?><?php $l = $rec ? attLate($rec, $sched) : 0; echo $l ? ' <span class="abadge abadge-red">' . $l . ' min late</span>' : ''; ?></dd></div>
            <div><dt>Lunch</dt><dd><?php echo attHm($rec['lunch_out'] ?? null); ?> – <?php echo attHm($rec['lunch_in'] ?? null); ?></dd></div>
            <div><dt>Check out</dt><dd><?php echo attHm($rec['check_out'] ?? null); ?></dd></div>
            <div><dt>Status</dt><dd><?php echo $rec ? clean(ATT_STATUS[$rec['status']][0] ?? $rec['status']) : 'Not checked in'; ?></dd></div>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><?php echo clean(date('F Y', strtotime($ym . '-01'))); ?></h2>
        <div class="btn-group"><a class="abtn abtn-outline abtn-sm" href="?month=<?php echo $prev; ?>">‹ Prev</a><a class="abtn abtn-outline abtn-sm" href="?month=<?php echo date('Y-m'); ?>">This month</a><a class="abtn abtn-outline abtn-sm" href="?month=<?php echo $next; ?>">Next ›</a></div>
    </div>
    <div class="att-totals">
        <span><b><?php echo $totals['P']; ?></b> Present</span><span><b><?php echo $totals['H']; ?></b> Half day</span><span><b><?php echo $totals['A']; ?></b> Absent</span>
        <span><b><?php echo $totals['L']; ?></b> Leave</span><span><b><?php echo $totals['late']; ?></b> Late</span><span><b><?php echo attDur($totals['worked']); ?></b> Worked</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr><th>Date</th><th>Status</th><th>In</th><th>Lunch</th><th>Out</th><th>Worked</th><th>Note</th></tr></thead>
            <tbody>
            <?php foreach (array_reverse($days) as $d): if ($d['code'] === '') continue; $r = $d['rec']; ?>
                <tr>
                    <td data-label="Date" class="td-title"><?php echo clean(date('D, j M', strtotime($d['date']))); ?></td>
                    <td data-label="Status"><span class="abadge <?php echo $d['class']; ?>"><?php echo clean($d['label']); ?></span><?php echo $d['late'] ? ' <span class="td-muted">' . $d['late'] . 'm late</span>' : ''; ?></td>
                    <td data-label="In"><?php echo attHm($r['check_in'] ?? null); ?></td>
                    <td data-label="Lunch"><?php echo attHm($r['lunch_out'] ?? null); ?> – <?php echo attHm($r['lunch_in'] ?? null); ?></td>
                    <td data-label="Out"><?php echo attHm($r['check_out'] ?? null); ?></td>
                    <td data-label="Worked"><?php echo attDur($d['worked']); ?></td>
                    <td data-label="Note" class="td-muted"><?php echo clean((string)($r['note'] ?? ($d['holiday'] ?? ''))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
