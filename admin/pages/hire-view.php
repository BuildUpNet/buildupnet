<?php
// One hire: details, owner approval, document checklist, projects, letters, joining. Every change is a POST with CSRF + role + status checks.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('hiring.manage');
hireRequireTables();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$h  = $id ? dbRow("SELECT h.*, c.name AS creator, d.name AS decider, r.name AS reports_name
                   FROM hire_requests h JOIN admin_users c ON c.id = h.created_by_id
                   LEFT JOIN admin_users d ON d.id = h.decided_by_id LEFT JOIN admin_users r ON r.id = h.reports_to_id WHERE h.id = ?", 'i', [$id]) : null;
if (!$h) { flashMessage('error', 'Hire request not found.'); header('Location: hires.php'); exit; }

$me = currentUserId(); $today = date('Y-m-d'); $now = date('Y-m-d H:i:s');
$docOpen = in_array($h['status'], ['approved', 'offer_issued', 'accepted'], true);

/** Ends a POST: JSON for the no-reload buttons, otherwise flash + redirect. */
function hireDone(bool $ok, string $msg, int $id): void {
    if (isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['ok' => $ok, 'message' => $msg]); exit; }
    flashMessage($ok ? 'success' : 'error', $msg);
    header('Location: hire-view.php?id=' . $id); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $act  = (string)($_POST['action'] ?? '');
    $note = mb_substr(plainText((string)($_POST['note'] ?? '')), 0, 500);
    $err  = null; $okMsg = 'Done.';

    switch ($act) {
        case 'submit':
            if (!hireOwns($h)) { $err = 'Only the person who made this request can send it.'; break; }
            $err = hireMove($h, 'pending', ['submitted_at' => ['s', $now]]);
            if (!$err) { hireLog($id, 'submitted', 'Sent to the owner for approval.'); $okMsg = 'Sent to the owner for approval.'; }
            break;

        case 'withdraw':
            if (!hireOwns($h)) { $err = 'Only the person who made this request can take it back.'; break; }
            $err = hireMove($h, 'draft');
            if (!$err) { hireLog($id, 'withdrawn', 'Taken back to draft.'); $okMsg = 'Back to draft.'; }
            break;

        case 'approve':
        case 'reject':
            if (!can('hiring.approve')) { $err = 'Only the owner can approve or reject a hire.'; break; }
            if ($act === 'reject' && $note === '') { $err = 'Write a short reason when rejecting, so the manager knows what to fix.'; break; }
            $set = ['decided_by_id' => ['i', $me], 'decided_at' => ['s', $now], 'decision_note' => ['s', $note]];
            if ($act === 'approve') $set['offer_ref'] = ['s', sprintf('BUN/OFR/%s/%04d', date('Y'), $id)];
            $err = hireMove($h, $act === 'approve' ? 'approved' : 'rejected', $set);
            if (!$err) { hireLog($id, $act === 'approve' ? 'approved' : 'rejected', $note); $okMsg = $act === 'approve' ? 'Approved. You can now issue the offer letter.' : 'Rejected. The manager can edit and send it again.'; }
            break;

        case 'issue_offer':
            $err = hireMove($h, 'offer_issued', ['offer_date' => ['s', $h['offer_date'] ?: $today]]);
            if (!$err) { hireLog($id, 'offer_issued', 'Offer letter issued to the candidate.'); $okMsg = 'Offer marked as issued.'; }
            break;

        case 'accept':
            $on = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['accepted_on'] ?? '')) && strtotime($_POST['accepted_on']) ? $_POST['accepted_on'] : $today;
            $err = hireMove($h, 'accepted', ['accepted_on' => ['s', $on]]);
            if (!$err) { hireLog($id, 'accepted', 'Candidate accepted the offer on ' . fmtDate($on) . '.'); $okMsg = 'Offer accepted by the candidate.'; }
            break;

        case 'decline':
            $err = hireMove($h, 'declined');
            if (!$err) { hireLog($id, 'declined', $note); $okMsg = 'Marked as declined.'; }
            break;

        case 'cancel':
            // A draft/rejected request can be dropped by whoever made it; once the owner approved it, only the owner can stop it.
            $allowed = in_array($h['status'], ['draft', 'rejected'], true) ? hireOwns($h) : currentRole() === 'owner';
            if (!$allowed) { $err = 'Only the owner can cancel a request that is already approved.'; break; }
            $err = hireMove($h, 'cancelled');
            if (!$err) { hireLog($id, 'cancelled', $note); $okMsg = 'Request cancelled.'; }
            break;

        case 'toggle_doc':
            if (!$docOpen) { $err = 'Documents can be ticked after the owner has approved the hire.'; break; }
            $did = (int)($_POST['doc_id'] ?? 0);
            $dt = dbRow("SELECT id, label FROM hire_doc_types WHERE id = ? AND is_active = 1", 'i', [$did]);
            if (!$dt) { $err = 'Unknown document.'; break; }
            if (dbRow("SELECT 1 AS x FROM hire_documents WHERE hire_id = ? AND doc_type_id = ?", 'ii', [$id, $did])) {
                $st = $conn->prepare("DELETE FROM hire_documents WHERE hire_id = ? AND doc_type_id = ?"); $st->bind_param('ii', $id, $did); $st->execute();
                hireLog($id, 'doc_removed', $dt['label'] . ' un-ticked.'); $okMsg = 'Un-ticked.';
            } else {
                $st = $conn->prepare("INSERT INTO hire_documents (hire_id, doc_type_id, received_on, marked_by_id) VALUES (?,?,?,?)"); $st->bind_param('iisi', $id, $did, $today, $me); $st->execute();
                hireLog($id, 'doc_received', $dt['label'] . ' received.'); $okMsg = 'Marked as received.';
            }
            break;

        case 'save_projects':
            if (!in_array($h['status'], HIRE_OPEN, true)) { $err = 'Projects of a joined person are managed in the project itself.'; break; }
            $valid = array_map('intval', array_column(dbRows("SELECT id FROM client_projects WHERE status NOT IN ('delivered','closed','cancelled')"), 'id'));
            $sel = array_values(array_intersect(array_map('intval', (array)($_POST['projects'] ?? [])), $valid));
            $conn->begin_transaction();
            $conn->query("DELETE FROM hire_projects WHERE hire_id = " . (int)$id);
            if ($sel) { $hp = $conn->prepare("INSERT INTO hire_projects (hire_id, project_id) VALUES (?, ?)"); foreach ($sel as $pid) { $hp->bind_param('ii', $id, $pid); $hp->execute(); } }
            $conn->commit();
            hireLog($id, 'projects', count($sel) . ' project(s) selected.'); $okMsg = 'Projects saved.';
            break;

        case 'create_login':      // the person appears in Users & Roles with the same role as in the hire form
            if (!in_array($h['status'], ['approved', 'offer_issued', 'accepted', 'joined'], true)) { $err = 'The owner must approve the hire before a login can be created.'; break; }
            if ($h['user_id']) { $err = 'This person already has a login.'; break; }
            $username = strtolower(trim((string)($_POST['username'] ?? '')));
            if (($err = hireUsernameError($username)) !== null) break;
            if (!isset(HIRE_ROLES[$h['role']])) { $err = 'The role of this hire is not valid.'; break; }
            $conn->begin_transaction();
            try {
                [$uid, $temp] = hireMakeLogin($h, $username);
                $up = $conn->prepare("UPDATE hire_requests SET user_id = ? WHERE id = ? AND user_id IS NULL"); $up->bind_param('ii', $uid, $id); $up->execute();
                if ($up->affected_rows !== 1) throw new RuntimeException('login already linked');
                hireLog($id, 'login_created', 'Login "' . $username . '" created with role ' . (HIRE_ROLES[$h['role']] ?? $h['role']) . '.');
                $conn->commit();
                $_SESSION['temp_password'] = ['name' => $h['full_name'], 'password' => $temp, 'username' => $username];
                $okMsg = 'Login created. Copy the temporary password shown on this page.';
                if (!empty($_POST['send_mail'])) $okMsg .= ' ' . hireSendLoginMail($h, $username, $temp);
            } catch (Throwable $e) { $conn->rollback(); error_log('hire login failed: ' . $e->getMessage()); $err = 'Could not create the login. Nothing was changed.'; }
            break;

        case 'join':
            if ($h['status'] !== 'accepted') { $err = 'The candidate must accept the offer first.'; break; }
            $username = strtolower(trim((string)($_POST['username'] ?? '')));
            $joined = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['joined_on'] ?? '')) && strtotime($_POST['joined_on']) ? $_POST['joined_on'] : null;
            $missing = hireMissingRequired($id);
            if (!$h['user_id'] && ($err = hireUsernameError($username)) !== null) break;      // an existing login needs no username
            if (!$joined || $joined > date('Y-m-d', strtotime('+7 days')) || $joined < date('Y-m-d', strtotime('-90 days'))) $err = 'Choose a valid joining date (up to 90 days back, 7 days ahead).';
            elseif ($missing) $err = 'These required documents are still missing: ' . implode(', ', $missing) . '.';
            elseif (!isset(HIRE_ROLES[$h['role']])) $err = 'The role of this hire is not valid.';
            if ($err) break;

            $conn->begin_transaction();
            try {
                $n = (int)(dbRow("SELECT COALESCE(MAX(CAST(SUBSTRING(employee_code, 9) AS UNSIGNED)), 0) + 1 AS n FROM hire_requests WHERE employee_code LIKE 'BUN-EMP-%' FOR UPDATE")['n'] ?? 1);
                $code = sprintf('BUN-EMP-%03d', $n); $jref = sprintf('BUN/JL/%s/%04d', date('Y'), $id);
                $temp = null;
                if ($h['user_id']) { $uid = (int)$h['user_id']; $np = hireApplySetup($h, $uid); }
                else { [$uid, $temp] = hireMakeLogin($h, $username); $np = (int)(dbRow("SELECT COUNT(*) AS c FROM project_developers WHERE user_id = ?", 'i', [$uid])['c'] ?? 0); }
                $err = hireMove($h, 'joined', ['joined_on' => ['s', $joined], 'employee_code' => ['s', $code], 'joining_ref' => ['s', $jref], 'user_id' => ['i', $uid]]);
                if ($err) throw new RuntimeException($err);
                hireLog($id, 'joined', 'Joined on ' . fmtDate($joined) . ' as ' . $code . '. ' . ($temp !== null ? 'Login "' . $username . '" created' : 'Existing login used') . ($np ? ', on ' . $np . ' project(s)' : '') . '.');
                $conn->commit();
                if ($temp !== null) $_SESSION['temp_password'] = ['name' => $h['full_name'], 'password' => $temp, 'username' => $username];
                $okMsg = $temp !== null ? 'Joined. Login created — copy the temporary password shown on this page.' : 'Joined.';
                if ($temp !== null && !empty($_POST['send_mail'])) $okMsg .= ' ' . hireSendLoginMail($h, $username, $temp);
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('hire join failed: ' . $e->getMessage());
                $err = $err ?: 'Could not complete joining. Nothing was changed — please try again.';
            }
            break;

        default: $err = 'Unknown action.';
    }
    hireDone(!$err, $err ?? $okMsg, $id);
}

