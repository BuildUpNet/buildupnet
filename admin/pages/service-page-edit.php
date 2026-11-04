<?php
// ==========================================================
// Buildupnet Admin — Add / edit one service page
// Place at: /admin/pages/service-page-edit.php
// ==========================================================
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('content');

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item = null;
try {
    if ($id) {
        $st = $conn->prepare("SELECT * FROM service_pages WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
        $item = $st->get_result()->fetch_assoc();
        if (!$item) { header('Location: service-pages.php'); exit; }
    } else {
        $conn->query("SELECT 1 FROM service_pages LIMIT 1");
    }
} catch (mysqli_sql_exception $e) {
    flashMessage('error', 'Service pages are not set up yet. Run upgrade-service-pages.sql first.');
    header('Location: service-pages.php'); exit;
}

$pageTitle = $id ? 'Edit Service Page' : 'Add Service Page';
$f = [   // values shown in the form
    'slug' => $item['slug'] ?? '', 'nav_label' => $item['nav_label'] ?? '', 'title' => $item['title'] ?? '',
    'meta_title' => $item['meta_title'] ?? '', 'meta_description' => $item['meta_description'] ?? '', 'intro' => $item['intro'] ?? '',
    'deliverables' => $item['deliverables'] ?? '', 'approach' => $item['approach'] ?? '', 'faq' => $item['faq'] ?? '',
    'blog_category' => $item['blog_category'] ?? '', 'work_keywords' => $item['work_keywords'] ?? '', 'status' => $item['status'] ?? 'published',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    foreach (['nav_label','title','meta_title','meta_description','intro','deliverables','approach','faq','blog_category','work_keywords'] as $k) $f[$k] = trim((string)($_POST[$k] ?? ''));
    if (!$id) $f['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $f['status'] = ($_POST['status'] ?? '') === 'hidden' ? 'hidden' : 'published';

    $errors = [];
    if (!$id && !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $f['slug'])) $errors[] = 'Address: use only small letters, numbers and single hyphens, e.g. web-development.';
    if (!$id && strlen($f['slug']) > 80) $errors[] = 'Address is too long (80 characters at most).';
    if ($f['nav_label'] === '' || mb_strlen($f['nav_label']) > 60) $errors[] = 'Short name is required (60 characters at most).';
    if ($f['title'] === '' || mb_strlen($f['title']) > 160) $errors[] = 'Heading is required (160 characters at most).';
    if (mb_strlen($f['meta_title']) > 120) $errors[] = 'Google title is too long (120 characters at most).';
    if (mb_strlen($f['meta_description']) > 320) $errors[] = 'Google description is too long (320 characters at most).';
    if (mb_strlen($f['work_keywords']) > 200) $errors[] = 'Work keywords are too long (200 characters at most).';
    if ($f['blog_category'] !== '' && !isset(BLOG_CATEGORIES[$f['blog_category']])) $errors[] = 'Pick a blog category from the list.';
    foreach (['intro' => 'Introduction', 'deliverables' => 'What you get', 'approach' => 'How we work', 'faq' => 'Questions'] as $k => $label) {
        if (mb_strlen($f[$k]) > 6000) $errors[] = $label . ' is too long (6000 characters at most).';
    }

    if (!$errors) {
        try {
            if ($id) {
                $st = $conn->prepare("UPDATE service_pages SET nav_label=?, title=?, meta_title=?, meta_description=?, intro=?, deliverables=?, approach=?, faq=?, blog_category=?, work_keywords=?, status=? WHERE id=?");
                $st->bind_param('sssssssssssi', $f['nav_label'], $f['title'], $f['meta_title'], $f['meta_description'], $f['intro'], $f['deliverables'], $f['approach'], $f['faq'], $f['blog_category'], $f['work_keywords'], $f['status'], $id);
            } else {
                $max = (int)($conn->query("SELECT MAX(sort_order) AS m FROM service_pages")->fetch_assoc()['m'] ?? 0) + 1;
                $st = $conn->prepare("INSERT INTO service_pages (slug, nav_label, title, meta_title, meta_description, intro, deliverables, approach, faq, blog_category, work_keywords, sort_order, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $st->bind_param('sssssssssssis', $f['slug'], $f['nav_label'], $f['title'], $f['meta_title'], $f['meta_description'], $f['intro'], $f['deliverables'], $f['approach'], $f['faq'], $f['blog_category'], $f['work_keywords'], $max, $f['status']);
            }
            $st->execute();
            flashMessage('success', $id ? 'Service page updated.' : 'Service page added.');
            header('Location: service-pages.php'); exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) flashMessage('error', 'A page with this address already exists.');
            else { error_log('service page save failed: ' . $ex->getMessage()); flashMessage('error', 'Could not save the page. Please try again.'); }
        }
    } else {
        flashMessage('error', implode(' ', $errors));
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2><?php echo $pageTitle; ?></h2>
        <a href="service-pages.php" class="abtn abtn-outline abtn-sm">← Back to List</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="form-grid">

                <div class="form-group">
                    <label>Address *</label>
                    <?php if ($id): ?>
                        <input type="text" value="/services/<?php echo clean($f['slug']); ?>" disabled>
                        <span class="hint">The address cannot be changed after the page is created (links and Google already know it).</span>
                    <?php else: ?>
                        <input type="text" name="slug" required maxlength="80" placeholder="web-development" autocapitalize="none" value="<?php echo clean($f['slug']); ?>">
                        <span class="hint">Becomes <code>/services/web-development</code>. Small letters, numbers and hyphens only.</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="published" <?php echo $f['status'] === 'published' ? 'selected' : ''; ?>>Published (visible on site)</option>
                        <option value="hidden" <?php echo $f['status'] === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Short name * <span style="color:var(--a-muted); font-weight:400;">(footer, menus, blog boxes)</span></label>
                    <input type="text" name="nav_label" required maxlength="60" placeholder="Web Development" value="<?php echo clean($f['nav_label']); ?>">
                </div>

                <div class="form-group">
                    <label>Blog category <span style="color:var(--a-muted); font-weight:400;">(posts of this category show on the page and link to it)</span></label>
                    <select name="blog_category">
                        <option value="">— none —</option>
                        <?php foreach (array_keys(BLOG_CATEGORIES) as $cat): ?>
                        <option value="<?php echo clean($cat); ?>" <?php echo $f['blog_category'] === $cat ? 'selected' : ''; ?>><?php echo clean($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Heading (H1) *</label>
                    <input type="text" name="title" required maxlength="160" value="<?php echo clean($f['title']); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Introduction</label>
                    <textarea name="intro" rows="3" maxlength="6000"><?php echo clean($f['intro']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>What you get <span style="color:var(--a-muted); font-weight:400;">(one per line: Title :: short text)</span></label>
                    <textarea name="deliverables" rows="7" maxlength="6000" placeholder="Custom design :: A design made for your brand."><?php echo clean($f['deliverables']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>How we work <span style="color:var(--a-muted); font-weight:400;">(one step per line: Title :: short text; shown as numbered steps)</span></label>
                    <textarea name="approach" rows="5" maxlength="6000"><?php echo clean($f['approach']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Questions and answers <span style="color:var(--a-muted); font-weight:400;">(one per line: Question :: Answer; also sent to Google as FAQ)</span></label>
                    <textarea name="faq" rows="7" maxlength="6000"><?php echo clean($f['faq']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Work keywords <span style="color:var(--a-muted); font-weight:400;">(comma separated: Recent Work projects whose category contains one of these words are shown here)</span></label>
                    <input type="text" name="work_keywords" maxlength="200" placeholder="website,web design,portal" value="<?php echo clean($f['work_keywords']); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Title shown in Google</label>
                    <input type="text" name="meta_title" maxlength="120" value="<?php echo clean($f['meta_title']); ?>">
                    <span class="hint">Keep it under 60 characters. Empty = the heading is used.</span>
                </div>

                <div class="form-group full-width">
                    <label>Description shown in Google</label>
                    <textarea name="meta_description" rows="2" maxlength="320"><?php echo clean($f['meta_description']); ?></textarea>
                    <span class="hint">About 150 characters. Empty = the start of the introduction is used.</span>
                </div>
            </div>

            <div class="form-actions" style="margin-top:24px;">
                <button type="submit" class="abtn abtn-primary"><?php echo $id ? 'Update Page' : 'Add Page'; ?></button>
                <a href="service-pages.php" class="abtn abtn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
