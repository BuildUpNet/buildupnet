<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('content');

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;

if ($id) {
    $st = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
    $post = $st->get_result()->fetch_assoc();
    if (!$post) { header('Location: posts.php'); exit; }
}

$pageTitle = $id ? 'Edit Post' : 'New Blog Post';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $title      = trim($_POST['title'] ?? '');
    $slug       = slugify(trim($_POST['slug'] ?? '') ?: $title);
    $excerpt    = trim($_POST['excerpt'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $author     = trim($_POST['author'] ?? '');
    $read_time  = trim($_POST['read_time'] ?? '');
    $content    = $_POST['content'] ?? '';
    $status     = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    if (!isset(BLOG_CATEGORIES[$category])) $category = 'Business Tips';
    if ($slug === '') $slug = 'post-' . date('YmdHis');
    $slug = mb_substr($slug, 0, 190);

    // Thumbnail upload
    $thumbnail = $post['thumbnail'] ?? '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/img/blog/';
        $uploaded  = uploadImage($_FILES['thumbnail'], $uploadDir);
        if ($uploaded) $thumbnail = 'assets/img/blog/' . $uploaded;
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE blog_posts SET title=?,slug=?,excerpt=?,category=?,author=?,read_time=?,content=?,status=?,thumbnail=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param('sssssssssi', $title, $slug, $excerpt, $category, $author, $read_time, $content, $status, $thumbnail, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO blog_posts (title,slug,excerpt,category,author,read_time,content,status,thumbnail) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssss', $title, $slug, $excerpt, $category, $author, $read_time, $content, $status, $thumbnail);
    }

    try {
        $stmt->execute();
        flashMessage('success', $id ? 'Post updated successfully.' : 'Post created successfully.');
        header('Location: posts.php');
        exit;
    } catch (mysqli_sql_exception $ex) {
        error_log('post save failed: ' . $ex->getMessage());
        flashMessage('error', $ex->getCode() === 1062 ? 'Another post already uses this URL slug — change the slug.' : 'Error saving post. Please try again.');
    }
}

include __DIR__ . '/../includes/admin-header.php';
?>

<?php renderFlash(); ?>

<div class="card">
    <div class="card-header">
        <h2><?php echo $pageTitle; ?></h2>
        <a href="posts.php" class="abtn abtn-outline abtn-sm">← Back</a>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Post Title *</label>
                    <input type="text" id="post_title" name="title" required value="<?php echo clean($post['title'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>URL Slug *</label>
                    <input type="text" id="post_slug" name="slug" required value="<?php echo clean($post['slug'] ?? ''); ?>">
                    <span class="hint">Auto-generated from title. Only lowercase letters, numbers, hyphens.</span>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach (array_keys(BLOG_CATEGORIES) as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($post['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <select name="author">
                        <?php $names = array_column(authors(), 'name'); $cur = $post['author'] ?? ($names[0] ?? ''); if ($cur !== '' && !in_array($cur, $names, true)) $names[] = $cur; ?>
                        <?php foreach ($names as $n): ?>
                        <option value="<?php echo clean($n); ?>" <?php echo $cur === $n ? 'selected' : ''; ?>><?php echo clean($n); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Read Time</label>
                    <input type="text" name="read_time" placeholder="e.g. 5 min read" value="<?php echo clean($post['read_time'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" <?php echo ($post['status'] ?? '') !== 'published' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Excerpt (short description for listing) *</label>
                    <textarea name="excerpt" rows="3" required><?php echo clean($post['excerpt'] ?? ''); ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Thumbnail Image (JPG/PNG/WebP, max 5MB)</label>
                    <?php if (!empty($post['thumbnail'])): ?>
                        <p class="hint" style="margin-bottom:8px;">Current: <?php echo clean($post['thumbnail']); ?></p>
                    <?php endif; ?>
                    <input type="file" name="thumbnail" accept="image/*">
                </div>
                <div class="form-group full-width">
                    <label>Post Content (HTML allowed) *</label>
                    <textarea name="content" rows="20" required style="font-family:monospace; font-size:0.85rem;"><?php echo htmlspecialchars($_POST['content'] ?? $post['content'] ?? ''); ?></textarea>
                    <span class="hint">Use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;, &lt;a href&gt; tags. Full HTML supported.</span>
                </div>
            </div>
            <div class="form-actions" style="margin-top:20px;">
                <button type="submit" class="abtn abtn-primary"><?php echo $id ? 'Update Post' : 'Create Post'; ?></button>
                <a href="posts.php" class="abtn abtn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
