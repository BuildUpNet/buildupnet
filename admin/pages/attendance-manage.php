<?php
// Team attendance for owner & manager: daily board, month report (all staff) and one person's month.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('attendance.manage');

$view  = (string)($_GET['view'] ?? 'day');
if (!in_array($view, ['day', 'month', 'user'], true)) $view = 'day';
$staff = attStaff(true);
$today = date('Y-m-d');

// ================= DAILY BOARD =================
if ($view === 'day') {
    $date = attDateOrNull($_GET['date'] ?? '') ?? $today;
    if ($date > $today) $date = $today;
    $ym   = substr($date, 0, 7);
    $hol  = attHolidays($ym . '-01', date('Y-m-t', strtotime($ym . '-01')));
    $rows = []; $cnt = ['P' => 0, 'H' => 0, 'A' => 0, 'L' => 0, 'late' => 0, 'off' => 0, 'out' => 0];
    foreach ($staff as $u) {
        $sched = attSchedule((int)$u['id']);
        $d = attMonth($u, $ym, $sched, $hol)[$date];
        $rows[] = ['u' => $u, 'd' => $d, 's' => $sched];
        if (isset($cnt[$d['code']])) $cnt[$d['code']]++; elseif (in_array($d['code'], ['W', 'HD'], true)) $cnt['off']++;
        if ($d['late'] > 0) $cnt['late']++;
        if (($d['br']['open'] ?? false) && $d['rec'] && !$d['rec']['check_out'] && $date === $today) $cnt['out']++;
    }
    $prev = date('Y-m-d', strtotime($date . ' -1 day')); $next = date('Y-m-d', strtotime($date . ' +1 day'));
}

// ================= MONTH (all staff) / ONE PERSON =================
if ($view === 'month' || $view === 'user') {
    $ym   = attMonthParam($_GET['month'] ?? '');
    $first = $ym . '-01'; $last = date('Y-m-t', strtotime($first)); $dim = (int)date('t', strtotime($first));
    $hol  = attHolidays($first, $last);
    $prev = date('Y-m', strtotime($first . ' -1 month')); $next = date('Y-m', strtotime($first . ' +1 month'));
}
if ($view === 'month') {
    $matrix = [];
    foreach ($staff as $u) { $days = attMonth($u, $ym, attSchedule((int)$u['id']), $hol); $matrix[] = ['u' => $u, 'days' => $days, 't' => attTotals($days)]; }

    if (($_GET['export'] ?? '') === 'csv') {          // read-only download of what is on screen
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="attendance-' . $ym . '.csv"');
        $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_merge(['Name', 'Role'], range(1, $dim), ['Present', 'Half day', 'Absent', 'Leave', 'Late days', 'Worked (h)', 'Personal out (h)']));
        foreach ($matrix as $m) {
            $codes = array_map(fn($d) => $d['code'] === '…' ? '' : $d['code'], array_values($m['days']));
            fputcsv($out, array_merge([$m['u']['name'], ROLE_LABELS[$m['u']['role']] ?? ''], $codes, [$m['t']['P'], $m['t']['H'], $m['t']['A'], $m['t']['L'], $m['t']['late'], round($m['t']['worked'] / 60, 1), round($m['t']['break'] / 60, 1)]));
        }
        exit;
    }
}
if ($view === 'user') {
    $uid = (int)($_GET['user'] ?? 0);
    $target = $uid ? dbRow("SELECT id, name, role, created_at FROM admin_users WHERE id = ?", 'i', [$uid]) : null;
    if (!$target) { flashMessage('error', 'Person not found.'); header('Location: attendance-manage.php?view=month'); exit; }
    $sched  = attSchedule($uid);
    $days   = attMonth($target, $ym, $sched, $hol);
    $totals = attTotals($days);
}

$pageTitle = 'Team Attendance';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<div data-region="board"<?php echo ($view === 'day' && $date === $today) ? ' data-refresh="30"' : ''; ?>>
<?php echo attTabs($view === 'user' ? 'month' : $view); ?>

