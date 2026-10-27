<?php
// ==========================================================
// Attendance helpers (working hours, lunch, late, month summary).
// All timings come from the work_schedules table; holidays from the holidays table.
// ==========================================================

const ATT_STATUS = [          // stored status => [label, short code, badge class]
    'present'  => ['Present',  'P', 'abadge-green'],
    'half_day' => ['Half day', 'H', 'abadge-orange'],
    'absent'   => ['Absent',   'A', 'abadge-red'],
    'leave'    => ['On leave', 'L', 'abadge-blue'],
];
const ATT_DERIVED = [         // shown in reports when there is no row
    'weekoff' => ['Weekly off', 'W', 'abadge-grey'],
    'holiday' => ['Holiday',    'HD', 'abadge-grey'],
    'absent'  => ['Absent',     'A', 'abadge-red'],
];
const LEAVE_TYPES = ['casual' => 'Casual leave', 'sick' => 'Sick leave', 'unpaid' => 'Unpaid leave', 'other' => 'Other'];
const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/** The person's schedule; a default row (defaults live in the table) is created the first time it is needed. */
function attSchedule(int $uid): array {
    global $conn;
    $default = ['user_id' => $uid, 'work_start' => '10:00:00', 'work_end' => '19:00:00', 'lunch_start' => '13:30:00', 'lunch_end' => '14:30:00', 'grace_minutes' => 10, 'weekly_off' => '0'];
    try {
        $row = dbRow("SELECT * FROM work_schedules WHERE user_id = ?", 'i', [$uid]);
        if (!$row) {
            $st = $conn->prepare("INSERT IGNORE INTO work_schedules (user_id) VALUES (?)"); $st->bind_param('i', $uid); $st->execute();
            $row = dbRow("SELECT * FROM work_schedules WHERE user_id = ?", 'i', [$uid]);
        }
        return $row ?: $default;
    } catch (Throwable $e) {                       // attendance tables not created yet (upgrade-attendance.sql not run)
        error_log('attSchedule: ' . $e->getMessage());
        return $default;
    }
}

/** [Y-m-d => holiday name] for a date range. */
function attHolidays(string $from, string $to): array {
    $out = [];
    foreach (dbRows("SELECT holiday_date, name FROM holidays WHERE holiday_date BETWEEN ? AND ?", 'ss', [$from, $to]) as $h) $out[$h['holiday_date']] = $h['name'];
    return $out;
}

function attMin(?string $t): ?int {                 // "13:30:00" -> 810
    if ($t === null || $t === '') return null;
    $p = explode(':', $t); return (int)$p[0] * 60 + (int)$p[1];
}
function attHm(?string $t): string { return $t ? substr($t, 0, 5) : '—'; }
function attDur(?int $m): string { return $m === null ? '—' : intdiv($m, 60) . 'h ' . str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT) . 'm'; }
function attIsWeekOff(array $sched, string $date): bool { return in_array((string)date('w', strtotime($date)), explode(',', $sched['weekly_off']), true); }

/** Minutes worked = (check-out − check-in) − lunch. Lunch is the real lunch out/in when recorded, otherwise the planned lunch. */
function attWorked(array $rec, array $sched): ?int {
    $in = attMin($rec['check_in'] ?? null); $out = attMin($rec['check_out'] ?? null);
    if ($in === null || $out === null || $out <= $in) return null;
    $lo = attMin($rec['lunch_out'] ?? null); $li = attMin($rec['lunch_in'] ?? null);
    if ($lo !== null && $li !== null && $li > $lo) $lunch = $li - $lo;
    else { $ls = attMin($sched['lunch_start']); $le = attMin($sched['lunch_end']); $lunch = ($in <= $ls && $out >= $le) ? $le - $ls : 0; }
    return max(0, $out - $in - $lunch);
}
/** Minutes a full day should have (end − start − lunch). */
function attPlanned(array $sched): int {
    return max(0, attMin($sched['work_end']) - attMin($sched['work_start']) - (attMin($sched['lunch_end']) - attMin($sched['lunch_start'])));
}
/** Minutes late (0 when inside the grace time). */
function attLate(array $rec, array $sched): int {
    $in = attMin($rec['check_in'] ?? null); if ($in === null) return 0;
    $late = $in - attMin($sched['work_start']);
    return $late > (int)$sched['grace_minutes'] ? $late : 0;
}

/**
 * Every day of a month for one person: [date => ['code','label','class','rec','worked','late']].
 * No row on a past working day = Absent. Days before the person joined and future days stay blank.
 */