// ---------- page data ----------
$docs     = hireDocs($id);
$selected = hireProjects($id);
$selIds   = array_map('intval', array_column($selected, 'id'));
$allProj  = dbRows("SELECT id, name FROM client_projects WHERE status NOT IN ('delivered','closed','cancelled') ORDER BY name");
$log      = dbRows("SELECT l.*, u.name FROM hire_log l LEFT JOIN admin_users u ON u.id = l.user_id WHERE l.hire_id = ? ORDER BY l.id DESC", 'i', [$id]);
$missing  = hireMissingRequired($id);
$tempShown = $_SESSION['temp_password'] ?? null; unset($_SESSION['temp_password']);
$offNames = implode(', ', array_filter(array_map(fn($d) => WEEKDAYS_LONG[(int)$d] ?? null, array_filter(explode(',', (string)$h['weekly_off']), 'strlen'))));
$suggest  = preg_replace('/[^a-z0-9]+/', '.', strtolower(trim((string)$h['full_name']))); $suggest = trim($suggest, '.');
$letterUrl = fn($t) => 'hire-letter.php?id=' . $id . '&type=' . $t;
$aj = fn($act, $label, $cls, $confirm = '', $extra = []) => postButton('hire-view.php?id=' . $id, array_merge(['action' => $act, 'id' => $id], $extra), $label, $cls, $confirm, '', true);

