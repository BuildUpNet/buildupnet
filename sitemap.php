<?php
// XML sitemap generated from the database: main pages + every published blog post.
// <lastmod> is given only for blog posts (real dates from the database) — Google ignores inaccurate dates.
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$pages = ['index.php', 'services.php', 'portfolio.php', 'about.php', 'blog.php', 'careers.php', 'contact.php', 'privacy-policy.php', 'terms.php'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $file) {
    echo '  <url><loc>' . e($file === 'index.php' ? SITE_URL . '/' : SITE_URL . '/' . $file) . "</loc></url>\n";
}
foreach (dbRows("SELECT slug, created_at, updated_at FROM blog_posts WHERE status = 'published' AND noindex = 0 ORDER BY created_at DESC") as $p) {
    echo '  <url><loc>' . e(postUrl($p['slug'])) . '</loc><lastmod>' . date('Y-m-d', strtotime($p['updated_at'] ?: $p['created_at'])) . "</lastmod></url>\n";
}
echo "</urlset>\n";
