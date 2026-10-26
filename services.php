<?php
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Web Design, App Development & SEO Services | Buildupnet';
$pageDescription = 'Website design, custom development, ecommerce, apps, SEO, social media, Google Ads and maintenance for businesses across India.';

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <div data-reveal style="max-width: 700px;">
            <span class="eyebrow">Services</span>
            <h1>Everything you need to launch, grow and maintain your digital presence.</h1>
            <p class="lead">
                Complete digital solutions to grow your business online — from stunning,
                fast-loading websites to result-driven marketing campaigns. Each service
                works on its own or as part of a larger plan.
            </p>
        </div>
    </div>
</section>

<!-- ===== Services Grid ===== -->
<section id="web-development" class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">What we offer</span>
            <h2>Professional IT services & digital solutions.</h2>
        </div>

        <div class="services-grid">

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </div>
                <h3>Web Development</h3>
                <p>High-performance, SEO-friendly websites built with HTML5, CSS3, PHP and React — from corporate sites to custom web applications.</p>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <h3>Ecommerce Websites</h3>
                <p>Feature-rich online stores with secure payment gateways (Razorpay, PayU), product management and mobile-optimized checkout.</p>
            </div>

            <div class="service-card" data-reveal id="app-development">
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                </div>
                <h3>Android & iOS Apps</h3>
                <p>Native mobile apps built with Kotlin, Java and Swift — from MVP to full-scale apps, deployed to the Play Store and App Store.</p>
            </div>

            <div class="service-card" data-reveal id="digital-marketing">
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3>SEO & Link Building</h3>
                <p>On-page, technical and local SEO plus white-hat link building through guest posting, niche edits and digital PR.</p>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3>Content Marketing</h3>
                <p>SEO-optimized blogs, web copy and content calendars that attract your audience and build long-term brand authority.</p>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3>Social Media & Ads</h3>
                <p>Facebook, Instagram and LinkedIn campaigns plus Google Ads & PPC management — keyword research through performance tracking.</p>
            </div>

            <div class="service-card" data-reveal>
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h3>Local SEO & Google Business</h3>
                <p>Optimized Google Business Profiles and local search strategy — ideal for shops, clinics, schools and local businesses.</p>
            </div>

            <div class="service-card" data-reveal id="networking">
                <div class="service-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                </div>
                <h3>Networking & AMC</h3>
                <p>Cisco networking setup — routers, switches, firewalls — plus Annual Maintenance Contracts covering hardware and software upkeep.</p>
            </div>

        </div>
    </div>
</section>

<!-- ===== Process ===== -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">How we work</span>
            <h2>A simple, transparent process — on time, every time.</h2>
        </div>
        <div class="process-list" data-reveal>
            <div class="process-step">
                <div class="process-num">01</div>
                <div>
                    <h3>Discovery</h3>
                    <p>We understand your business, goals, target audience and requirements in detail.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">02</div>
                <div>
                    <h3>Strategy & Design</h3>
                    <p>We create a tailored plan and design mockups aligned with your brand identity.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">03</div>
                <div>
                    <h3>Development</h3>
                    <p>Your website or campaign is built with clean code, speed and SEO best practices.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">04</div>
                <div>
                    <h3>Launch & Support</h3>
                    <p>We go live on schedule and provide ongoing support, updates and optimization.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="section">
    <div class="container">
        <div class="section-head center" data-reveal>
            <span class="eyebrow">Questions about our services</span>
            <h2>Got questions? We have answers.</h2>
        </div>
        <div class="faq-list">
            <div class="faq-item">
                <button class="faq-question">
                    What website design services does Buildupnet offer?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Custom website design, responsive web development, ecommerce websites, landing pages, school & institution portals, and WordPress-based websites for businesses of all sizes across India.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Does Buildupnet offer SEO services?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Yes — complete SEO services including on-page optimization, technical SEO, local SEO, Google Business Profile setup, keyword research and link building to help your site rank higher and attract organic traffic.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    Can Buildupnet help my business get more leads online?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Yes. We specialize in lead generation through targeted digital marketing campaigns, SEO, paid advertising and conversion-optimized landing pages that turn visitors into paying customers.</p>
                </div>
            </div>
            <div class="faq-item">
                <button class="faq-question">
                    How do I get started with Buildupnet?
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <div class="faq-answer">
                    <p>Call us at <?php echo SITE_PHONE; ?> or fill out our free quote form. We'll discuss your requirements and provide a transparent, no-obligation proposal within 24 hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>