<?php if ($view === 'day'): ?>
<div class="stat-cards compact">
    <div class="stat-card"><div><div class="stat-card-num"><?php echo $cnt['P'] + $cnt['H']; ?></div><div class="stat-card-label">Present</div></div></div>
    <div class="stat-card"><div><div class="stat-card-num"><?php echo $cnt['A']; ?></div><div class="stat-card-label">Absent</div></div></div>
    <div class="stat-card"><div><div class="stat-card-num"><?php echo $cnt['L']; ?></div><div class="stat-card-label">On leave</div></div></div>
    <div class="stat-card"><div><div class="stat-card-num"><?php echo $cnt['late']; ?></div><div class="stat-card-label">Late</div></div></div>
    <?php if ($date === $today): ?><div class="stat-card"><div><div class="stat-card-num"><?php echo $cnt['out']; ?></div><div class="stat-card-label">Out right now</div></div></div><?php endif; ?>
</div>
<div class="card">
    <div class="card-header">
        <h2><?php echo clean(date('l, j F Y', strtotime($date))); ?></h2>
        <form method="GET" class="btn-group" style="align-items:center;"><input type="hidden" name="view" value="day">
            <a class="abtn abtn-outline abtn-sm" data-nav href="?view=day&date=<?php echo $prev; ?>">‹</a>
            <input type="date" name="date" value="<?php echo $date; ?>" max="<?php echo $today; ?>" data-navform style="min-height:40px;border:1px solid var(--a-border);border-radius:8px;padding:0 10px;">
            <?php if ($date < $today): ?><a class="abtn abtn-outline abtn-sm" data-nav href="?view=day&date=<?php echo $next; ?>">›</a><?php endif; ?>
        </form>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr><th>Person</th><th>Timing</th><th>Status</th><th>In</th><th>Lunch</th><th>Out</th><th>Went out</th><th>Worked</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $u = $r['u']; $d = $r['d']; $rec = $d['rec']; ?>
                <tr>
                    <td data-label="Person" class="td-title"><a data-nav href="?view=user&user=<?php echo (int)$u['id']; ?>&month=<?php echo substr($date, 0, 7); ?>"><?php echo clean($u['name']); ?></a><div class="td-muted"><?php echo clean(ROLE_LABELS[$u['role']] ?? $u['role']); ?></div></td>
                    <td data-label="Timing" class="td-muted"><?php echo attHm($r['s']['work_start']); ?>–<?php echo attHm($r['s']['work_end']); ?></td>
                    <td data-label="Status"><span class="abadge <?php echo $d['class']; ?>"><?php echo clean($d['label']); ?></span><?php echo $d['late'] ? ' <span class="td-muted">' . $d['late'] . 'm late</span>' : ''; ?><?php echo $d['holiday'] ? ' <span class="td-muted">' . clean($d['holiday']) . '</span>' : ''; ?><?php echo attLiveChip($d, $today); ?></td>
                    <td data-label="In"><?php echo attHm($rec['check_in'] ?? null); ?></td>
                    <td data-label="Lunch"><?php echo attHm($rec['lunch_out'] ?? null); ?> – <?php echo attHm($rec['lunch_in'] ?? null); ?></td>
                    <td data-label="Out"><?php echo attHm($rec['check_out'] ?? null); ?></td>
                    <td data-label="Went out" class="td-muted"><?php echo clean(attBreakText($d['br'])); ?></td>
                    <td data-label="Worked"><?php echo attDur($d['worked']); ?></td>
                    <td data-label=""><?php if (attCanManage($u['role'])): ?><a class="abtn abtn-outline abtn-sm" href="attendance-edit.php?user=<?php echo (int)$u['id']; ?>&date=<?php echo $date; ?>">Edit / Mark</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($view === 'month'): ?>
