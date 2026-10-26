<?php
// ==========================================================
// Buildupnet — Single Blog Post Page
// Place this file at: /blog-post.php
// Public URL: /blog/<slug>  (rewritten to this file by .htaccess)
// Posts come from the database (Admin → Blog Posts)
// ==========================================================
require_once __DIR__ . '/includes/config.php';

// Get slug from URL
$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$post = preg_match('/^[a-z0-9-]{1,190}$/', $slug) ? blogPostBySlug($slug) : null;

// One URL per article: old /blog-post.php?slug=... links are redirected (301) to /blog/<slug>
if ($post && strpos($_SERVER['REQUEST_URI'], 'blog-post.php') !== false) {
    header('Location: ' . postUrl($post['slug']), true, 301);
    exit;
}

// 404 if post not found
if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post Not Found | Buildupnet Blog';
    $pageDescription = 'This blog post could not be found.';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container" style="text-align:center; padding: 80px 0;">
        <h1>Post Not Found</h1>
        <p style="margin: 16px 0 28px;">This article doesn\'t exist or may have been moved.</p>
        <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-primary">Back to Blog</a>
    </div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// SEO fields from Admin → Blog → "SEO" box; sensible automatic fallbacks when they are empty
$pageTitle       = $post['meta_title'] !== '' ? $post['meta_title']
                 : (mb_strlen($post['title']) <= 46 ? $post['title'] . ' | Buildupnet' : $post['title']);          // header.php escapes it
$pageDescription = $post['meta_desc'] !== '' ? $post['meta_desc'] : $post['excerpt'];
if ($post['noindex']) $pageRobots = 'noindex, follow';
$pageImage       = !empty($post['thumbnail']) ? SITE_URL . '/' . ltrim($post['thumbnail'], '/') : OG_IMAGE_DEFAULT;
$pageType        = 'article';
$canonicalUrl    = postUrl($post['slug']);
$headExtra       = '<meta property="article:published_time" content="' . e($post['date']) . '">' . "\n"
                 . '<meta property="article:modified_time" content="' . e($post['updated']) . '">' . "\n"
                 . '<meta property="article:section" content="' . e($post['category']) . '">' . "\n"
                 . '<meta property="article:author" content="' . e($post['author']) . '">' . "\n";
$authorRole      = $post['author_role'] ?: 'Buildupnet Team';
$isKnownAuthor   = !empty($post['author_role']);
$modified        = $post['updated'];

// Related posts
$related = relatedPosts($post['category'], $post['slug'], 2);

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Article Hero ===== -->
<section class="hero" style="padding: 48px 0 0;">
    <div class="container" style="max-width: 860px; margin: 0 auto;">
        <div data-reveal>
            <a href="<?php echo SITE_URL; ?>/blog.php" class="learn-more" style="margin-bottom: 20px; display:inline-flex;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transform:rotate(180deg)"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                Back to Blog
            </a>

            <div class="blog-meta" style="margin: 16px 0 18px;">
                <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                <time datetime="<?php echo e($post['date']); ?>"><?php echo htmlspecialchars($post['date_display']); ?></time>
                <?php if ($modified !== $post['date']): ?><span>Updated <time datetime="<?php echo e($modified); ?>"><?php echo e(date('F j, Y', strtotime($modified))); ?></time></span><?php endif; ?>
                <span><?php echo htmlspecialchars($post['read_time']); ?></span>
            </div>

            <h1 style="font-size: clamp(1.8rem, 4vw, 2.8rem); margin-bottom: 20px;">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>

            <p class="lead" style="margin-bottom: 32px;">
                <?php echo htmlspecialchars($post['excerpt']); ?>
            </p>

            <div class="testimonial-author" style="padding: 18px 0; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); margin-bottom: 0;">
                <div class="testimonial-avatar">
                    <?php $authorImg = 'assets/img/team/' . preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $post['author']))) . '.jpg'; ?>
                    <?php if (file_exists(__DIR__ . '/' . $authorImg)): ?>
                    <img loading="lazy" decoding="async" src="<?php echo e(SITE_URL . '/' . $authorImg); ?>"
                         alt="<?php echo htmlspecialchars($post['author']); ?>"
                         onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
                <div>
                    <h4><?php if ($isKnownAuthor): ?><a href="<?php echo SITE_URL; ?>/about.php#team" rel="author"><?php echo htmlspecialchars($post['author']); ?></a><?php else: ?><?php echo htmlspecialchars($post['author']); ?><?php endif; ?></h4>
                    <span><?php echo e($authorRole); ?> · Buildupnet, Sangrur (Punjab)</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Thumbnail ===== -->
<?php if (!empty($post['thumbnail'])): ?>
<div class="container" style="max-width: 860px; margin: 0 auto; padding: 28px 24px 0;">
    <div style="border-radius: var(--radius-md); overflow: hidden; max-height: 460px;" data-reveal>
        <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim($post['thumbnail'], '/')); ?>"
             alt="<?php echo htmlspecialchars($post['title']); ?>"
             style="width:100%; height:100%; object-fit:cover; display:block;"
             onerror="this.parentElement.style.display='none'">
    </div>
