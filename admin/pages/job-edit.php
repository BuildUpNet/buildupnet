<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$job = null;
if ($id) {
    $st = $conn->prepare("SELECT * FROM job_openings WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
    $job = $st->get_result()->fetch_assoc();
    if (!$job) { header('Location: jobs.php'); exit; }
}

$pageTitle = $id ? 'Edit Job Opening' : 'New Job Opening';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $title       = trim($_POST['title'] ?? '');
    $type        = trim($_POST['type'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements= trim($_POST['requirements'] ?? '');
    $status      = ($_POST['status'] ?? '') === 'active' ? 'active' : 'closed';
    $types       = ['Full-time','Part-time','Remote','Freelance','Internship'];
    if (!in_array($type, $types, true)) $type = 'Full-time';
    $title = mb_substr($title, 0, 160); $location = mb_substr($location, 0, 160);

    if ($id) {
        $stmt = $conn->prepare("UPDATE job_openings SET title=?,type=?,location=?,description=?,requirements=?,status=? WHERE id=?");
        $stmt->bind_param('ssssssi', $title, $type, $location, $description, $requirements, $status, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO job_openings (title,type,location,description,requirements,status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssssss', $title, $type, $location, $description, $requirements, $status);
    }

    if ($stmt->execute()) {
        flashMessage('success', $id ? 'Job updated.' : 'Job created.');
        header('Location: jobs.php'); exit;
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<div class="card">
    <div class="card-header">
        <h2><?php echo $pageTitle; ?></h2>
        <a href="jobs.php" class="abtn abtn-outline abtn-sm">← Back</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Job Title *</label>
                    <input type="text" name="title" required value="<?php echo clean($job['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Employment Type</label>
                    <select name="type">
                        <?php foreach (['Full-time','Part-time','Remote','Freelance','Internship'] as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo ($job['type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. Sangrur, Punjab (On-site)" value="<?php echo clean($job['location'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?php echo ($job['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="closed" <?php echo ($job['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Job Description *</label>
                    <textarea name="description" rows="5" required><?php echo clean($job['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Requirements / Skills (one per line)</label>
                    <textarea name="requirements" rows="6" placeholder="e.g.&#10;2+ years PHP experience&#10;Knowledge of MySQL&#10;Good communication skills"><?php echo clean($job['requirements'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="form-actions" style="margin-top:20px;">
                <button type="submit" class="abtn abtn-primary"><?php echo $id ? 'Update Job' : 'Create Job'; ?></button>
                <a href="jobs.php" class="abtn abtn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
