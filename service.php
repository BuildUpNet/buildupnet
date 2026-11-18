<?php
// ==========================================================
// Buildupnet — one service page
// Public URL: /services/<slug>  (rewritten to this file by .htaccess)
// Everything on the page comes from the service_pages table (Admin -> Service Pages).
// ==========================================================
require_once __DIR__ . '/includes/config.php';

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$svc  = null;
if (preg_match('/^[a-z0-9-]{1,80}$/', $slug)) {
    try { $svc = dbRow("SELECT * FROM service_pages WHERE slug = ? AND status = 'published' LIMIT 1", 's', [$slug]); }
    catch (Throwable $e) { $svc = null; }       // table not created yet -> behaves like "not found"
}

// Old / unknown address: show the 404 page with a way forward
if (!$svc) {
    http_response_code(404);
    $pageRobots = 'noindex, follow';
    $pageTitle = 'Service Not Found | ' . SITE_NAME;
    $pageDescription = 'This service page could not be found.';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container" style="text-align:center; padding: 80px 0;">
        <h1>Service Not Found</h1>
        <p style="margin: 16px 0 28px;">This page doesn\'t exist or may have moved. See everything we offer instead.</p>
        <a href="' . e(SITE_URL) . '/services.php" class="btn btn-primary">All Services</a>
    </div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$url       = SITE_URL . '/services/' . rawurlencode($svc['slug']);
$deliver   = pairLines($svc['deliverables']);
$steps     = pairLines($svc['approach']);
$faqs      = array_values(array_filter(pairLines($svc['faq']), fn($f) => $f[1] !== ''));
$intro     = trim((string)$svc['intro']);

// Related projects: Work-page projects whose category belongs to this service
$projects = [];
try {
    foreach (dbRows("SELECT title, category, description, image, live_url FROM portfolio_items WHERE status = 'active' ORDER BY sort_order ASC, id ASC") as $it) {
        if (serviceForWork($it['category'])['slug'] === $svc['slug']) $projects[] = $it;
        if (count($projects) >= 3) break;
    }
} catch (Throwable $e) { $projects = []; }

// Related articles: blog posts of this service's category
$articles = [];
if (trim((string)$svc['blog_category']) !== '') {
    try { $articles = relatedPosts((string)$svc['blog_category'], '', 3); } catch (Throwable $e) { $articles = []; }
}

// Other services
$others = array_slice(array_values(array_filter(servicesList(), fn($s) => $s['slug'] !== $svc['slug'])), 0, 3);

// Page head
$pageTitle       = trim((string)$svc['meta_title']) !== '' ? $svc['meta_title'] : $svc['title'];
$pageDescription = trim((string)$svc['meta_description']) !== '' ? $svc['meta_description'] : seoTrim($intro, 155);
$pageKeywords    = (string)($svc['keywords'] ?? '');
$canonicalUrl    = $url;
$breadcrumbTrail = [['Services', SITE_URL . '/services.php'], [$svc['nav_label'], null]];

$schemas = [
    ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Services', 'item' => SITE_URL . '/services.php'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $svc['nav_label'], 'item' => $url],
    ]],
    ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $svc['nav_label'], 'serviceType' => $svc['nav_label'],
     'description' => $pageDescription, 'url' => $url, 'areaServed' => ['@type' => 'Country', 'name' => 'India'],
     'provider' => ['@id' => SITE_URL . '/#organization']],
    // WebPage with speakable parts and a modified date: voice assistants and AI tools can read the key answer aloud / quote it, and see it is current.
    ['@context' => 'https://schema.org', '@type' => 'WebPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => $pageTitle, 'inLanguage' => 'en-IN',
     'isPartOf' => ['@id' => SITE_URL . '/#website'], 'about' => ['@type' => 'Service', 'name' => $svc['nav_label']],
     'dateModified' => date('c', strtotime((string)($svc['updated_at'] ?: ($svc['created_at'] ?? 'now')))),
     'speakable' => ['@type' => 'SpeakableSpecification', 'cssSelector' => ['h1', '.lead', '.faq-answer']]],
];
if ($pageKeywords !== '') $schemas[1]['keywords'] = $pageKeywords;
if ($faqs) {
    $schemas[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => [
        '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)];
}
$headExtra = '';
foreach ($schemas as $sc) $headExtra .= '<script type="application/ld+json">' . ldJson($sc) . '</script>' . "\n";

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <div data-reveal style="max-width: 760px;">
            <span class="eyebrow">Service</span>
            <h1><?php echo e($svc['title']); ?></h1>
            <?php if ($intro !== ''): ?><p class="lead"><?php echo e($intro); ?></p><?php endif; ?>
            <div class="hero-actions">
                <a href="#" data-open-quote class="btn btn-primary">Get a Free Quote</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline">Contact Us</a>
            </div>
            <p class="inline-links"><a class="text-link" href="<?php echo SITE_URL; ?>/services.php">All our services</a> · <a class="text-link" href="<?php echo SITE_URL; ?>/portfolio.php">See our work</a></p>
            <p class="td-updated">Last updated: <time datetime="<?php echo e(date('Y-m-d', strtotime((string)($svc['updated_at'] ?: ($svc['created_at'] ?? 'now'))))); ?>"><?php echo e(date('j F Y', strtotime((string)($svc['updated_at'] ?: ($svc['created_at'] ?? 'now'))))); ?></time></p>
        </div>
    </div>
</section>

<?php if ($deliver): ?>
<!-- ===== What you get ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">What you get</span>
            <h2>What is included in <?php echo e($svc['nav_label']); ?>.</h2>
        </div>
        <div class="services-grid">
            <?php foreach ($deliver as [$t, $d]): ?>
            <div class="service-card" data-reveal>
                <h3><?php echo e($t); ?></h3>
                <?php if ($d !== ''): ?><p><?php echo e($d); ?></p><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($steps): ?>
<!-- ===== How we work ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">How we work</span>
            <h2>A simple, transparent process.</h2>
        </div>
        <div class="process-list" data-reveal>
            <?php foreach ($steps as $i => [$t, $d]): ?>
            <div class="process-step">
                <div class="process-num"><?php echo str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); ?></div>
                <div>
                    <h3><?php echo e($t); ?></h3>
                    <?php if ($d !== ''): ?><p><?php echo e($d); ?></p><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($projects): ?>
<!-- ===== Related work ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Our work</span>
            <h2>Projects like this we have delivered.</h2>
        </div>
        <div class="portfolio-grid">
            <?php foreach ($projects as $item): ?>
            <div class="portfolio-card" data-reveal>
                <div class="portfolio-thumb">
                    <?php if (!empty($item['image'])): ?>
                    <img loading="lazy" decoding="async" src="<?php echo e(SITE_URL . '/' . ltrim($item['image'], '/')); ?>" alt="<?php echo e($item['title']); ?>" onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
                <div class="portfolio-info">
                    <span class="tag"><?php echo e($item['category']); ?></span>
                    <h3><?php echo e($item['title']); ?></h3>
                    <?php if (!empty($item['description'])): ?><p style="font-size:0.9rem; margin: 0 0 10px;"><?php echo e($item['description']); ?></p><?php endif; ?>
                    <?php if (safeUrl($item['live_url'])): ?>
                    <a href="<?php echo e(safeUrl($item['live_url'])); ?>" target="_blank" rel="noopener noreferrer">Visit live site →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="inline-links" style="text-align:center; margin-top:32px;"><a class="text-link" href="<?php echo SITE_URL; ?>/portfolio.php">View all projects</a></p>
    </div>
</section>
<?php endif; ?>

<?php if ($articles): ?>
<!-- ===== Related articles ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">From the blog</span>
            <h2>Read more about <?php echo e($svc['nav_label']); ?>.</h2>
        </div>
        <div class="blog-grid">
            <?php foreach ($articles as $a): ?>
            <a href="<?php echo e(postUrl($a['slug'])); ?>" class="blog-card-v2" data-reveal>
                <div class="bcv-body">
                    <div class="bcv-top">
                        <span class="bcv-cat" style="<?php echo categoryChipStyle($a['category']); ?>"><?php echo e($a['category']); ?></span>
                        <span class="bcv-date"><?php echo e($a['date_display']); ?></span>
                    </div>
                    <h3><?php echo e($a['title']); ?></h3>
                    <p><?php echo e($a['excerpt']); ?></p>
                    <span class="bcv-link">Read article →</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($faqs): ?>
<!-- ===== FAQ ===== -->
<section class="section">
    <div class="container">
        <div class="section-head center" data-reveal>
            <span class="eyebrow">Common questions</span>
            <h2>Questions about <?php echo e($svc['nav_label']); ?>.</h2>
        </div>
        <div class="faq-list">
            <?php foreach ($faqs as [$q, $a]): ?>
            <div class="faq-item">
                <button class="faq-question">
                    <?php echo e($q); ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer"><p><?php echo e($a); ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($others): ?>
<!-- ===== You may also need ===== -->
<section class="section section-alt">
    <div class="container">
        <aside class="related-services" aria-label="Other services">
            <h3>You may also need</h3>
            <ul>
                <?php foreach ($others as $o): ?>
                <li><a href="<?php echo e($o['url']); ?>"><?php echo e($o['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
            <p class="inline-links" style="margin-bottom:0;"><a class="text-link" href="<?php echo SITE_URL; ?>/services.php">All services</a> · <a class="text-link" href="<?php echo SITE_URL; ?>/contact.php">Talk to us</a></p>
        </aside>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
