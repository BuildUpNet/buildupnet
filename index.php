<?php
// ==========================================================
// Buildupnet — Home Page
// Place this file at: /index.php
// ==========================================================
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Website Design & Digital Marketing in India | Buildupnet';
$pageDescription = 'Buildupnet provides website design, web development, app development, SEO, and digital marketing services across India with 150+ successful projects.';

// Fetch all active projects for proof strip
$allProjects   = dbRows("SELECT title, live_url FROM portfolio_items WHERE status = 'active' AND live_url != '' ORDER BY sort_order ASC, id ASC");

// Fetch latest 3 for portfolio section
$latestProjects = dbRows("SELECT * FROM portfolio_items WHERE status = 'active' ORDER BY sort_order ASC, id ASC LIMIT 3");

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Hero ===== -->
<section class="hero">
    <div class="container hero-inner">
        <div data-reveal>
            <span class="eyebrow">Web · App · Marketing — one team</span>
            <h1>The IT partner that <span class="highlight">stays</span> after launch.</h1>
            <p class="lead">
                We design websites and apps that work the way your business actually runs,
                then keep them fast, secure and growing — with a team you can call, not a ticket number.
            </p>
            <div class="hero-actions">
                <a href="#" data-open-quote class="btn btn-primary">Get a Free Quote</a>
                <a href="portfolio.php" class="btn btn-outline">See Our Work</a>
            </div>
            <div class="hero-trust">
                <div class="hero-trust-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>150+ Projects Delivered</span>
                </div>
                <div class="hero-trust-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>80+ Happy Clients</span>
                </div>
                <div class="hero-trust-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>6+ Years in Business</span>
                </div>
            </div>
        </div>

        <div data-reveal class="hero-visual">
            <div class="hero-visual-bar">
                <span></span><span></span><span></span>
                <span class="hero-visual-label">buildupnet — project_status.sh</span>
            </div>
            <div class="hero-visual-body">
                <div class="line comment">// What you get when you work with us</div>
                <div class="line">&nbsp;</div>
                <div class="line"><span class="accent">$</span> design_review <span class="success">✓ approved</span></div>
                <div class="line"><span class="accent">$</span> build_status <span class="success">✓ on schedule</span></div>
                <div class="line"><span class="accent">$</span> ssl_certificate <span class="success">✓ active</span></div>
                <div class="line"><span class="accent">$</span> mobile_responsive <span class="success">✓ tested</span></div>
                <div class="line"><span class="accent">$</span> support_window <span class="success">✓ 24/7</span></div>
                <div class="line">&nbsp;</div>
                <div class="line"><span class="accent">$</span> <span class="hero-visual-cursor"></span></div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Proof Strip — live client sites ===== -->
<section class="proof-strip">
    <div class="container">
        <div class="proof-strip-label">Real projects, currently live — click to visit</div>
        <div class="proof-track">
            <?php if (!empty($allProjects)): ?>
                <?php foreach ($allProjects as $p): ?>
                <a href="<?php echo htmlspecialchars(safeUrl($p['live_url']) ?: '#'); ?>" target="_blank" rel="noopener noreferrer" class="proof-item">
                    <span class="proof-status"></span>
                    <?php echo htmlspecialchars(parse_url($p['live_url'], PHP_URL_HOST) ?: $p['live_url']); ?>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="proof-item" style="opacity:0.5;">Projects loading...</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== Services ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">What we do</span>
            <h2>Everything your digital presence needs — under one roof.</h2>
        </div>

        <div class="services-grid">
            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </div>
                <h3>Web Development</h3>
                <p>Fast, responsive websites built with clean code — from business sites to e-commerce platforms.</p>
                <a href="services.php#web-development" class="learn-more">Learn more <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                </div>
                <h3>Android & iOS Apps</h3>
                <p>Native mobile apps built with Kotlin and Swift — designed for App Store and Play Store approval.</p>
                <a href="services.php#app-development" class="learn-more">Learn more <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3>SEO & Content</h3>
                <p>On-page, off-page SEO and content that's written for people first — and ranks because of it.</p>
                <a href="services.php#digital-marketing" class="learn-more">Learn more <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3>Networking & AMC</h3>
                <p>Enterprise networking setup plus annual maintenance contracts to keep your IT running smoothly.</p>
                <a href="services.php#networking" class="learn-more">Learn more <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
        </div>
    </div>