</div>
<?php endif; ?>

<!-- ===== Article Body ===== -->
<section class="section">
    <div class="container" style="max-width: 860px; margin: 0 auto;">
        <div class="post-body">
            <?php echo sanitizeHtml($post['content']); ?>
        </div>

        <!-- E-E-A-T: who wrote this, who stands behind it, how to reach a real person -->
        <aside class="testimonial-card" style="margin-top:40px; text-align:left;" aria-label="About the author">
            <h4 style="margin:0 0 6px;">Written by <?php echo $isKnownAuthor ? '<a href="<?php echo SITE_URL; ?>/about.php#team" rel="author">' . e($post['author']) . '</a>' : e($post['author']); ?></h4>
            <p style="margin:0 0 10px; color:var(--color-slate); font-size:0.9rem;"><?php echo e($authorRole); ?>, <?php echo e(SITE_NAME); ?> — a web design &amp; digital marketing team in Sangrur, Punjab, working with businesses across India since <?php echo e(SITE_FOUNDING_YEAR); ?>.</p>
            <p style="margin:0; font-size:0.88rem; color:var(--color-slate);">
                Published <?php echo e($post['date_display']); ?><?php if ($modified !== $post['date']): ?> · Last updated <?php echo e(date('F j, Y', strtotime($modified))); ?><?php endif; ?>.
                Prices and platform details change — if something here looks out of date, please
                <a href="<?php echo SITE_URL; ?>/contact.php">tell us</a> or write to <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>.
            </p>
        </aside>

        <!-- Share / CTA -->
        <div class="post-share" data-reveal>
            <span>Share this article:</span>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(postUrl($post['slug'])); ?>&text=<?php echo urlencode($post['title']); ?>"
               target="_blank" rel="noopener" class="share-btn">X / Twitter</a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(postUrl($post['slug'])); ?>&title=<?php echo urlencode($post['title']); ?>"
               target="_blank" rel="noopener" class="share-btn">LinkedIn</a>
            <a href="https://wa.me/?text=<?php echo urlencode($post['title'] . ' — ' . postUrl($post['slug'])); ?>"
               target="_blank" rel="noopener" class="share-btn">WhatsApp</a>
        </div>
    </div>
</section>

<!-- ===== Related Posts ===== -->
<?php if (!empty($related)): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Keep Reading</span>
            <h2>Related articles.</h2>
        </div>
        <div class="blog-grid" style="grid-template-columns: repeat(<?php echo count($related); ?>, 1fr);">
            <?php foreach ($related as $rp): ?>
            <div class="blog-card" data-reveal>
                <div class="blog-thumb">
                    <?php if (!empty($rp['thumbnail'])): ?>
                    <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim($rp['thumbnail'], '/')); ?>"
                         alt="<?php echo htmlspecialchars($rp['title']); ?>"
                         onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
                <div class="blog-body">
                    <div class="blog-meta">
                        <span class="blog-category"><?php echo htmlspecialchars($rp['category']); ?></span>
                        <span><?php echo htmlspecialchars($rp['date_display']); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($rp['title']); ?></h3>
                    <p><?php echo htmlspecialchars($rp['excerpt']); ?></p>
                    <a href="<?php echo e(postUrl($rp['slug'])); ?>" class="learn-more">
                        Read article
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== Article Schema (E-E-A-T / AEO) ===== -->
<?php
$postUrl = postUrl($post['slug']);
$authorNode = $isKnownAuthor
    ? ['@type' => 'Person', 'name' => $post['author'], 'jobTitle' => $authorRole, 'url' => SITE_URL . '/about.php#team',
       'worksFor' => ['@id' => SITE_URL . '/#organization']]
    : ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL . '/'];
$words = str_word_count(strip_tags($post['content']));
$ld = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'description' => seoTrim($pageDescription, 158, '…'),
            'image' => $pageImage,
            'url' => $postUrl,
            'inLanguage' => 'en-IN',
            'articleSection' => $post['category'],
            'keywords' => $post['keyword'] !== '' ? $post['keyword'] : null,
            'wordCount' => $words,
            'datePublished' => $post['date'],
            'dateModified' => $modified,
            'author' => $authorNode,
            'publisher' => ['@type' => 'Organization', '@id' => SITE_URL . '/#organization', 'name' => SITE_NAME,
                            'logo' => ['@type' => 'ImageObject', 'url' => SCHEMA_LOGO]],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => SITE_URL . '/blog.php'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $postUrl],
            ],
        ],
    ],
];
if ($post['keyword'] === '') unset($ld['@graph'][0]['keywords']);
echo '<script type="application/ld+json">' . ldJson($ld) . '</script>';
?>

<?php include __DIR__ . '/includes/footer.php'; ?>
