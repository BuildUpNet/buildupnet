<?php
// Owner edits the wording of the Offer / Joining letter. Placeholders like {name} are filled from the hire form.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('hiring.templates');
hireRequireTables();

const LETTER_TAGS = ['name', 'position', 'department', 'employment_type', 'reports_to', 'work_location', 'joining_date', 'joined_date', 'salary', 'probation', 'notice',
                     'work_timing', 'weekly_off', 'valid_until', 'offer_date', 'offer_ref', 'employee_code', 'projects'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $type = (string)($_POST['letter_type'] ?? '');
    $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 120);
    $subject = mb_substr(trim((string)($_POST['subject'] ?? '')), 0, 200);
    $body = mb_substr(str_replace("\r", '', trim((string)($_POST['body'] ?? ''))), 0, 12000);
    $sn = mb_substr(trim((string)($_POST['signatory_name'] ?? '')), 0, 120); $stl = mb_substr(trim((string)($_POST['signatory_title'] ?? '')), 0, 120);
    $all = $title . $subject . $body . $sn . $stl;
    preg_match_all('/\{([a-z_]*)\}/', $all, $m); $badTags = array_values(array_unique(array_diff($m[1], LETTER_TAGS)));
    if (!in_array($type, ['offer', 'joining'], true)) $errors[] = 'Unknown letter.';
    elseif ($title === '' || $subject === '' || $body === '') $errors[] = 'Title, subject and text cannot be empty.';
    elseif ($badTags) $errors[] = 'Unknown placeholder: {' . implode('}, {', $badTags) . '}. Use only the ones listed below the form.';
    elseif (preg_match('/[^\x{20}-\x{7E}\x{A0}-\x{FF}\x{2013}\x{2014}\x{2018}\x{2019}\x{201C}\x{201D}\x{2022}\n]/u', $all)) $errors[] = 'Use English letters only — the PDF cannot print other scripts.';
    if (!$errors) {
        $uid = currentUserId();
        $st = $conn->prepare("UPDATE letter_templates SET title = ?, subject = ?, body = ?, signatory_name = ?, signatory_title = ?, updated_by_id = ? WHERE letter_type = ?");
        $st->bind_param('sssssis', $title, $subject, $body, $sn, $stl, $uid, $type); $st->execute();
        flashMessage('success', ucfirst($type) . ' letter saved.');
        header('Location: letter-templates.php#' . $type); exit;
    }
}
$tpls = dbRows("SELECT * FROM letter_templates ORDER BY FIELD(letter_type,'offer','joining')");
$pageTitle = 'Letter Templates';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php foreach ($errors as $err): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($err); ?></div><?php endforeach; ?>
<?php renderFlash(); ?>
<div class="card" style="margin-bottom:20px;"><div class="card-body">
    <p style="margin:0 0 8px;"><b>How to write:</b> a blank line starts a new paragraph · <code>## Heading</code> · <code>- bullet point</code> · <code>Label :: value</code> makes a table row.</p>
    <p class="hint" style="margin:0 0 8px;">Placeholders filled automatically: <?php echo clean('{' . implode('} {', LETTER_TAGS) . '}'); ?></p>
    <p class="hint" style="margin:0;">⚠ This is general wording, not legal advice. Have your lawyer or CA read it once before you use it (notice period, probation, PF/ESI and similar clauses depend on your situation).</p>
</div></div>
<?php foreach ($tpls as $t): $posted = $errors && ($_POST['letter_type'] ?? '') === $t['letter_type']; $val = fn($k) => clean((string)($posted ? ($_POST[$k] ?? '') : $t[$k])); ?>
<div class="card" id="<?php echo clean($t['letter_type']); ?>" style="margin-bottom:20px;">
    <div class="card-header"><h2><?php echo $t['letter_type'] === 'offer' ? 'Offer letter' : 'Joining letter'; ?></h2></div>
    <div class="card-body"><form method="POST"><?php echo csrfField(); ?><input type="hidden" name="letter_type" value="<?php echo clean($t['letter_type']); ?>">
        <div class="form-grid">
            <div class="form-group"><label>Title on the letter</label><input type="text" name="title" maxlength="120" required value="<?php echo $val('title'); ?>"></div>
            <div class="form-group"><label>Subject line</label><input type="text" name="subject" maxlength="200" required value="<?php echo $val('subject'); ?>"></div>
            <div class="form-group"><label>Signed by (name)</label><input type="text" name="signatory_name" maxlength="120" value="<?php echo $val('signatory_name'); ?>"></div>
            <div class="form-group"><label>Signed by (title)</label><input type="text" name="signatory_title" maxlength="120" value="<?php echo $val('signatory_title'); ?>"></div>
            <div class="form-group full-width"><label>Text</label><textarea name="body" rows="22" required maxlength="12000" style="font-family:'JetBrains Mono',monospace;font-size:.85rem;"><?php echo $val('body'); ?></textarea></div>
        </div>
        <div class="form-actions" style="margin-top:14px;"><button class="abtn abtn-primary" type="submit">Save <?php echo clean($t['letter_type']); ?> letter</button></div>
    </form></div>
</div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