$pageTitle = 'Hire: ' . $h['full_name'];
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<div data-region="hire">

<?php if ($tempShown): ?>
<div class="temp-pass">
    <strong>Login created for <?php echo clean($tempShown['name']); ?> — shown only once:</strong>
    <span>Username: <b><?php echo clean($tempShown['username']); ?></b></span>
    <code><?php echo clean($tempShown['password']); ?></code>
    <span>Send it to them privately. They must choose their own password at first login.</span>
</div>
<?php endif; ?>

<div class="project-head">
    <div>
        <h2><?php echo clean($h['full_name']); ?></h2>
        <div><?php echo hireBadge($h['status']); ?> <span class="td-muted"><?php echo clean($h['position']); ?> · <?php echo clean(HIRE_ROLES[$h['role']] ?? $h['role']); ?> login · made by <?php echo clean($h['creator']); ?></span></div>
    </div>
    <div class="btn-group">
        <a href="hires.php" class="abtn abtn-outline abtn-sm">← All hires</a>
        <?php if (hireEditable($h)): ?><a href="hire-edit.php?id=<?php echo $id; ?>" class="abtn abtn-outline abtn-sm">Edit</a><?php endif; ?>
        <?php if (hireLetterAllowed($h, 'offer')): ?><a href="<?php echo $letterUrl('offer'); ?>" class="abtn abtn-primary abtn-sm" target="_blank" rel="noopener">Offer letter (PDF)</a><?php endif; ?>
        <?php if (hireLetterAllowed($h, 'joining')): ?><a href="<?php echo $letterUrl('joining'); ?>" class="abtn abtn-primary abtn-sm" target="_blank" rel="noopener">Joining letter (PDF)</a><?php endif; ?>
    </div>