function attMonth(array $user, string $ym, ?array $sched = null, ?array $holidays = null): array {
    $sched = $sched ?? attSchedule((int)$user['id']);
    $first = $ym . '-01'; $last = date('Y-m-t', strtotime($first));
    $holidays = $holidays ?? attHolidays($first, $last);
    $recs = [];
    foreach (dbRows("SELECT * FROM attendance WHERE user_id = ? AND att_date BETWEEN ? AND ?", 'iss', [(int)$user['id'], $first, $last]) as $r) $recs[$r['att_date']] = $r;
    $leaves = dbRows("SELECT from_date, to_date FROM leave_requests WHERE user_id = ? AND status = 'approved' AND from_date <= ? AND to_date >= ?", 'iss', [(int)$user['id'], $last, $first]);
    $onLeave = function (string $d) use ($leaves) { foreach ($leaves as $l) if ($d >= $l['from_date'] && $d <= $l['to_date']) return true; return false; };
    $joined = substr((string)($user['created_at'] ?? '2000-01-01'), 0, 10);
    $today = date('Y-m-d'); $out = [];
    for ($d = $first; $d <= $last; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
        $rec = $recs[$d] ?? null; $row = ['date' => $d, 'rec' => $rec, 'worked' => null, 'late' => 0, 'holiday' => $holidays[$d] ?? null];
        if ($rec) {
            [$label, $code, $class] = ATT_STATUS[$rec['status']] ?? [$rec['status'], '?', 'abadge-grey'];
            $row += compact('label', 'code', 'class');
            $row['worked'] = attWorked($rec, $sched); $row['late'] = attLate($rec, $sched);
        } elseif ($d < $joined)                 { $row += ['label' => '', 'code' => '', 'class' => '']; }
        elseif (isset($holidays[$d]))           { [$label, $code, $class] = ATT_DERIVED['holiday']; $row += compact('label', 'code', 'class'); }
        elseif (attIsWeekOff($sched, $d))       { [$label, $code, $class] = ATT_DERIVED['weekoff']; $row += compact('label', 'code', 'class'); }
        elseif ($onLeave($d))                   { [$label, $code, $class] = ATT_STATUS['leave'];    $row += compact('label', 'code', 'class'); }   // approved leave
        elseif ($d > $today)                    { $row += ['label' => '', 'code' => '', 'class' => '']; }
        elseif ($d === $today)                  { $row += ['label' => 'Not in yet', 'code' => '…', 'class' => 'abadge-grey']; }
        else                                    { [$label, $code, $class] = ATT_DERIVED['absent'];  $row += compact('label', 'code', 'class'); }
        $out[$d] = $row;
    }
    return $out;
}

/** Totals for attMonth(). Half day counts as 0.5 present + 0.5 absent. */
function attTotals(array $days): array {
    $t = ['P' => 0, 'H' => 0, 'A' => 0, 'L' => 0, 'W' => 0, 'HD' => 0, 'late' => 0, 'worked' => 0];
    foreach ($days as $d) {
        if (isset($t[$d['code']])) $t[$d['code']]++;
        if ($d['late'] > 0) $t['late']++;
        $t['worked'] += (int)$d['worked'];
    }
    return $t;
}

/** Valid HH:MM (or HH:MM:SS) -> "HH:MM:00"; anything else -> null. */
function attTimeOrNull($v): ?string {
    $v = trim((string)$v);
    return preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $v) ? substr($v, 0, 5) . ':00' : null;
}
function attDateOrNull($v): ?string {
    $v = trim((string)$v);
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}
function attMonthParam($v): string { return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$v) ? (string)$v : date('Y-m'); }


/** Staff list for team pages. Managers see everybody; only the owner may change an owner's records. */
function attStaff(bool $activeOnly = true): array {
    return dbRows("SELECT id, name, username, role, is_active, created_at FROM admin_users" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY FIELD(role,'owner','manager','developer'), name");
}
function attCanManage(string $targetRole): bool {
    return can('attendance.manage') && ($targetRole !== 'owner' || currentRole() === 'owner');
}
/** Short label for the sidebar-less pages. */
function attTabs(string $current): string {
    $tabs = ['day' => ['Daily', 'attendance-manage.php?view=day'], 'month' => ['Monthly report', 'attendance-manage.php?view=month'],
             'timings' => ['Work timings', 'schedules.php'], 'holidays' => ['Holidays', 'holidays.php'], 'leaves' => ['Leaves', 'leaves.php']];
    $h = '<div class="tabs">';
    foreach ($tabs as $k => [$l, $u]) $h .= '<a href="' . $u . '" class="' . ($current === $k ? 'active' : '') . '">' . $l . '</a>';
    return $h . '</div>';
}
