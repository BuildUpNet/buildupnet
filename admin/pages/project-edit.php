<?php
// Add / edit a client project (owner + manager).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
requirePermission($id ? 'projects.edit' : 'projects.create');

$project = null; $selectedDevs = [];
if ($id) {
    $project = dbRow("SELECT * FROM client_projects WHERE id = ?", 'i', [$id]);
    if (!$project) { header('Location: projects.php'); exit; }
    $selectedDevs = array_map('intval', array_column(dbRows("SELECT user_id FROM project_developers WHERE project_id = ?", 'i', [$id]), 'user_id'));
}

$developers = staffList(['developer']);
$owners     = staffList(['owner']);
$allStaff   = staffList();
$errors     = [];
$in         = $project ?? ['status' => 'lead', 'priority' => 'normal', 'source_type' => 'client', 'project_type' => 'Website', 'price' => 0, 'advance_paid' => 0];

/** Read a money field: "25,000" -> 25000.00 (never negative). */
function moneyField(string $key): float {
    $v = (float)str_replace([',', '₹', ' '], '', (string)($_POST[$key] ?? '0'));
    return max(0, round($v, 2));
}
function dateField(string $key): ?string {
    $v = trim((string)($_POST[$key] ?? ''));
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $name    = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 160);
    $type    = in_array($_POST['project_type'] ?? '', PROJECT_TYPES, true) ? $_POST['project_type'] : 'Other';
    $status  = isset(PROJECT_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : 'lead';
    $prio    = isset(PROJECT_PRIORITIES[$_POST['priority'] ?? '']) ? $_POST['priority'] : 'normal';
    $source  = ($_POST['source_type'] ?? '') === 'owner_direct' ? 'owner_direct' : 'client';
    $desc    = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 5000);
    $notes   = mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 5000);
    $hosting = mb_substr(trim((string)($_POST['hosting_notes'] ?? '')), 0, 3000);
    $price   = moneyField('price'); $advance = moneyField('advance_paid');
    $start   = dateField('start_date'); $deadline = dateField('deadline'); $delivered = dateField('delivered_on');
    $live    = trim((string)($_POST['live_url'] ?? ''));
    $stack   = implode(', ', array_slice(array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($_POST['tech_stack'] ?? ''))), 'strlen'))), 0, 30));
    $stack   = mb_substr($stack, 0, 500);

    if ($source === 'owner_direct') {
        $ownerId = (int)($_POST['direct_owner_id'] ?? 0);
        $clientName = ''; foreach ($owners as $o) if ((int)$o['id'] === $ownerId) $clientName = $o['name'];
        $company = $phone = $email = '';
        if ($clientName === '') $errors[] = 'Choose which owner gave this project.';
    } else {
        $clientName = mb_substr(trim((string)($_POST['client_name'] ?? '')), 0, 160);
        $company    = mb_substr(trim((string)($_POST['client_company'] ?? '')), 0, 160);
        $phone      = mb_substr(trim((string)($_POST['client_phone'] ?? '')), 0, 40);
        $email      = mb_substr(trim((string)($_POST['client_email'] ?? '')), 0, 160);
        if ($clientName === '') $errors[] = 'Client name is required (or choose "Direct from owner").';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Client email is not valid.';
    }
    if ($name === '') $errors[] = 'Project name is required.';
    if ($advance > $price) $errors[] = 'Advance received cannot be more than the project price.';
    if ($deadline && $start && $deadline < $start) $errors[] = 'Deadline cannot be before the start date.';
    if ($live !== '' && safeUrl($live) === '') $errors[] = 'Live URL must start with http:// or https://';

    $broughtBy = (int)($_POST['brought_by_id'] ?? 0);
    if (!in_array($broughtBy, array_map('intval', array_column($allStaff, 'id')), true)) $broughtBy = null;
    $devIds = array_values(array_intersect(array_map('intval', (array)($_POST['developers'] ?? [])), array_map('intval', array_column($developers, 'id'))));
    if ($status === 'delivered' && !$delivered) $delivered = date('Y-m-d');
    if ($status !== 'delivered') $delivered = null;

    $in = array_merge($in, compact('name', 'status', 'source') + ['project_type' => $type, 'priority' => $prio, 'source_type' => $source, 'description' => $desc,
        'notes' => $notes, 'hosting_notes' => $hosting, 'price' => $price, 'advance_paid' => $advance, 'start_date' => $start, 'deadline' => $deadline,
        'live_url' => $live, 'tech_stack' => $stack, 'client_name' => $clientName, 'client_company' => $company ?? '', 'client_phone' => $phone ?? '',
        'client_email' => $email ?? '', 'brought_by_id' => $broughtBy]);
    $selectedDevs = $devIds;

    if (!$errors) {
        $conn->begin_transaction();
        try {
            if ($id) {
                $st = $conn->prepare("UPDATE client_projects SET name=?, project_type=?, description=?, tech_stack=?, source_type=?, client_name=?, client_company=?, client_phone=?, client_email=?,
                    brought_by_id=?, price=?, advance_paid=?, status=?, priority=?, start_date=?, deadline=?, delivered_on=?, live_url=?, hosting_notes=?, notes=?, updated_at=NOW() WHERE id=?");
                $st->bind_param('sssssssssiddssssssssi', $name, $type, $desc, $stack, $source, $clientName, $company, $phone, $email, $broughtBy, $price, $advance,
                    $status, $prio, $start, $deadline, $delivered, $live, $hosting, $notes, $id);
                $st->execute();
                if ($project['status'] !== $status) {
                    $lg = $conn->prepare("INSERT INTO project_updates (project_id, user_id, note, old_status, new_status) VALUES (?,?,?,?,?)");
                    $uid = currentUserId(); $msg = 'Status changed while editing the project.';
                    $lg->bind_param('iisss', $id, $uid, $msg, $project['status'], $status); $lg->execute();
                }
            } else {
                $uid = currentUserId();
                $st = $conn->prepare("INSERT INTO client_projects (name, project_type, description, tech_stack, source_type, client_name, client_company, client_phone, client_email,
                    brought_by_id, price, advance_paid, status, priority, start_date, deadline, delivered_on, live_url, hosting_notes, notes, created_by_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $st->bind_param('sssssssssiddssssssssi', $name, $type, $desc, $stack, $source, $clientName, $company, $phone, $email, $broughtBy, $price, $advance,
                    $status, $prio, $start, $deadline, $delivered, $live, $hosting, $notes, $uid);
                $st->execute();
                $id = (int)$conn->insert_id;
                $code = sprintf('BUN-%s-%03d', date('Y'), $id);
                $cs = $conn->prepare("UPDATE client_projects SET project_code = ? WHERE id = ?"); $cs->bind_param('si', $code, $id); $cs->execute();
                $lg = $conn->prepare("INSERT INTO project_updates (project_id, user_id, note, new_status) VALUES (?,?,?,?)");
                $msg = 'Project created.'; $lg->bind_param('iiss', $id, $uid, $msg, $status); $lg->execute();
            }
            $conn->query("DELETE FROM project_developers WHERE project_id = " . (int)$id);
            if ($devIds) {
                $pd = $conn->prepare("INSERT INTO project_developers (project_id, user_id) VALUES (?, ?)");
                foreach ($devIds as $d) { $pd->bind_param('ii', $id, $d); $pd->execute(); }
            }
            $conn->commit();
            flashMessage('success', 'Project saved.');
            header('Location: project-view.php?id=' . $id); exit;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('project save failed: ' . $e->getMessage());
            $errors[] = 'Could not save the project. Please try again.';
        }
    }
}

$pageTitle = $project ? 'Edit Project' : 'New Project';
$v = fn($k, $d = '') => clean((string)($in[$k] ?? $d));
include __DIR__ . '/../includes/admin-header.php';
?>
<?php foreach ($errors as $err): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($err); ?></div><?php endforeach; ?>

<form method="POST" autocomplete="off">
<?php echo csrfField(); ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>1. Project</h2><a href="<?php echo $project ? 'project-view.php?id=' . $id : 'projects.php'; ?>" class="abtn abtn-outline abtn-sm">← Back</a></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group full-width"><label>Project name *</label><input type="text" name="name" required maxlength="160" value="<?php echo $v('name'); ?>" placeholder="e.g. Grover Travels — new website"></div>
        <div class="form-group"><label>Project type</label>
            <select name="project_type"><?php foreach (PROJECT_TYPES as $t): ?><option <?php echo ($in['project_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo clean($t); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Tech stack</label><input type="text" name="tech_stack" maxlength="500" list="stackList" value="<?php echo $v('tech_stack'); ?>" placeholder="PHP, MySQL, Bootstrap, WordPress…">
            <datalist id="stackList"><?php foreach (['PHP', 'Laravel', 'MySQL', 'WordPress', 'Shopify', 'WooCommerce', 'JavaScript', 'React', 'Node.js', 'Kotlin', 'Flutter', 'Bootstrap', 'Tailwind CSS', 'Figma'] as $t): ?><option value="<?php echo $t; ?>"><?php endforeach; ?></datalist>
            <span class="hint">Separate with commas.</span></div>
        <div class="form-group"><label>Status</label>
            <select name="status"><?php foreach (PROJECT_STATUSES as $k => $s): ?><option value="<?php echo $k; ?>" <?php echo ($in['status'] ?? '') === $k ? 'selected' : ''; ?>><?php echo clean($s[0]); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Priority</label>
            <select name="priority"><?php foreach (PROJECT_PRIORITIES as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($in['priority'] ?? '') === $k ? 'selected' : ''; ?>><?php echo clean($l); ?></option><?php endforeach; ?></select></div>
        <div class="form-group full-width"><label>What does the client need? (requirements)</label><textarea name="description" rows="4" maxlength="5000"><?php echo $v('description'); ?></textarea></div>
    </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>2. Who gave this project?</h2></div>
    <div class="card-body">
        <div class="radio-row">
            <label><input type="radio" name="source_type" value="client" <?php echo ($in['source_type'] ?? 'client') === 'client' ? 'checked' : ''; ?>> A client</label>
            <label><input type="radio" name="source_type" value="owner_direct" <?php echo ($in['source_type'] ?? '') === 'owner_direct' ? 'checked' : ''; ?>> Direct from owner</label>
        </div>
        <div class="form-grid src-client" style="margin-top:16px;">
            <div class="form-group"><label>Client name *</label><input type="text" name="client_name" maxlength="160" value="<?php echo ($in['source_type'] ?? 'client') === 'client' ? $v('client_name') : ''; ?>"></div>
            <div class="form-group"><label>Company / business</label><input type="text" name="client_company" maxlength="160" value="<?php echo $v('client_company'); ?>"></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="client_phone" maxlength="40" value="<?php echo $v('client_phone'); ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="client_email" maxlength="160" value="<?php echo $v('client_email'); ?>"></div>
        </div>
        <div class="form-grid src-owner" style="margin-top:16px;">
            <div class="form-group"><label>Which owner gave it? *</label>
                <select name="direct_owner_id"><option value="">Select owner</option>
                    <?php foreach ($owners as $o): ?><option value="<?php echo (int)$o['id']; ?>" <?php echo ($in['source_type'] ?? '') === 'owner_direct' && ($in['client_name'] ?? '') === $o['name'] ? 'selected' : ''; ?>><?php echo clean($o['name']); ?></option><?php endforeach; ?></select></div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>3. Team</h2></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group"><label>Brought by (who got this project)</label>
            <select name="brought_by_id"><option value="0">— nobody / walk-in —</option>
                <?php foreach ($allStaff as $u): ?><option value="<?php echo (int)$u['id']; ?>" <?php echo (int)($in['brought_by_id'] ?? 0) === (int)$u['id'] ? 'selected' : ''; ?>><?php echo clean($u['name']); ?> (<?php echo clean(ROLE_LABELS[$u['role']] ?? $u['role']); ?>)</option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Developers working on it</label>
            <?php if (!$developers): ?><span class="hint">No developer accounts yet. The owner can add them in Users &amp; Roles.</span><?php endif; ?>
            <div class="check-list"><?php foreach ($developers as $d): ?>
                <label><input type="checkbox" name="developers[]" value="<?php echo (int)$d['id']; ?>" <?php echo in_array((int)$d['id'], $selectedDevs, true) ? 'checked' : ''; ?>> <?php echo clean($d['name']); ?></label>
            <?php endforeach; ?></div></div>
    </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>4. Money &amp; dates</h2></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group"><label>Project price (₹)</label><input type="text" inputmode="decimal" name="price" value="<?php echo $v('price', '0'); ?>"></div>
        <div class="form-group"><label>Advance / received so far (₹)</label><input type="text" inputmode="decimal" name="advance_paid" value="<?php echo $v('advance_paid', '0'); ?>"></div>
        <div class="form-group"><label>Start date</label><input type="date" name="start_date" value="<?php echo $v('start_date'); ?>"></div>
        <div class="form-group"><label>Deadline</label><input type="date" name="deadline" value="<?php echo $v('deadline'); ?>"></div>
    </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>5. Links &amp; notes</h2></div>
    <div class="card-body"><div class="form-grid">
        <div class="form-group full-width"><label>Live / staging URL</label><input type="url" name="live_url" maxlength="300" value="<?php echo $v('live_url'); ?>" placeholder="https://"></div>
        <div class="form-group full-width"><label>Domain &amp; hosting details</label><textarea name="hosting_notes" rows="3" maxlength="3000" placeholder="Domain registrar, hosting provider, renewal dates…"><?php echo $v('hosting_notes'); ?></textarea>
            <span class="hint">⚠ Never type passwords here — use a password manager.</span></div>
        <div class="form-group full-width"><label>Internal notes (managers &amp; owners only)</label><textarea name="notes" rows="3" maxlength="5000"><?php echo $v('notes'); ?></textarea></div>
    </div></div>
</div>

<div class="form-actions"><button type="submit" class="abtn abtn-primary"><?php echo $project ? 'Save changes' : 'Create project'; ?></button>
    <a href="<?php echo $project ? 'project-view.php?id=' . $id : 'projects.php'; ?>" class="abtn abtn-outline">Cancel</a></div>
</form>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