</section>

<!-- ===== Stats (dark band) ===== -->
<section class="section section-dark">
    <div class="container">
        <div class="section-head center" data-reveal style="margin-bottom: 48px;">
            <span class="eyebrow">Why businesses trust us</span>
            <h2>Numbers we're proud to stand behind.</h2>
        </div>
        <div class="stats-grid" data-reveal>
            <div>
                <div class="stat-number">150+</div>
                <div class="stat-label">Projects Delivered</div>
            </div>
            <div>
                <div class="stat-number">80+</div>
                <div class="stat-label">Happy Clients</div>
            </div>
            <div>
                <div class="stat-number">6+</div>
                <div class="stat-label">Years Experience</div>
            </div>
            <div>
                <div class="stat-number">100%</div>
                <div class="stat-label">Client Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Why Choose Us ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">How we work</span>
            <h2>What makes Buildupnet different.</h2>
        </div>
        <div class="features-grid">
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <h4>Built around your business, not a template</h4>
                    <p>We start by understanding how your business actually works — then design around that, instead of forcing you into a generic layout.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div>
                    <h4>Fast, secure, mobile-first by default</h4>
                    <p>Every site we ship is SSL-secured, optimized for Core Web Vitals, and tested across real devices before going live.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h4>Clear timelines, no disappearing acts</h4>
                    <p>You get a written project plan with milestones up front — and regular updates while we build, not just at the end.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                </div>
                <div>
                    <h4>Support that continues after launch</h4>
                    <p>Most agencies vanish after delivery. We offer ongoing maintenance plans, so updates and fixes are a quick call away.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Process ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Our process</span>
            <h2>How a project moves from idea to launch.</h2>
        </div>
        <div class="process-list" data-reveal>
            <div class="process-step">
                <div class="process-num">01</div>
                <div>
                    <h3>Discovery call</h3>
                    <p>We talk through your goals, audience and budget — no jargon, no sales pressure. You'll get honest input on what's realistic.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">02</div>
                <div>
                    <h3>Design & proposal</h3>
                    <p>We share wireframes or design concepts along with a fixed quote and timeline, so you know exactly what you're approving.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">03</div>
                <div>
                    <h3>Build & review</h3>
                    <p>Development happens in stages with check-ins, so you can review progress and request changes before anything is final.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">04</div>
                <div>
                    <h3>Launch & support</h3>
                    <p>We handle deployment, testing and go-live — then stay on for maintenance, updates and questions whenever they come up.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Portfolio Preview ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Recent work</span>
            <h2>A few projects we've delivered recently.</h2>
        </div>
        <div class="portfolio-grid">
            <?php if (!empty($latestProjects)): ?>
                <?php foreach ($latestProjects as $p): ?>
                <div class="portfolio-card" data-reveal>
                    <div class="portfolio-thumb">
                        <?php if (!empty($p['image'])): ?>
                        <img loading="lazy" decoding="async" src="<?php echo SITE_URL . '/' . htmlspecialchars($p['image']); ?>"
                             alt="<?php echo htmlspecialchars($p['title']); ?>"
                             onerror="this.style.display='none'">
                        <?php endif; ?>
                    </div>
                    <div class="portfolio-info">
                        <span class="tag"><?php echo htmlspecialchars($p['category']); ?></span>
                        <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                        <?php if (safeUrl($p['live_url'])): ?>
                        <a href="<?php echo htmlspecialchars(safeUrl($p['live_url'])); ?>" target="_blank" rel="noopener noreferrer">Visit live site →</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--color-slate);">Projects coming soon.</p>
            <?php endif; ?>
        </div>
        <div style="text-align:center; margin-top:40px;" data-reveal>
            <a href="portfolio.php" class="btn btn-outline">View Full Portfolio</a>
        </div>
    </div>
