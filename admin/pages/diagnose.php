<?php
// Owner-only health check: shows exactly what is missing on THIS server (PHP extensions, DB columns, folders, file versions)
// and tries a real blog-post insert that is rolled back, so nothing is saved.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('users');

$checks = [];
$add = function (string $name, bool $ok, string $detail = '', string $fix = '') use (&$checks) { $checks[] = [$name, $ok, $detail, $fix]; };

$add('PHP version', version_compare(PHP_VERSION, '8.0', '>='), PHP_VERSION, 'Use PHP 8.1 or newer (hPanel → Advanced → PHP Configuration).');
foreach (['mysqli', 'mbstring', 'dom', 'fileinfo', 'gd', 'libxml'] as $ext) {
    $add("PHP extension: $ext", extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'missing', "hPanel → Advanced → PHP Configuration → Extensions → enable $ext.");
}
$add('GD can write WebP (thumbnails)', function_exists('imagewebp'), function_exists('imagewebp') ? 'yes' : 'no — originals will be saved instead', 'Optional.');
$add('upload_max_filesize / post_max_size', true, ini_get('upload_max_filesize') . ' / ' . ini_get('post_max_size') . ', memory_limit ' . ini_get('memory_limit'));

$add('File version: includes/security.php', function_exists('plainText'), function_exists('plainText') ? 'up to date' : 'OLD version', 'Upload includes/security.php from the latest update zip.');
$add('File version: includes/blog.php', function_exists('blogHasSeoColumns'), function_exists('blogHasSeoColumns') ? 'up to date' : 'OLD version', 'Upload includes/blog.php from the latest update zip.');

$cols = array_column(dbRows("SHOW COLUMNS FROM blog_posts"), 'Field');
$need = ['title', 'slug', 'excerpt', 'category', 'author', 'read_time', 'content', 'thumbnail', 'status', 'meta_title', 'meta_description', 'focus_keyword', 'noindex'];
$missing = array_diff($need, $cols);
$add('blog_posts table columns', !$missing, $missing ? 'missing: ' . implode(', ', $missing) : 'all present', 'Run upgrade-blog-seo.sql in phpMyAdmin (SQL tab).');
$coll = dbRow("SELECT TABLE_COLLATION c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog_posts'")['c'] ?? '?';
$add('blog_posts character set', str_starts_with($coll, 'utf8mb4'), $coll, 'Run upgrade-blog-seo.sql (converts to utf8mb4 so emoji work).');

foreach (['assets/img/blog', 'assets/img/projects'] as $dir) {
    $path = __DIR__ . '/../../' . $dir;
    if (!is_dir($path)) @mkdir($path, 0755, true);
    $add("Folder writable: $dir", is_dir($path) && is_writable($path), is_dir($path) ? (is_writable($path) ? 'ok' : 'not writable') : 'missing', 'File Manager → folder permissions 755.');
}

// Real insert, rolled back
$insert = 'not tried';
$ok = false;
try {
    $conn->begin_transaction();
    $t = 'diagnose-' . bin2hex(random_bytes(4));
    $cols2 = in_array('meta_title', $cols, true)
        ? "(title,slug,excerpt,category,author,read_time,content,status,thumbnail,meta_title,meta_description,focus_keyword,noindex) VALUES (?,?,'ex','Business Tips','','3 min','<p>✅ test 🚀</p>','draft','',?, 'd','k',0)"
        : "(title,slug,excerpt,category,author,read_time,content,status,thumbnail) VALUES (?,?,'ex','Business Tips','','3 min','<p>✅ test 🚀</p>','draft','')";
    $st = in_array('meta_title', $cols, true) ? $conn->prepare("INSERT INTO blog_posts $cols2") : $conn->prepare("INSERT INTO blog_posts $cols2");
    if (in_array('meta_title', $cols, true)) $st->bind_param('sss', $t, $t, $t); else $st->bind_param('ss', $t, $t);
    $st->execute();
    $conn->rollback();
    $ok = true; $insert = 'works (rolled back, nothing saved)';
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $e2) {}
    $insert = $e->getMessage();
}
$add('Test insert of a blog post (with emoji)', $ok, $insert, 'Send this message to your developer / Claude.');

$pageTitle = 'Server check';
include __DIR__ . '/../includes/admin-header.php';
?>
<div class="card">
    <div class="card-header"><h2>Server check</h2></div>
    <div style="overflow-x:auto;">
    <table class="admin-table table-cards">
        <thead><tr><th>Check</th><th>Result</th><th>Details / what to do</th></tr></thead>
        <tbody>
        <?php foreach ($checks as [$name, $good, $detail, $fix]): ?>
            <tr>
                <td data-label="Check" class="td-title"><?php echo clean($name); ?></td>
                <td data-label="Result"><span class="abadge <?php echo $good ? 'abadge-green' : 'abadge-red'; ?>"><?php echo $good ? 'OK' : 'PROBLEM'; ?></span></td>
                <td data-label="Details" style="text-align:left;"><?php echo clean($detail); ?><?php if (!$good && $fix): ?><div class="td-muted"><b>Fix:</b> <?php echo clean($fix); ?></div><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
