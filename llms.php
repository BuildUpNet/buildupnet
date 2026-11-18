<?php
// /llms.txt — a plain-text summary of the site for AI tools (ChatGPT, Gemini, Perplexity, Claude).
// Built from the database, so services and posts stay current. Rewrite rule: llms.txt -> llms.php (.htaccess).
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Robots-Tag: noindex');
$o = [];
$o[] = '# ' . SITE_NAME;
$o[] = '';
$o[] = '> ' . SITE_NAME . ' is a web design, app development, SEO and IT services company based in Sangrur, Punjab, India, serving businesses across India.';
$o[] = '';
$o[] = '## Main pages';
foreach ([['Home', ''], ['Services', '/services.php'], ['Our work', '/portfolio.php'], ['About and team', '/about.php'], ['Blog', '/blog.php'], ['Contact', '/contact.php'], ['Careers', '/careers.php']] as $p) {
    $o[] = '- [' . $p[0] . '](' . SITE_URL . $p[1] . ')';
}
$o[] = '';
$o[] = '## Services';
try {
    foreach (dbRows("SELECT slug, nav_label, COALESCE(NULLIF(meta_description, ''), intro) AS d FROM service_pages WHERE status = 'published' ORDER BY sort_order, id") as $r) {
        $d = trim(preg_replace('/\s+/', ' ', (string)$r['d']));
        if (mb_strlen($d) > 220) $d = rtrim(mb_substr($d, 0, 217)) . '...';
        $o[] = '- [' . $r['nav_label'] . '](' . SITE_URL . '/services/' . rawurlencode($r['slug']) . '): ' . $d;
    }
} catch (Throwable $e) { $o[] = '- [All services](' . SITE_URL . '/services.php)'; }
$o[] = '';
$o[] = '## Latest articles';
try {
    foreach (dbRows("SELECT slug, title FROM blog_posts WHERE status = 'published'" . (blogHasSeoColumns() ? ' AND noindex = 0' : '') . " ORDER BY created_at DESC, id DESC LIMIT 10") as $r) {
        $o[] = '- [' . str_replace(['[', ']'], '', (string)$r['title']) . '](' . SITE_URL . '/blog/' . rawurlencode($r['slug']) . ')';
    }
} catch (Throwable $e) { }
$o[] = '';
$o[] = '## Contact';
$o[] = '- ' . SITE_URL . '/contact.php';
echo implode("\n", $o) . "\n";