</section>

<!-- ===== Testimonials ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Client feedback</span>
            <h2>What clients say after working with us.</h2>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-card" data-reveal>
                <p class="testimonial-quote">"Buildupnet completely transformed our online presence. The new website is visually strong and has noticeably increased enquiries and bookings. The team stayed professional and on schedule throughout."</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">
                        <img loading="lazy" decoding="async" src="assets/img/team/client-rajiv.jpg" alt="Rajiv Sharma" onerror="this.style.display='none'">
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
                        <img loading="lazy" decoding="async" src="assets/img/team/client-harpreet.jpg" alt="Dr. Harpreet Kaur" onerror="this.style.display='none'">
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

<!-- ===== FAQ ===== -->
<section class="section">
    <div class="container">
        <div class="section-head center" data-reveal>
            <span class="eyebrow">Common questions</span>
            <h2>Things people usually ask before starting.</h2>
        </div>
        <div class="faq-list">
            <div class="faq-item">
                <button class="faq-question">
                    What services does Buildupnet offer?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>We offer custom website design and development, Android and iOS app development, SEO, content marketing, social media and Meta/Google Ads management, plus networking setup and annual maintenance contracts.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    How much does a website cost?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>It depends on the number of pages, features and complexity. We provide a clear, itemized quote after a short discovery call — no hidden costs added later.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    How long does a typical project take?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>A standard business website typically takes 2–4 weeks. Larger projects like e-commerce stores or custom portals usually take 4–8 weeks, with a milestone plan shared in advance.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Do you provide support after the website is live?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Yes. We offer ongoing maintenance plans covering updates, security patches, performance checks and content changes, so your site stays reliable long after launch.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Which industries have you worked with?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>We've delivered projects across travel & tourism, education, agriculture, healthcare, finance, retail, real estate and professional services throughout India.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// ===== FAQPage Schema =====
// Mirrors the FAQ section above word-for-word. This is the single
// highest-impact block for Answer Engine Optimization — AI assistants
// (Google AI Overviews, ChatGPT, Perplexity, etc.) frequently lift
// question/answer pairs directly from FAQPage structured data when
// responding to "how much does a website cost", "do you offer X", etc.
$homeFaqs = [
    [
        'q' => 'What services does Buildupnet offer?',
        'a' => 'We offer custom website design and development, Android and iOS app development, SEO, content marketing, social media and Meta/Google Ads management, plus networking setup and annual maintenance contracts.'
    ],
    [
        'q' => 'How much does a website cost?',
        'a' => 'It depends on the number of pages, features and complexity. We provide a clear, itemized quote after a short discovery call — no hidden costs added later.'
    ],
    [
        'q' => 'How long does a typical project take?',
        'a' => 'A standard business website typically takes 2–4 weeks. Larger projects like e-commerce stores or custom portals usually take 4–8 weeks, with a milestone plan shared in advance.'
    ],
    [
        'q' => 'Do you provide support after the website is live?',
        'a' => 'Yes. We offer ongoing maintenance plans covering updates, security patches, performance checks and content changes, so your site stays reliable long after launch.'
    ],
    [
        'q' => 'Which industries have you worked with?',
        'a' => "We've delivered projects across travel & tourism, education, agriculture, healthcare, finance, retail, real estate and professional services throughout India."
    ],
];

$faqEntities = [];
foreach ($homeFaqs as $item) {
    $faqEntities[] = [
        '@type' => 'Question',
        'name' => $item['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['a'],
        ],
    ];
}

$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $faqEntities,
];
?>
<script type="application/ld+json">
<?php echo json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<!-- ===== BreadcrumbList Schema ===== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "<?php echo SITE_URL; ?>/"
    }
  ]
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
