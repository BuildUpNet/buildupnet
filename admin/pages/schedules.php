<?php
// Working hours per person: start, end, lunch, grace time, weekly off. Owner & manager.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('attendance.manage');

/** Validates the posted timing; returns [values, error]. */
function readSchedule(array $p): array {
    $v = ['work_start' => attTimeOrNull($p['work_start'] ?? ''), 'work_end' => attTimeOrNull($p['work_end'] ?? ''),
          'lunch_start' => attTimeOrNull($p['lunch_start'] ?? ''), 'lunch_end' => attTimeOrNull($p['lunch_end'] ?? '')];
    foreach ($v as $k => $x) if ($x === null) return [$v, 'Please fill all four times (HH:MM).'];
    [$s, $e, $ls, $le] = [attMin($v['work_start']), attMin($v['work_end']), attMin($v['lunch_start']), attMin($v['lunch_end'])];
    if ($e <= $s) return [$v, 'End time must be after start time.'];
    if (!($ls < $le && $ls >= $s && $le <= $e)) return [$v, 'Lunch must be inside working hours (start before end).'];
    $v['grace_minutes'] = max(0, min(120, (int)($p['grace_minutes'] ?? 10)));
    $days = array_unique(array_filter(array_map('intval', (array)($p['weekly_off'] ?? [])), fn($n) => $n >= 0 && $n <= 6));
    sort($days); $v['weekly_off'] = implode(',', $days);
    return [$v, null];
}
function saveSchedule(int $uid, array $v): void {
    global $conn; $me = currentUserId();
    $st = $conn->prepare("INSERT INTO work_schedules (user_id, work_start, work_end, lunch_start, lunch_end, grace_minutes, weekly_off, updated_by_id) VALUES (?,?,?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE work_start=VALUES(work_start), work_end=VALUES(work_end), lunch_start=VALUES(lunch_start), lunch_end=VALUES(lunch_end), grace_minutes=VALUES(grace_minutes), weekly_off=VALUES(weekly_off), updated_by_id=VALUES(updated_by_id)");
    $st->bind_param('issssisi', $uid, $v['work_start'], $v['work_end'], $v['lunch_start'], $v['lunch_end'], $v['grace_minutes'], $v['weekly_off'], $me);
    $st->execute();
}

$staff = attStaff(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    [$v, $err] = readSchedule($_POST);
    if ($err) { flashMessage('error', $err); }
    elseif (($_POST['action'] ?? '') === 'bulk') {
        $who = (string)($_POST['who'] ?? ''); $n = 0;
        foreach ($staff as $u) {
            if (($who === 'all' || $who === $u['role']) && attCanManage($u['role'])) { saveSchedule((int)$u['id'], $v); $n++; }
        }
        flashMessage('success', "Timing applied to $n people.");
    } else {
        $uid = (int)($_POST['user'] ?? 0);
        $t = $uid ? dbRow("SELECT id, name, role FROM admin_users WHERE id = ?", 'i', [$uid]) : null;
        if (!$t || !attCanManage($t['role'])) flashMessage('error', 'You cannot change this person\'s timing.');
        else { saveSchedule($uid, $v); flashMessage('success', 'Timing saved for ' . $t['name'] . '.'); }
    }
    header('Location: schedules.php'); exit;
}

$pageTitle = 'Work Timings';
include __DIR__ . '/../includes/admin-header.php';

/** One timing form (used for the bulk form and every person). */
function scheduleFields(array $s): void { ?>
    <div class="sched-grid">
        <div class="form-group"><label>Start</label><input type="time" name="work_start" value="<?php echo clean(substr($s['work_start'], 0, 5)); ?>" required></div>
        <div class="form-group"><label>End</label><input type="time" name="work_end" value="<?php echo clean(substr($s['work_end'], 0, 5)); ?>" required></div>
        <div class="form-group"><label>Lunch from</label><input type="time" name="lunch_start" value="<?php echo clean(substr($s['lunch_start'], 0, 5)); ?>" required></div>
        <div class="form-group"><label>Lunch to</label><input type="time" name="lunch_end" value="<?php echo clean(substr($s['lunch_end'], 0, 5)); ?>" required></div>
        <div class="form-group"><label>Grace (min)</label><input type="number" name="grace_minutes" min="0" max="120" value="<?php echo (int)$s['grace_minutes']; ?>"></div>
    </div>
    <div class="off-days"><span>Weekly off:</span>
        <?php $off = array_filter(explode(',', $s['weekly_off']), 'strlen'); foreach (WEEKDAYS as $i => $wd): ?>
            <label><input type="checkbox" name="weekly_off[]" value="<?php echo $i; ?>" <?php echo in_array((string)$i, $off, true) ? 'checked' : ''; ?>> <?php echo $wd; ?></label>
        <?php endforeach; ?>
    </div>
<?php } ?>
<?php renderFlash(); ?>
<?php echo attTabs('timings'); ?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>Set the same timing for a group</h2></div>
    <div class="card-body">
        <form method="POST"><?php echo csrfField(); ?><input type="hidden" name="action" value="bulk">
            <?php scheduleFields(['work_start' => '10:00', 'work_end' => '19:00', 'lunch_start' => '13:30', 'lunch_end' => '14:30', 'grace_minutes' => 10, 'weekly_off' => '0']); ?>
            <div class="form-actions" style="margin-top:14px;align-items:center;">
                <select name="who" style="min-height:42px;border:1px solid var(--a-border);border-radius:8px;padding:0 12px;">
                    <option value="developer">All developers</option><option value="manager">All managers</option><option value="all">Everyone</option>
                </select>
                <button class="abtn abtn-primary" type="submit" data-confirm="Replace the timing of the selected group?">Apply to group</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($staff as $u): $s = attSchedule((int)$u['id']); $editable = attCanManage($u['role']); ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h2><?php echo clean($u['name']); ?> <span class="abadge abadge-grey"><?php echo clean(ROLE_LABELS[$u['role']] ?? $u['role']); ?></span></h2>
        <span class="td-muted">Planned work: <?php echo attDur(attPlanned($s)); ?>/day</span></div>
    <div class="card-body">
        <?php if ($editable): ?>
        <form method="POST"><?php echo csrfField(); ?><input type="hidden" name="user" value="<?php echo (int)$u['id']; ?>">
            <?php scheduleFields($s); ?>
            <div class="form-actions" style="margin-top:12px;"><button class="abtn abtn-primary abtn-sm" type="submit">Save</button></div>
        </form>
        <?php else: ?><p class="td-muted" style="margin:0;"><?php echo attHm($s['work_start']); ?>–<?php echo attHm($s['work_end']); ?>, lunch <?php echo attHm($s['lunch_start']); ?>–<?php echo attHm($s['lunch_end']); ?> (only the owner can change this)</p><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
