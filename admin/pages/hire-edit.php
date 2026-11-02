<?php
// Hire form: candidate, job, salary & timing, projects. Filled by a manager (or the owner), approved by the owner.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('hiring.manage');
hireRequireTables();

$id   = (int)($_GET['id'] ?? 0);
$hire = null;
if ($id) {
    $hire = dbRow("SELECT * FROM hire_requests WHERE id = ?", 'i', [$id]);
    if (!$hire) { header('Location: hires.php'); exit; }
    if (!hireEditable($hire)) { flashMessage('error', 'This request can no longer be edited (only the person who made it, or the owner, can edit a draft or rejected request).'); header('Location: hire-view.php?id=' . $id); exit; }
}

$isOwner  = currentRole() === 'owner';
$errors   = [];
$people   = staffList(['owner', 'manager']);
$projects = dbRows("SELECT id, name, project_code, status FROM client_projects WHERE status NOT IN ('delivered','closed','cancelled') ORDER BY name");
$selProjects = $id ? array_map('intval', array_column(hireProjects($id), 'id')) : [];

$in = $hire ?? ['full_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'date_of_birth' => '', 'qualification' => '', 'experience' => '', 'skills' => '',
    'source_application_id' => null, 'position' => '', 'department' => 'Development', 'role' => 'developer', 'employment_type' => 'full_time', 'reports_to_id' => currentUserId(),
    'work_location' => 'Sangrur, Punjab', 'salary_monthly' => '', 'probation_months' => 3, 'notice_days' => 30, 'joining_date' => '', 'offer_valid_until' => '',
    'work_start' => '10:00:00', 'work_end' => '19:00:00', 'lunch_start' => '13:30:00', 'lunch_end' => '14:30:00', 'weekly_off' => '0'];

// New hire from a careers-page application: copy what we already know about the person.
$appId = (int)($_GET['application_id'] ?? 0);
if (!$id && $appId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $app = dbRow("SELECT * FROM job_applications WHERE id = ?", 'i', [$appId]);
    if ($app) {
        $in = array_merge($in, ['full_name' => $app['name'], 'email' => $app['email'], 'phone' => (string)$app['phone'], 'position' => (string)$app['position'],
            'experience' => (string)$app['experience'], 'source_application_id' => $appId]);
    }
}