</div>

<?php if (in_array($h['status'], ['approved', 'offer_issued', 'accepted'], true) && !$h['user_id']): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>Add to Users now</h2></div>
    <div class="card-body">
        <p style="margin-top:0;">Create the team login now, with the same role (<b><?php echo clean(HIRE_ROLES[$h['role']] ?? $h['role']); ?></b>), work timing and projects as in this form. A temporary password is shown once; the person must change it at first login. You can disable the login in Users &amp; Roles at any time.</p>
        <form method="POST" action="hire-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="create_login">
            <div class="form-grid"><div class="form-group"><label>Login username</label><input type="text" name="username" required maxlength="60" autocapitalize="none" value="<?php echo clean($suggest); ?>"></div></div>
            <?php if (filter_var(trim((string)$h['email']), FILTER_VALIDATE_EMAIL)): ?><label class="chk-line"><input type="checkbox" name="send_mail" value="1" checked> E-mail the offer letter (PDF) + username + password to <?php echo clean($h['email']); ?></label>
            <?php else: ?><p class="hint">No valid e-mail on this hire, so nothing can be e-mailed. Edit the hire to add one.</p><?php endif; ?>
            <div class="form-actions" style="margin-top:12px;"><button class="abtn abtn-primary" type="submit" data-confirm="Create the login for <?php echo clean($h['full_name']); ?>?">Create login</button></div>
        </form>
    </div>
</div>
<?php elseif ($h['user_id'] && $h['status'] !== 'joined'): ?>
<div class="flash" style="background:#E8F5EE;border-left:4px solid #2D7A52;color:#1e5a3b;margin-bottom:20px;">Login already created — <?php echo can('users') ? '<a href="users.php" style="color:inherit;font-weight:600;">see in Users &amp; Roles</a>' : 'the owner can see it in Users &amp; Roles'; ?>.</div>
<?php endif; ?>

