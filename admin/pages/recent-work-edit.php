<?php
// ==========================================================
// Buildupnet Admin — Recent Work Add / Edit
// Place at: /admin/pages/recent-work-edit.php
// ==========================================================
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = null;

if ($id) {
    $st = $conn->prepare("SELECT * FROM portfolio_items WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
    $item = $st->get_result()->fetch_assoc();
    if (!$item) { header('Location: recent-work.php'); exit; }
}

$pageTitle = $id ? 'Edit Project' : 'Add New Project';

$categories = [
    'Web Development', 'Ecommerce', 'Education', 'Healthcare',
    'Travel & Tourism', 'Agriculture', 'Finance', 'Logistics',
    'Professional Services', 'Real Estate', 'App Development', 'Other'
];

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $live_url    = trim($_POST['live_url'] ?? '');
    $sort_order  = (int)($_POST['sort_order'] ?? 0);
    $status      = ($_POST['status'] ?? '') === 'active' ? 'active' : 'hidden';
    if ($live_url !== '' && safeUrl($live_url) === '') {   // only http(s) links are allowed (blocks javascript: URLs)
        $live_url = '';
        flashMessage('error', 'Live URL must start with http:// or https:// — it was not saved.');
    }
    if (!in_array($category, $categories, true)) $category = 'Other';

    // Image upload
    $image = $item['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/img/projects/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $uploaded = uploadImage($_FILES['image'], $uploadDir);
        if ($uploaded) {
            // Delete old uploaded image if exists
            if ($item) deleteProjectImage($item['image']);
            $image = 'assets/img/projects/' . $uploaded;
        }
    }

    if (!$title) {
        flashMessage('error', 'Project title is required.');
    } else {
        if ($id) {
            $stmt = $conn->prepare("UPDATE portfolio_items SET title=?, category=?, description=?, image=?, live_url=?, sort_order=?, status=? WHERE id=?");
            $stmt->bind_param('sssssisi', $title, $category, $description, $image, $live_url, $sort_order, $status, $id);
        } else {
            // Auto sort_order = last + 1
            $maxOrder = (int)($conn->query("SELECT MAX(sort_order) as m FROM portfolio_items")->fetch_assoc()['m'] ?? 0);
            $sort_order = $sort_order ?: ($maxOrder + 1);
            $stmt = $conn->prepare("INSERT INTO portfolio_items (title, category, description, image, live_url, sort_order, status) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssis', $title, $category, $description, $image, $live_url, $sort_order, $status);
        }

        try {
            $stmt->execute();
            flashMessage('success', $id ? 'Project updated successfully.' : 'Project added successfully.');
            header('Location: recent-work.php'); exit;
        } catch (mysqli_sql_exception $ex) {
            error_log('project save failed: ' . $ex->getMessage());
            flashMessage('error', 'Could not save the project. Please try again.');
        }
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2><?php echo $pageTitle; ?></h2>
        <a href="recent-work.php" class="abtn abtn-outline abtn-sm">← Back to List</a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="form-grid">

                <div class="form-group full-width">
                    <label>Project Title *</label>
                    <input type="text" name="title" required
                           placeholder="e.g. Smart Panda Travels"
                           value="<?php echo clean($item['title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>"
                            <?php echo ($item['category'] ?? 'Web Development') === $cat ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active"  <?php echo ($item['status'] ?? 'active') === 'active'  ? 'selected' : ''; ?>>Active (visible on site)</option>
                        <option value="hidden"  <?php echo ($item['status'] ?? '') === 'hidden'  ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Live Website URL</label>
                    <input type="url" name="live_url"
                           placeholder="https://example.com"
                           value="<?php echo clean($item['live_url'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Sort Order <span style="color:var(--a-muted); font-weight:400;">(lower = first)</span></label>
                    <input type="number" name="sort_order" min="0"
                           value="<?php echo (int)($item['sort_order'] ?? 0); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Short Description</label>
                    <textarea name="description" rows="3"
                              placeholder="One or two lines describing the project..."><?php echo clean($item['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Project Thumbnail Image <span style="color:var(--a-muted); font-weight:400;">(JPG/PNG/WebP, min 800×600px, max 5MB)</span></label>

                    <?php if (!empty($item['image'])): ?>
                    <div style="margin-bottom:12px;">
                        <img src="<?php echo '../../' . clean($item['image']); ?>"
                             alt="Current thumbnail"
                             style="max-width:240px; height:160px; object-fit:cover; border-radius:8px; border:1px solid var(--a-border);"
                             onerror="this.style.display='none'">
                        <p class="hint" style="margin-top:6px;">Current image. Upload a new one to replace it.</p>
                    </div>
                    <?php endif; ?>

                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <span class="hint">
                        Upload a screenshot of the live website. If the project image already exists in
                        <code>/assets/img/projects/</code> (e.g. from the old static version), no need to re-upload —
                        just leave this blank.
                    </span>
                </div>

            </div>

            <div class="form-actions" style="margin-top:24px;">
                <button type="submit" class="abtn abtn-primary">
                    <?php echo $id ? 'Update Project' : 'Add Project'; ?>
                </button>
                <a href="recent-work.php" class="abtn abtn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
