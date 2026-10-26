<?php
// ==========================================================
// Buildupnet — Portfolio Page (Dynamic — DB powered)
// Place at: /portfolio.php
// Projects managed from: /admin/pages/recent-work.php
// ==========================================================
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'Our Work: Websites Delivered Across India | Buildupnet';
$pageDescription = 'Browse real, live websites delivered by Buildupnet across travel, education, logistics, agriculture, healthcare and professional services.';

// DB connection
$items = dbRows("SELECT * FROM portfolio_items WHERE status = 'active' ORDER BY sort_order ASC, id ASC");

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <div data-reveal style="max-width: 700px;">
            <span class="eyebrow">Our Work</span>
            <h1>Live projects, not mockups.</h1>
            <p class="lead">
                Every project below is a real website we've built that's still running today.
                Click through to see it in action.
            </p>
        </div>
    </div>
</section>

<!-- ===== Portfolio Grid ===== -->
<section class="section">
    <div class="container">
        <?php if (empty($items)): ?>
            <div style="text-align:center; padding: 60px 0; color: var(--color-slate);">
                <p>Projects coming soon — check back shortly.</p>
            </div>
        <?php else: ?>
        <div class="portfolio-grid">
            <?php foreach ($items as $item): ?>
            <div class="portfolio-card" data-reveal>
                <div class="portfolio-thumb">
                    <?php if (!empty($item['image'])): ?>
                        <img loading="lazy" decoding="async" src="<?php echo SITE_URL . '/' . htmlspecialchars($item['image']); ?>"
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
                <div class="portfolio-info">
                    <span class="tag"><?php echo htmlspecialchars($item['category']); ?></span>
                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                    <?php if (!empty($item['description'])): ?>
                        <p style="font-size:0.9rem; margin: 0 0 10px;"><?php echo htmlspecialchars($item['description']); ?></p>
                    <?php endif; ?>
                    <?php if (safeUrl($item['live_url'])): ?>
                        <a href="<?php echo htmlspecialchars(safeUrl($item['live_url'])); ?>" target="_blank" rel="noopener noreferrer">Visit live site →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="text-align:center; margin-top:48px;" data-reveal>
            <p style="margin-bottom:16px;">Want results like these for your business?</p>
            <a href="#" data-open-quote class="btn btn-primary">Get a Free Quote</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