<?php // ---------- what happens next ---------- ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h2>Next step</h2></div>
    <div class="card-body">
    <?php switch ($h['status']):
        case 'draft': ?>
            <p>This is still a draft. Check the details, then send it to the owner.</p>
            <div class="btn-group"><?php if (hireOwns($h)) { echo $aj('submit', 'Send to owner for approval', 'abtn abtn-primary'); echo $aj('cancel', 'Cancel request', 'abtn abtn-danger abtn-sm', 'Cancel this hire request?'); } else echo '<span class="td-muted">Only ' . clean($h['creator']) . ' or the owner can send it.</span>'; ?></div>
        <?php break;
        case 'pending': ?>
            <?php if (can('hiring.approve')): ?>
                <p><b>Your decision is needed.</b> Check salary, joining date and role below, then approve or reject.</p>
                <form method="POST" action="hire-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="form-group"><label>Note <span class="hint">(needed when rejecting)</span></label><input type="text" name="note" maxlength="500"></div>
                    <div class="btn-group" style="margin-top:12px;"><button class="abtn abtn-primary" name="action" value="approve" type="submit" data-confirm="Approve this hire? Salary: <?php echo clean(inr($h['salary_monthly'])); ?> per month.">Approve</button>
                        <button class="abtn abtn-danger" name="action" value="reject" type="submit">Reject</button></div>
                </form>
            <?php else: ?>
                <p>Waiting for the owner to approve. You will see the decision here.</p>
                <?php if (hireOwns($h)) echo $aj('withdraw', 'Take back to draft', 'abtn abtn-outline abtn-sm'); ?>
            <?php endif; ?>
        <?php break;
        case 'rejected': ?>
            <p><b>Rejected by <?php echo clean((string)$h['decider']); ?>:</b> <?php echo clean((string)$h['decision_note']); ?></p>
            <div class="btn-group"><?php if (hireOwns($h)) { echo '<a class="abtn abtn-primary" href="hire-edit.php?id=' . $id . '">Edit and send again</a>'; echo $aj('cancel', 'Cancel request', 'abtn abtn-danger abtn-sm', 'Cancel this hire request?'); } ?></div>
        <?php break;
        case 'approved': ?>
            <p>Approved<?php echo $h['decider'] ? ' by ' . clean($h['decider']) : ''; ?>. Download the offer letter, give it to the candidate, then mark it as issued. Tick the documents below as you receive them.</p>
            <div class="btn-group"><a href="<?php echo $letterUrl('offer'); ?>" class="abtn abtn-outline" target="_blank" rel="noopener">Download offer letter</a>
                <?php echo $aj('issue_offer', 'Mark offer as issued', 'abtn abtn-primary', 'The offer letter will be dated today. Continue?'); ?>
                <?php if (currentRole() === 'owner') echo $aj('cancel', 'Cancel hire', 'abtn abtn-danger abtn-sm', 'Cancel this approved hire?'); ?></div>
        <?php break;
        case 'offer_issued': ?>
            <p>Offer issued on <b><?php echo clean(fmtDate($h['offer_date'])); ?></b>. What did the candidate say?</p>
            <form method="POST" action="hire-view.php?id=<?php echo $id; ?>" data-ajax class="decide"><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="form-group"><label>Accepted on</label><input type="date" name="accepted_on" value="<?php echo $today; ?>" max="<?php echo $today; ?>"></div>
                <div class="btn-group" style="margin-top:12px;"><button class="abtn abtn-primary" name="action" value="accept" type="submit">Candidate accepted</button>
                    <button class="abtn abtn-outline" name="action" value="decline" type="submit" data-confirm="Mark the offer as declined?">Candidate declined</button></div>
            </form>
        <?php break;
        case 'accepted': ?>
            <p>Offer accepted<?php echo $h['accepted_on'] ? ' on ' . clean(fmtDate($h['accepted_on'])) : ''; ?>. On the day the person joins, mark them as joined — this creates their login, work timing and project access, and unlocks the joining letter.</p>
            <?php if ($missing): ?><div class="flash flash-error" style="background:#FFF6E5;border-left:4px solid #E6A23C;color:#7a5200;">Still missing: <?php echo clean(implode(', ', $missing)); ?>. Tick them in the checklist below first.</div><?php endif; ?>
            <form method="POST" action="hire-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="join">
                <div class="form-grid">
                    <div class="form-group"><label>Date of joining</label><input type="date" name="joined_on" required value="<?php echo clean((string)($h['joining_date'] ?: $today)); ?>"></div>
                    <?php if (!$h['user_id']): ?><div class="form-group"><label>Login username</label><input type="text" name="username" required maxlength="60" autocapitalize="none" value="<?php echo clean($suggest); ?>"></div>
                    <?php else: ?><div class="form-group"><label>Login</label><div class="td-muted" style="padding-top:10px;">Already created — it will be used.</div></div><?php endif; ?>
                </div>
                <?php if (!$h['user_id']): ?><?php if (filter_var(trim((string)$h['email']), FILTER_VALIDATE_EMAIL)): ?><label class="chk-line"><input type="checkbox" name="send_mail" value="1" checked> E-mail the offer letter (PDF) + username + password to <?php echo clean($h['email']); ?></label>
            <?php else: ?><p class="hint">No valid e-mail on this hire, so nothing can be e-mailed. Edit the hire to add one.</p><?php endif; ?><?php endif; ?>
                <div class="btn-group" style="margin-top:14px;"><button class="abtn abtn-primary" type="submit" <?php echo $missing ? 'disabled' : ''; ?> data-confirm="Create the login and mark <?php echo clean($h['full_name']); ?> as joined?">Mark as joined &amp; create login</button>
                    <?php if (currentRole() === 'owner') echo $aj('cancel', 'Cancel hire', 'abtn abtn-danger abtn-sm', 'Cancel this hire?'); ?></div>
            </form>
        <?php break;
        case 'joined': ?>
            <p>Joined on <b><?php echo clean(fmtDate($h['joined_on'])); ?></b> · Employee code <b><?php echo clean((string)$h['employee_code']); ?></b>. Login, work timing and project access were created.
               <?php if (can('users') && $h['user_id']): ?><a href="users.php" style="color:var(--a-blue);">See in Users &amp; Roles</a><?php endif; ?></p>
        <?php break;
        default: ?><p class="td-muted">This request is closed (<?php echo clean(HIRE_STATUSES[$h['status']][0] ?? $h['status']); ?>).</p>
    <?php endswitch; ?>
    </div>