function hireText(string $k, int $max): string { return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($_POST[$k] ?? ''))), 0, $max); }
function hireDate(string $k): ?string { $v = trim((string)($_POST[$k] ?? '')); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null; }
function hireTime(string $k): ?string { $v = trim((string)($_POST[$k] ?? '')); return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v . ':00' : null; }
/** Letters are printed in English (Latin) letters only. */
function hireLatin(string $s): bool { return !preg_match('/[^\x{20}-\x{7E}\x{A0}-\x{FF}\x{2013}\x{2014}\x{2018}\x{2019}\x{201C}\x{201D}]/u', $s); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $f = [
        'full_name' => hireText('full_name', 120), 'email' => hireText('email', 160), 'phone' => hireText('phone', 40), 'address' => hireText('address', 300),
        'date_of_birth' => hireDate('date_of_birth'), 'qualification' => hireText('qualification', 160), 'experience' => hireText('experience', 80), 'skills' => hireText('skills', 500),
        'position' => hireText('position', 120), 'department' => hireText('department', 80), 'work_location' => hireText('work_location', 160),
        'role' => (string)($_POST['role'] ?? ''), 'employment_type' => (string)($_POST['employment_type'] ?? ''),
        'reports_to_id' => (int)($_POST['reports_to_id'] ?? 0) ?: null,
        'salary_monthly' => round((float)str_replace([',', '₹', ' '], '', (string)($_POST['salary_monthly'] ?? '0')), 2),
        'probation_months' => (int)($_POST['probation_months'] ?? 0), 'notice_days' => (int)($_POST['notice_days'] ?? 0),
        'joining_date' => hireDate('joining_date'), 'offer_valid_until' => hireDate('offer_valid_until'),
        'work_start' => hireTime('work_start'), 'work_end' => hireTime('work_end'), 'lunch_start' => hireTime('lunch_start'), 'lunch_end' => hireTime('lunch_end'),
        'weekly_off' => implode(',', array_values(array_unique(array_filter(array_map('intval', (array)($_POST['weekly_off'] ?? [])), fn($d) => $d >= 0 && $d <= 6)))),
        'source_application_id' => $in['source_application_id'],
    ];
    if (!$id && ($_POST['source_application_id'] ?? '') !== '') $f['source_application_id'] = (int)$_POST['source_application_id'] ?: null;
    $selProjects = array_values(array_intersect(array_map('intval', (array)($_POST['projects'] ?? [])), array_map('intval', array_column($projects, 'id'))));
    $in = array_merge($in, $f);

    if ($f['full_name'] === '') $errors[] = 'Candidate name is required.';
    if ($f['position'] === '')  $errors[] = 'Position / designation is required.';
    foreach (['full_name' => 'Name', 'position' => 'Position', 'address' => 'Address', 'department' => 'Department', 'work_location' => 'Place of work'] as $k => $l)
        if (!hireLatin($f[$k])) $errors[] = $l . ': please use English letters only — the letters are printed in English.';
    if (!isset(HIRE_ROLES[$f['role']])) $errors[] = 'Choose the role.';
    elseif ($f['role'] === 'manager' && !$isOwner) $errors[] = 'Only the owner can hire a manager.';
    if (!isset(HIRE_TYPES[$f['employment_type']])) $errors[] = 'Choose the employment type.';
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
    if ($f['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,40}$/', $f['phone'])) $errors[] = 'Phone number is not valid.';
    if ($f['salary_monthly'] <= 0 || $f['salary_monthly'] > 1000000) $errors[] = 'Enter the monthly salary (more than 0).';
    if ($f['probation_months'] < 0 || $f['probation_months'] > 12) $errors[] = 'Probation must be 0 to 12 months.';
    if ($f['notice_days'] < 0 || $f['notice_days'] > 180) $errors[] = 'Notice period must be 0 to 180 days.';
    if (!$f['joining_date']) $errors[] = 'Choose the joining date.';
    elseif ($f['joining_date'] > date('Y-m-d', strtotime('+1 year'))) $errors[] = 'Joining date is too far away.';
    if ($f['date_of_birth'] && ($f['date_of_birth'] > date('Y-m-d', strtotime('-15 years')) || $f['date_of_birth'] < date('Y-m-d', strtotime('-70 years')))) $errors[] = 'Date of birth does not look right.';
    if ($f['offer_valid_until'] && $f['joining_date'] && $f['offer_valid_until'] > $f['joining_date']) $errors[] = 'The offer should be valid until a date on or before the joining date.';
    foreach (['work_start', 'work_end', 'lunch_start', 'lunch_end'] as $k) if (!$f[$k]) { $errors[] = 'Fill all four work timing fields.'; break; }
    if ($f['work_start'] && $f['work_end'] && $f['lunch_start'] && $f['lunch_end'] && !($f['work_start'] < $f['lunch_start'] && $f['lunch_start'] < $f['lunch_end'] && $f['lunch_end'] < $f['work_end'])) $errors[] = 'Timing order must be: start < lunch start < lunch end < end.';
    if ($f['reports_to_id'] && !in_array($f['reports_to_id'], array_map('intval', array_column($people, 'id')), true)) $f['reports_to_id'] = null;

    if (!$errors) {
        $submit = ($_POST['action'] ?? '') === 'submit';
        $conn->begin_transaction();
        try {
            $cols = ['full_name','email','phone','address','date_of_birth','qualification','experience','skills','source_application_id','position','department','role','employment_type',
                'reports_to_id','work_location','salary_monthly','probation_months','notice_days','joining_date','offer_valid_until','work_start','work_end','lunch_start','lunch_end','weekly_off'];
            $types = 'ssssssssissssisdiisssssss';     // one letter per column above (25)
            $vals = array_map(fn($c) => $f[$c], $cols);
            if ($id) {
                $st = $conn->prepare("UPDATE hire_requests SET " . implode('=?, ', $cols) . "=?, status = IF(status = 'rejected', 'draft', status) WHERE id = ? AND status IN ('draft','rejected')");
                $t2 = $types . 'i'; $v2 = array_merge($vals, [$id]); $st->bind_param($t2, ...$v2); $st->execute();
                hireLog($id, $hire['status'] === 'rejected' ? 'reopened' : 'edited', $hire['status'] === 'rejected' ? 'Edited after rejection, back to draft.' : '');
            } else {
                $uid = currentUserId();
                $st = $conn->prepare("INSERT INTO hire_requests (" . implode(',', $cols) . ", created_by_id) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ", ?)");
                $t2 = $types . 'i'; $v2 = array_merge($vals, [$uid]); $st->bind_param($t2, ...$v2); $st->execute();
                $id = (int)$conn->insert_id;
                hireLog($id, 'created', $f['source_application_id'] ? 'Started from a careers-page application.' : '');
            }
            $conn->query("DELETE FROM hire_projects WHERE hire_id = " . (int)$id);
            if ($selProjects) {
                $hp = $conn->prepare("INSERT INTO hire_projects (hire_id, project_id) VALUES (?, ?)");
                foreach ($selProjects as $pid) { $hp->bind_param('ii', $id, $pid); $hp->execute(); }
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('hire save failed: ' . $e->getMessage());
            $errors[] = 'Could not save. Please try again.';
        }
        if (!$errors) {
            $fresh = dbRow("SELECT * FROM hire_requests WHERE id = ?", 'i', [$id]);
            $approveNow = ($_POST['action'] ?? '') === 'approve_now' && can('hiring.approve');       // the owner is the approver, so no waiting is needed
            if ($approveNow && $fresh && $fresh['status'] === 'draft' && !hireMove($fresh, 'pending', ['submitted_at' => ['s', date('Y-m-d H:i:s')]])) {
                $fresh['status'] = 'pending'; hireLog($id, 'submitted', 'Owner hired directly.');
                $when = date('Y-m-d H:i:s'); $uid = currentUserId();
                if (!hireMove($fresh, 'approved', ['decided_by_id' => ['i', $uid], 'decided_at' => ['s', $when], 'decision_note' => ['s', 'Hired directly by the owner.'], 'offer_ref' => ['s', sprintf('BUN/OFR/%s/%04d', date('Y'), $id)]])) {
                    hireLog($id, 'approved', 'Hired directly by the owner.');
                    flashMessage('success', 'Approved. Download the offer letter below and give it to the candidate.');
                } else flashMessage('success', 'Saved as draft.');
            } elseif ($submit && $fresh && $fresh['status'] === 'draft' && !hireMove($fresh, 'pending', ['submitted_at' => ['s', date('Y-m-d H:i:s')]])) {
                hireLog($id, 'submitted', 'Sent to the owner for approval.');
                flashMessage('success', 'Sent to the owner for approval.');
            } else flashMessage('success', 'Saved as draft. Send it to the owner when it is ready.');
            header('Location: hire-view.php?id=' . $id); exit;
        }
    }
}

$pageTitle = $hire ? 'Edit hire request' : 'New hire';
$v = fn($k, $d = '') => clean((string)($in[$k] ?? $d));
$hm = fn($k) => clean(substr((string)($in[$k] ?? ''), 0, 5));
$off = array_filter(explode(',', (string)($in['weekly_off'] ?? '')), 'strlen');
include __DIR__ . '/../includes/admin-header.php';
?>
<?php foreach ($errors as $err): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($err); ?></div><?php endforeach; ?>

<form method="POST" autocomplete="off">
<?php echo csrfField(); ?>
<input type="hidden" name="source_application_id" value="<?php echo (int)($in['source_application_id'] ?? 0) ?: ''; ?>">

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>1. Candidate <span class="td-muted" style="font-weight:400;font-size:.8rem;">· no job post on the website is needed</span></h2><a href="<?php echo $hire ? 'hire-view.php?id=' . $id : 'hires.php'; ?>" class="abtn abtn-outline abtn-sm">← Back</a></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group"><label>Full name * <span class="hint">(as in ID proof, English letters)</span></label><input type="text" name="full_name" required maxlength="120" value="<?php echo $v('full_name'); ?>"></div>
        <div class="form-group"><label>Phone</label><input type="tel" name="phone" maxlength="40" value="<?php echo $v('phone'); ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" maxlength="160" value="<?php echo $v('email'); ?>"></div>
        <div class="form-group"><label>Date of birth</label><input type="date" name="date_of_birth" value="<?php echo $v('date_of_birth'); ?>"></div>
        <div class="form-group full-width"><label>Address (printed on the letter)</label><input type="text" name="address" maxlength="300" value="<?php echo $v('address'); ?>"></div>
        <div class="form-group"><label>Qualification</label><input type="text" name="qualification" maxlength="160" value="<?php echo $v('qualification'); ?>" placeholder="B.Tech CSE, BCA…"></div>
        <div class="form-group"><label>Experience</label><input type="text" name="experience" maxlength="80" value="<?php echo $v('experience'); ?>" placeholder="Fresher / 2 years"></div>
        <div class="form-group full-width"><label>Skills</label><input type="text" name="skills" maxlength="500" value="<?php echo $v('skills'); ?>" placeholder="PHP, MySQL, JavaScript…"></div>
    </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>2. Job</h2></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group"><label>Position / designation *</label><input type="text" name="position" required maxlength="120" value="<?php echo $v('position'); ?>" placeholder="PHP Developer"></div>
        <div class="form-group"><label>Department</label><input type="text" name="department" maxlength="80" value="<?php echo $v('department'); ?>"></div>
        <div class="form-group"><label>Login role after joining *</label>
            <select name="role"><?php foreach (HIRE_ROLES as $k => $l): if ($k === 'manager' && !$isOwner) continue; ?><option value="<?php echo $k; ?>" <?php echo ($in['role'] ?? '') === $k ? 'selected' : ''; ?>><?php echo clean($l); ?></option><?php endforeach; ?></select>
            <span class="hint">Developer sees only the projects given to them. Hiring a manager can only be done by the owner.</span></div>
        <div class="form-group"><label>Employment type</label>
            <select name="employment_type"><?php foreach (HIRE_TYPES as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($in['employment_type'] ?? '') === $k ? 'selected' : ''; ?>><?php echo clean($l); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Reports to</label>
            <select name="reports_to_id"><option value="0">—</option><?php foreach ($people as $u): ?><option value="<?php echo (int)$u['id']; ?>" <?php echo (int)($in['reports_to_id'] ?? 0) === (int)$u['id'] ? 'selected' : ''; ?>><?php echo clean($u['name']); ?> (<?php echo clean(ROLE_LABELS[$u['role']] ?? $u['role']); ?>)</option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Place of work</label><input type="text" name="work_location" maxlength="160" value="<?php echo $v('work_location'); ?>"></div>
    </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>3. Salary &amp; terms</h2></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group"><label>Monthly salary (₹, gross) *</label><input type="text" inputmode="decimal" name="salary_monthly" required value="<?php echo $v('salary_monthly'); ?>" placeholder="25000"></div>
        <div class="form-group"><label>Joining date *</label><input type="date" name="joining_date" required value="<?php echo $v('joining_date'); ?>"></div>
        <div class="form-group"><label>Probation (months)</label><input type="number" name="probation_months" min="0" max="12" value="<?php echo $v('probation_months', '3'); ?>"></div>
        <div class="form-group"><label>Notice period (days)</label><input type="number" name="notice_days" min="0" max="180" value="<?php echo $v('notice_days', '30'); ?>"></div>
        <div class="form-group"><label>Offer valid until <span class="hint">(empty = 7 days after the offer is issued)</span></label><input type="date" name="offer_valid_until" value="<?php echo $v('offer_valid_until'); ?>"></div>
    </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>4. Work timing</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group"><label>Start</label><input type="time" name="work_start" required value="<?php echo $hm('work_start'); ?>"></div>
            <div class="form-group"><label>End</label><input type="time" name="work_end" required value="<?php echo $hm('work_end'); ?>"></div>
            <div class="form-group"><label>Lunch from</label><input type="time" name="lunch_start" required value="<?php echo $hm('lunch_start'); ?>"></div>
            <div class="form-group"><label>Lunch to</label><input type="time" name="lunch_end" required value="<?php echo $hm('lunch_end'); ?>"></div>
        </div>
        <div class="check-list" style="margin-top:14px;"><?php foreach (WEEKDAYS_LONG as $i => $d): ?>
            <label><input type="checkbox" name="weekly_off[]" value="<?php echo $i; ?>" <?php echo in_array((string)$i, $off, true) ? 'checked' : ''; ?>> <?php echo $d; ?> off</label><?php endforeach; ?></div>
        <p class="hint" style="margin-top:8px;">This becomes the person's attendance timing when they join.</p>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>5. Projects (one or more)</h2></div>
    <div class="card-body">
        <?php if (!$projects): ?><p class="hint">No running projects yet. You can also assign projects after the person has joined.</p><?php endif; ?>
        <div class="check-list"><?php foreach ($projects as $p): ?>
            <label><input type="checkbox" name="projects[]" value="<?php echo (int)$p['id']; ?>" <?php echo in_array((int)$p['id'], $selProjects, true) ? 'checked' : ''; ?>> <?php echo clean($p['name']); ?></label><?php endforeach; ?></div>
        <p class="hint" style="margin-top:8px;">The person is added as developer on all ticked projects when they join. A project can have many developers.</p>
    </div>
</div>

<div class="form-actions" style="margin-bottom:30px;">
    <?php if (can('hiring.approve')): ?><button type="submit" name="action" value="approve_now" class="abtn abtn-primary">Save &amp; approve now</button>
    <button type="submit" name="action" value="submit" class="abtn abtn-outline">Save &amp; keep waiting for approval</button>
    <?php else: ?><button type="submit" name="action" value="submit" class="abtn abtn-primary">Save &amp; send to owner</button><?php endif; ?>
    <button type="submit" name="action" value="save" class="abtn abtn-outline">Save as draft</button>
</div>
</form>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
