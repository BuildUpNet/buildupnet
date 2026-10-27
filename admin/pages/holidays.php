<?php
// Office holidays (apply to everyone). Owner & manager.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('attendance.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    if (($_POST['action'] ?? '') === 'add') {
        $date = attDateOrNull($_POST['holiday_date'] ?? ''); $name = mb_substr(plainText((string)($_POST['name'] ?? '')), 0, 120);
        if (!$date || $name === '') flashMessage('error', 'Enter a valid date and a name.');
        else {
            try { $st = $conn->prepare("INSERT INTO holidays (holiday_date, name) VALUES (?, ?)"); $st->bind_param('ss', $date, $name); $st->execute(); flashMessage('success', 'Holiday added.'); }
            catch (mysqli_sql_exception $e) { flashMessage('error', $e->getCode() === 1062 ? 'There is already a holiday on that date.' : 'Could not save the holiday.'); }
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $id = (int)($_POST['id'] ?? 0); $st = $conn->prepare("DELETE FROM holidays WHERE id = ?"); $st->bind_param('i', $id); $st->execute(); flashMessage('success', 'Holiday removed.');
    }
    header('Location: holidays.php?year=' . (int)($_POST['year'] ?? date('Y'))); exit;
}
$year = (int)($_GET['year'] ?? date('Y')); if ($year < 2020 || $year > 2100) $year = (int)date('Y');
$list = dbRows("SELECT * FROM holidays WHERE holiday_date BETWEEN ? AND ? ORDER BY holiday_date", 'ss', ["$year-01-01", "$year-12-31"]);

$pageTitle = 'Holidays';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<?php echo attTabs('holidays'); ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2>Add a holiday</h2></div>
    <div class="card-body">
        <form method="POST" class="form-grid"><?php echo csrfField(); ?><input type="hidden" name="action" value="add"><input type="hidden" name="year" value="<?php echo $year; ?>">
            <div class="form-group"><label>Date *</label><input type="date" name="holiday_date" required></div>
            <div class="form-group"><label>Name *</label><input type="text" name="name" maxlength="120" required placeholder="e.g. Diwali"></div>
            <div class="form-actions"><button class="abtn abtn-primary" type="submit">Add holiday</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header"><h2>Holidays <?php echo $year; ?></h2><div class="btn-group"><a class="abtn abtn-outline abtn-sm" href="?year=<?php echo $year - 1; ?>">‹ <?php echo $year - 1; ?></a><a class="abtn abtn-outline abtn-sm" href="?year=<?php echo $year + 1; ?>"><?php echo $year + 1; ?> ›</a></div></div>
    <div style="overflow-x:auto;"><table class="admin-table table-cards"><thead><tr><th>Date</th><th>Day</th><th>Holiday</th><th></th></tr></thead><tbody>
    <?php if (!$list): ?><tr><td colspan="4" class="td-muted" style="text-align:center;padding:20px;">No holidays added for <?php echo $year; ?></td></tr><?php endif; ?>
    <?php foreach ($list as $h): ?>
        <tr><td data-label="Date" class="td-title"><?php echo clean(date('j M Y', strtotime($h['holiday_date']))); ?></td><td data-label="Day" class="td-muted"><?php echo date('l', strtotime($h['holiday_date'])); ?></td><td data-label="Holiday"><?php echo clean($h['name']); ?></td>
            <td data-label=""><?php echo postButton('holidays.php', ['action' => 'delete', 'id' => $h['id'], 'year' => $year], 'Remove', 'abtn abtn-danger abtn-sm', 'Remove this holiday?'); ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