</div>

<div class="detail-grid">
<div>
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2>Details</h2></div>
        <div class="card-body"><dl class="facts">
            <div><dt>Phone</dt><dd><?php echo clean((string)($h['phone'] ?: '—')); ?></dd></div>
            <div><dt>Email</dt><dd><?php echo clean((string)($h['email'] ?: '—')); ?></dd></div>
            <div><dt>Date of birth</dt><dd><?php echo clean(fmtDate($h['date_of_birth'])); ?></dd></div>
            <div><dt>Qualification</dt><dd><?php echo clean((string)($h['qualification'] ?: '—')); ?></dd></div>
            <div><dt>Experience</dt><dd><?php echo clean((string)($h['experience'] ?: '—')); ?></dd></div>
            <div><dt>Skills</dt><dd><?php echo clean((string)($h['skills'] ?: '—')); ?></dd></div>
            <div style="grid-column:1/-1;"><dt>Address</dt><dd><?php echo clean((string)($h['address'] ?: '—')); ?></dd></div>
            <div><dt>Department</dt><dd><?php echo clean((string)($h['department'] ?: '—')); ?></dd></div>
            <div><dt>Type</dt><dd><?php echo clean(HIRE_TYPES[$h['employment_type']] ?? ''); ?></dd></div>
            <div><dt>Reports to</dt><dd><?php echo clean((string)($h['reports_name'] ?: '—')); ?></dd></div>
            <div><dt>Place of work</dt><dd><?php echo clean((string)($h['work_location'] ?: '—')); ?></dd></div>
            <div><dt>Salary (per month)</dt><dd><?php echo clean(inr($h['salary_monthly'])); ?></dd></div>
            <div><dt>Joining date</dt><dd><?php echo clean(fmtDate($h['joining_date'])); ?></dd></div>
            <div><dt>Probation / notice</dt><dd><?php echo (int)$h['probation_months']; ?> months / <?php echo (int)$h['notice_days']; ?> days</dd></div>
            <div><dt>Work timing</dt><dd><?php echo clean(attHm($h['work_start']) . ' – ' . attHm($h['work_end'])); ?></dd></div>
            <div><dt>Lunch / weekly off</dt><dd><?php echo clean(attHm($h['lunch_start']) . ' – ' . attHm($h['lunch_end'])); ?> · <?php echo clean($offNames ?: '—'); ?></dd></div>
            <?php if ($h['offer_ref']): ?><div><dt>Offer ref</dt><dd><?php echo clean($h['offer_ref']); ?></dd></div><?php endif; ?>
            <?php if ($h['joining_ref']): ?><div><dt>Joining ref</dt><dd><?php echo clean($h['joining_ref']); ?></dd></div><?php endif; ?>
        </dl></div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2>Joining documents <span class="td-muted">(<?php echo count(array_filter($docs, fn($d) => $d['received_on'])); ?>/<?php echo count($docs); ?>)</span></h2></div>
        <div style="overflow-x:auto;"><table class="admin-table table-cards"><thead><tr><th>Document</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($docs as $d): ?>
            <tr><td data-label="Document" class="td-title"><?php echo clean($d['label']); ?><?php echo (int)$d['is_required'] ? ' <span class="td-muted">· required</span>' : ''; ?></td>
                <td data-label="Status"><?php echo $d['received_on'] ? '<span class="abadge abadge-green">Received ' . clean(fmtDate($d['received_on'])) . '</span>' : '<span class="abadge abadge-grey">Not yet</span>'; ?></td>
                <td data-label=""><?php if ($docOpen) echo $aj('toggle_doc', $d['received_on'] ? 'Undo' : 'Mark received', 'abtn abtn-outline abtn-sm', '', ['doc_id' => $d['id']]); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <div class="card-body"><p class="hint" style="margin:0;">Only "received or not" is saved. Do not type Aadhaar, PAN or bank numbers anywhere in this system. Required documents must be received before the person can be marked as joined.<?php echo $docOpen ? '' : ' Ticking opens after the owner approves.'; ?></p></div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><h2>Projects</h2></div>
        <div class="card-body">
        <?php if (in_array($h['status'], HIRE_OPEN, true)): ?>
            <form method="POST" action="hire-view.php?id=<?php echo $id; ?>" data-ajax><?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="action" value="save_projects">
                <?php if (!$allProj): ?><p class="hint">No running projects.</p><?php endif; ?>
                <div class="check-list"><?php foreach ($allProj as $p): ?><label><input type="checkbox" name="projects[]" value="<?php echo (int)$p['id']; ?>" <?php echo in_array((int)$p['id'], $selIds, true) ? 'checked' : ''; ?>> <?php echo clean($p['name']); ?></label><?php endforeach; ?></div>
                <p class="hint" style="margin:10px 0;">One person can work on many projects, and a project can have many developers. These are linked when the person joins.</p>
                <button class="abtn abtn-outline abtn-sm" type="submit">Save projects</button>
            </form>
        <?php elseif ($selected): ?>
            <div class="check-list"><?php foreach ($selected as $p): ?><a class="td-title" href="project-view.php?id=<?php echo (int)$p['id']; ?>" style="padding:9px 12px;border:1px solid var(--a-border);border-radius:8px;"><?php echo clean($p['name']); ?></a><?php endforeach; ?></div>
            <?php if ($h['status'] === 'joined'): ?><p class="hint" style="margin-top:10px;">Change a joined person's projects from the project page (Edit project → Developers).</p><?php endif; ?>
        <?php else: ?><p class="td-muted" style="margin:0;">No projects selected.</p><?php endif; ?>
        </div>
    </div>
</div>

<div>
    <div class="card">
        <div class="card-header"><h2>History</h2></div>
        <div class="card-body"><ul class="timeline">
        <?php foreach ($log as $l): ?>
            <li><div><b><?php echo clean(ucfirst(str_replace('_', ' ', $l['action']))); ?></b></div>
                <div class="tl-meta"><?php echo clean((string)($l['name'] ?: 'System')); ?> · <?php echo clean(date('d M Y, g:i A', strtotime($l['created_at']))); ?></div>
                <?php if ($l['note']): ?><p class="tl-note"><?php echo clean($l['note']); ?></p><?php endif; ?></li>
        <?php endforeach; ?>
        </ul></div>
    </div>
</div>
</div>

</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