<div class="card">
    <div class="card-header">
        <h2><?php echo clean(date('F Y', strtotime($first))); ?> — all staff</h2>
        <div class="btn-group"><a data-nav class="abtn abtn-outline abtn-sm" href="?view=month&month=<?php echo $prev; ?>">‹ Prev</a><a data-nav class="abtn abtn-outline abtn-sm" href="?view=month&month=<?php echo date('Y-m'); ?>">This month</a><a data-nav class="abtn abtn-outline abtn-sm" href="?view=month&month=<?php echo $next; ?>">Next ›</a>
        <a class="abtn abtn-primary abtn-sm" href="?view=month&month=<?php echo $ym; ?>&export=csv">Download CSV</a></div>
    </div>
    <div class="att-legend"><span class="c-P">P</span> Present <span class="c-H">H</span> Half day <span class="c-A">A</span> Absent <span class="c-L">L</span> Leave <span class="c-W">W</span> Weekly off <span class="c-HD">HD</span> Holiday</div>
    <div class="att-scroll">
        <table class="att-matrix">
            <thead><tr><th class="sticky">Person</th><th>P</th><th>H</th><th>A</th><th>L</th><th>Late</th><th>Hrs</th><?php for ($i = 1; $i <= $dim; $i++): ?><th><?php echo $i; ?></th><?php endfor; ?></tr></thead>
            <tbody>
            <?php foreach ($matrix as $m): ?>
                <tr>
                    <td class="sticky"><a data-nav href="?view=user&user=<?php echo (int)$m['u']['id']; ?>&month=<?php echo $ym; ?>"><?php echo clean($m['u']['name']); ?></a></td>
                    <td><b><?php echo $m['t']['P']; ?></b></td><td><?php echo $m['t']['H']; ?></td><td><?php echo $m['t']['A']; ?></td><td><?php echo $m['t']['L']; ?></td><td><?php echo $m['t']['late']; ?></td><td><?php echo round($m['t']['worked'] / 60, 1); ?></td>
                    <?php foreach ($m['days'] as $d): ?><td class="c-<?php echo clean($d['code'] === '…' ? '' : $d['code']); ?>" title="<?php echo clean($d['label'] . ($d['holiday'] ? ' — ' . $d['holiday'] : '')); ?>"><?php echo clean($d['code'] === '…' ? '' : $d['code']); ?></td><?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($view === 'user'): ?>
<div class="card">
    <div class="card-header">
        <h2><?php echo clean($target['name']); ?> — <?php echo clean(date('F Y', strtotime($first))); ?></h2>
        <div class="btn-group"><a data-nav class="abtn abtn-outline abtn-sm" href="?view=month&month=<?php echo $ym; ?>">← All staff</a><a data-nav class="abtn abtn-outline abtn-sm" href="?view=user&user=<?php echo $uid; ?>&month=<?php echo $prev; ?>">‹ Prev</a><a data-nav class="abtn abtn-outline abtn-sm" href="?view=user&user=<?php echo $uid; ?>&month=<?php echo $next; ?>">Next ›</a></div>
    </div>
    <p class="td-muted" style="padding:0 20px;">Timing <?php echo attHm($sched['work_start']); ?>–<?php echo attHm($sched['work_end']); ?>, lunch <?php echo attHm($sched['lunch_start']); ?>–<?php echo attHm($sched['lunch_end']); ?>, weekly off <?php echo clean(implode(', ', array_map(fn($n) => WEEKDAYS[(int)$n] ?? '', array_filter(explode(',', $sched['weekly_off']), 'strlen')))) ?: 'none'; ?></p>
    <div class="att-totals">
        <span><b><?php echo $totals['P']; ?></b> Present</span><span><b><?php echo $totals['H']; ?></b> Half day</span><span><b><?php echo $totals['A']; ?></b> Absent</span>
        <span><b><?php echo $totals['L']; ?></b> Leave</span><span><b><?php echo $totals['late']; ?></b> Late</span><span><b><?php echo attDur($totals['worked']); ?></b> Worked</span><span><b><?php echo attDur($totals['break']); ?></b> Personal out</span><span><b><?php echo attDur($totals['office']); ?></b> Office work out</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr><th>Date</th><th>Status</th><th>In</th><th>Lunch</th><th>Out</th><th>Went out</th><th>Worked</th><th>Note</th><th></th></tr></thead>
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
                    <td data-label=""><?php if (attCanManage($target['role']) && $d['date'] <= $today): ?><a class="abtn abtn-outline abtn-sm" href="attendance-edit.php?user=<?php echo $uid; ?>&date=<?php echo $d['date']; ?>">Edit</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
