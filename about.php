<?php
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'About Buildupnet | Web Design & Marketing Team, Punjab';
$pageDescription = 'Buildupnet is a website design and digital marketing agency in Sangrur, Punjab, with 150+ projects delivered for businesses across India.';

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <div data-reveal style="max-width: 700px;">
            <span class="eyebrow">About Buildupnet</span>
            <h1>6+ years of building digital success stories.</h1>
            <p class="lead">
                Buildupnet is a full-service website design and digital marketing agency based
                in Punjab, India. We've delivered 150+ projects for 80+ clients across travel,
                education, healthcare, agriculture, finance and retail — and we stay involved
                long after a project goes live.
            </p>
        </div>
    </div>
</section>

<!-- ===== Story ===== -->
<section class="section">
    <div class="container">
        <div class="features-grid">
            <div data-reveal>
                <span class="eyebrow">Our story</span>
                <h2>Built on designs that work as hard as they look.</h2>
                <p style="margin-top: 16px; font-size: 0.98rem;">
                    Every project starts with understanding your brand and your audience —
                    not a template. From there we focus on clean, modern design, navigation
                    that feels effortless, and pages that load fast on any device.
                </p>
                <ul class="check-list">
                    <li>Sleek, brand-aligned designs that stand out</li>
                    <li>Intuitive navigation that keeps visitors engaged</li>
                    <li>Fully responsive across desktop, tablet and mobile</li>
                    <li>Optimized for speed and Core Web Vitals</li>
                    <li>Scalable foundations that grow with your business</li>
                </ul>
            </div>
            <div data-reveal class="hero-visual" style="align-self: start;">
                <div class="hero-visual-bar">
                    <span></span><span></span><span></span>
                    <span class="hero-visual-label">buildupnet — about.json</span>
                </div>
                <div class="hero-visual-body">
                    <div class="line"><span class="accent">"based_in"</span>: <span class="success">"Punjab, India"</span>,</div>
                    <div class="line"><span class="accent">"experience"</span>: <span class="success">"6+ years"</span>,</div>
                    <div class="line"><span class="accent">"projects_delivered"</span>: <span class="success">"150+"</span>,</div>
                    <div class="line"><span class="accent">"clients_served"</span>: <span class="success">"80+"</span>,</div>
                    <div class="line"><span class="accent">"support_model"</span>: <span class="success">"post-launch, ongoing"</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Why Choose Us ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Why choose Buildupnet</span>
            <h2>What makes us different — beyond the portfolio.</h2>
        </div>
        <div class="features-grid">
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <h4>Proven track record</h4>
                    <p>150+ successful projects and 80+ satisfied clients across diverse industries — our results speak for themselves.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <h4>End-to-end service</h4>
                    <p>From ideation and design to development, SEO and ongoing support — your entire digital journey, handled under one roof.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h4>Transparent pricing</h4>
                    <p>No hidden costs. We provide clear, itemized proposals so you always know what you're getting and what you're paying for.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                </div>
                <div>
                    <h4>Data-driven & timely</h4>
                    <p>Marketing decisions backed by analytics and continuously refined, with delivery timelines we commit to and meet.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Team ===== -->
<section class="section" id="team">
    <div class="container">
        <div class="section-head center" data-reveal>
            <span class="eyebrow">Meet the team</span>
            <h2>The experienced leaders driving your digital success.</h2>
        </div>
        <div class="testimonial-grid">
            <?php foreach (authors() as $member): ?>
            <div class="testimonial-card" data-reveal style="text-align: center;">
                <div class="testimonial-avatar" style="width: 96px; height: 96px; margin: 0 auto 18px;">
                    <?php if (!empty($member['photo'])): ?>
                    <img loading="lazy" decoding="async" src="<?php echo e(SITE_URL . '/' . ltrim($member['photo'], '/')); ?>" alt="<?php echo e($member['name']); ?>" width="96" height="96" loading="lazy" onerror="this.style.display='none'">
                    <?php endif; ?>
                </div>
                <h3 style="margin-bottom: 4px;"><?php echo e($member['name']); ?></h3>
                <span style="color: var(--color-slate); font-size: 0.9rem;"><?php echo e($member['role']); ?></span>
                <?php if (!empty($member['bio'])): ?><p style="font-size:0.9rem; margin-top:10px;"><?php echo e($member['bio']); ?></p><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== Testimonials ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head center" data-reveal>
            <span class="eyebrow">Client feedback</span>
            <h2>What clients say after working with us.</h2>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-card" data-reveal>
                <p class="testimonial-quote">"Buildupnet completely transformed our online presence. The new website is visually strong and has noticeably increased enquiries and bookings. The team stayed professional and on schedule throughout."</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">
                        <img loading="lazy" decoding="async" src="assets/img/testmonial/testimonial.png" alt="Rajiv Sharma" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h4>Rajiv Sharma</h4>
                        <span>Owner, Smart Panda Travels</span>
                    </div>
                </div>
            </div>
            <div class="testimonial-card" data-reveal>
                <p class="testimonial-quote">"Working with Buildupnet was smooth from start to finish. They understood our school's vision and delivered a clean, modern website that parents and students genuinely use. Support is always reachable."</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">
                        <img loading="lazy" decoding="async" src="assets/img/testmonial/testimonial.png" alt="Dr. Harpreet Kaur" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h4>Dr. Harpreet Kaur</h4>
                        <span>Principal, DPS Majhi School</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>