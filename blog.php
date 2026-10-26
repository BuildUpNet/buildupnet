<?php
// ==========================================================
// Buildupnet — Blog Listing Page
// Place this file at: /blog.php
// Posts come from the database (Admin → Blog Posts)
// ==========================================================
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'Blog: Web Design, SEO & Marketing Tips | Buildupnet';
$pageDescription = 'Practical guides on web design, SEO, digital marketing and app development from the Buildupnet team — written for business owners, not developers.';

$published = blogPosts();
$featured  = $published[0] ?? null;
$rest      = array_slice($published, 1);

$catColors = BLOG_CATEGORIES;

function getCatStyle($cat, $catColors) {
    $c = $catColors[$cat] ?? ['bg' => '#E7F0F4', 'color' => '#005470'];
    return 'background:' . $c['bg'] . '; color:' . $c['color'] . ';';
}

// Gradient backgrounds for cards with no image
$gradients = [
    'linear-gradient(135deg, #005470 0%, #28A9E4 100%)',
    'linear-gradient(135deg, #1a6b3a 0%, #28a47a 100%)',
    'linear-gradient(135deg, #3d2b8c 0%, #7c5cbf 100%)',
    'linear-gradient(135deg, #7a2d00 0%, #d4622a 100%)',
    'linear-gradient(135deg, #00404d 0%, #009db5 100%)',
];

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="blog-hero">
    <div class="container">
        <div>
            <span class="eyebrow">Buildupnet Blog</span>
            <h1>Insights on web, SEO &amp; digital growth.</h1>
            <p class="lead">Practical guides written for business owners — not developers.</p>
        </div>
    </div>
</section>

<?php if ($featured): ?>
<!-- ===== Featured Post ===== -->
<section class="section" style="padding-top: 48px;">
    <div class="container">
        <span class="eyebrow" data-reveal>Featured Article</span>
        <div data-reveal>
        <a href="<?php echo e(postUrl($featured['slug'])); ?>" class="blog-featured-card">

            <!-- Left: visual panel -->
            <div class="bfc-visual" style="background: <?php echo $gradients[0]; ?>">
                <div class="bfc-visual-inner">
                    <span class="bfc-cat" style="<?php echo getCatStyle($featured['category'], $catColors); ?>">
                        <?php echo htmlspecialchars($featured['category']); ?>
                    </span>
                    <div class="bfc-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div class="bfc-read-time"><?php echo htmlspecialchars($featured['read_time']); ?></div>
                </div>
            </div>

            <!-- Right: content -->
            <div class="bfc-content">
                <div class="blog-meta" style="margin-bottom: 14px;">
                    <span><?php echo htmlspecialchars($featured['date_display']); ?></span>
                    <span>By <?php echo htmlspecialchars($featured['author']); ?></span>
                </div>
                <h2><?php echo htmlspecialchars($featured['title']); ?></h2>
                <p><?php echo htmlspecialchars($featured['excerpt']); ?></p>
                <span class="bfc-cta">
                    Read full article
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </span>
            </div>
        </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== All Posts ===== -->
<?php if (!empty($rest)): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">All Articles</span>
            <h2>More from the blog.</h2>
        </div>
        <div class="blog-grid">
            <?php foreach ($rest as $i => $post): ?>
            <a href="<?php echo e(postUrl($post['slug'])); ?>" class="blog-card-v2" data-reveal>
                <div class="bcv-thumb" style="background: <?php echo $gradients[($i + 1) % count($gradients)]; ?>">
                    <?php if (!empty($post['thumbnail'])): ?>
                        <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($post['thumbnail']); ?>"
                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <span class="bcv-read"><?php echo htmlspecialchars($post['read_time']); ?></span>
                </div>
                <div class="bcv-body">
                    <div class="bcv-top">
                        <span class="bcv-cat" style="<?php echo getCatStyle($post['category'], $catColors); ?>">
                            <?php echo htmlspecialchars($post['category']); ?>
                        </span>
                        <span class="bcv-date"><?php echo htmlspecialchars($post['date_display']); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                    <span class="bcv-link">
                        Read article
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== Newsletter Band ===== -->
<section class="section">
    <div class="container">
        <div class="newsletter-band">
            <div class="newsletter-band-text">
                <span class="eyebrow" style="color: var(--color-accent);">Stay updated</span>
                <h2>Get new articles in your inbox.</h2>
                <p>No spam — just practical guides on web design, SEO and digital marketing, once or twice a month.</p>
            </div>
            <form class="newsletter-form-inline" action="forms/newsletter.php" method="POST">
                <?php echo honeypotField(); ?>
                <?php if (isset($_GET['subscribed'])): ?>
                    <p class="nfi-note" style="color:#2D7A52; font-weight:600;"><?php echo ($_GET['subscribed'] ?? '') === '1' ? 'Thanks — you are subscribed!' : 'Please enter a valid email address.'; ?></p>
                <?php endif; ?>
                <div class="nfi-group">
                    <input type="email" name="email" required placeholder="Your email address">
                    <button type="submit" class="btn btn-primary" style="border:none; white-space:nowrap;">Subscribe Free</button>
                </div>
                <p class="nfi-note">Join business owners across India. Unsubscribe anytime.</p>
            </form>
        </div>
    </div>
</section>

<!-- Blog Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "name": "<?php echo SITE_NAME; ?> Blog",
  "url": "<?php echo SITE_URL; ?>/blog.php",
  "description": <?php echo json_encode($pageDescription, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES); ?>,
  "publisher": {
    "@type": "Organization",
    "name": "<?php echo SITE_NAME; ?>",
    "url": "<?php echo SITE_URL; ?>/"
  },
  "blogPost": [
    <?php
    $schemaItems = [];
    foreach ($published as $post) {
        $schemaItems[] = json_encode([
            '@type'         => 'BlogPosting',
            'headline'      => $post['title'],
            'description'   => $post['excerpt'],
            'url'           => postUrl($post['slug']),
            'datePublished' => $post['date'],
            'dateModified'  => $post['updated'] ?? $post['date'],
            'author'        => ['@type' => 'Person', 'name' => $post['author']],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }
    echo implode(",\n    ", $schemaItems);
    ?>
  ]
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
