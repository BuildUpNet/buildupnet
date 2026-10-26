<?php
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Careers | Join the Buildupnet Team — Open Positions';
$pageDescription = 'Explore open positions at Buildupnet and apply to join our web development, app development and digital marketing team in Punjab, India.';

// Open positions come from the database (Admin → Job Openings)
$jobOpenings = dbRows("SELECT id, title, type, location, description FROM job_openings WHERE status = 'active' ORDER BY created_at DESC");

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <div data-reveal style="max-width: 700px;">
            <span class="eyebrow">Careers at Buildupnet</span>
            <h1>Build things that actually go live.</h1>
            <p class="lead">
                We're a small, hands-on team working on real projects for real clients —
                no busywork, no endless internal tools nobody uses. If you want your work
                to matter and ship, we'd like to hear from you.
            </p>
            <div class="hero-actions">
                <a href="#open-positions" class="btn btn-primary">View Open Positions</a>
                <a href="#apply-form" class="btn btn-outline">Apply Now</a>
            </div>
        </div>
    </div>
</section>

<!-- ===== Why Work With Us ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Why Buildupnet</span>
            <h2>What it's like working here.</h2>
        </div>
        <div class="features-grid">
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <h4>Real client work from day one</h4>
                    <p>You'll work on live projects for real businesses — not simulations or internal-only tasks that never ship.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <h4>Small team, real ownership</h4>
                    <p>No bloated hierarchy — your decisions and code reach production quickly, and your contributions are visible.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h4>Flexible, remote-friendly roles</h4>
                    <p>Several positions are remote or freelance — we care about output and reliability more than where you sit.</p>
                </div>
            </div>
            <div class="feature-item" data-reveal>
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                </div>
                <div>
                    <h4>Learn across the stack</h4>
                    <p>Working across web, apps, SEO and infrastructure means you're never boxed into one narrow skill for long.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Open Positions ===== -->
<section id="open-positions" class="section section-alt">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Open Positions</span>
            <h2>Current openings — full-time, remote and freelance.</h2>
        </div>

        <?php if (empty($jobOpenings)): ?>
            <div class="testimonial-card" data-reveal style="text-align:center;">
                <p style="margin-bottom:0;">There are no open positions right now, but we're always happy to hear from talented people. Use the form below to send a general application.</p>
            </div>
        <?php else: ?>
            <div class="services-grid">
                <?php foreach ($jobOpenings as $job): ?>
                <div class="service-card" data-reveal>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:14px;">
                        <h3 style="margin-bottom:0;"><?php echo e($job['title']); ?></h3>
                        <span class="badge"><?php echo e($job['type']); ?></span>
                    </div>
                    <p style="font-size:0.85rem; color:var(--color-steel); font-family:var(--font-mono); margin-bottom:10px;">
                        <?php echo e($job['location']); ?>
                    </p>
                    <p><?php echo e($job['description']); ?></p>
                    <a href="#apply-form" class="learn-more apply-trigger" data-job="<?php echo e($job['title']); ?>">
                        Apply for this role
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== Hiring Process ===== -->
<section class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Hiring Process</span>
            <h2>What happens after you apply.</h2>
        </div>
        <div class="process-list" data-reveal>
            <div class="process-step">
                <div class="process-num">01</div>
                <div>
                    <h3>Application review</h3>
                    <p>We review your resume and portfolio against the role — usually within 3-5 working days.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">02</div>
                <div>
                    <h3>Screening call</h3>
                    <p>A short call to understand your experience, availability and expectations on both sides.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">03</div>
                <div>
                    <h3>Skills round</h3>
                    <p>A practical task or portfolio walkthrough relevant to the role — not abstract puzzles.</p>
                </div>
            </div>
            <div class="process-step">
                <div class="process-num">04</div>
                <div>
                    <h3>Offer & onboarding</h3>
                    <p>If it's a good fit on both sides, we move quickly — clear terms, no long waiting games.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Application Form ===== -->
<section id="apply-form" class="section section-alt">
    <div class="container">
        <div style="max-width: 760px; margin: 0 auto;">
            <div class="section-head center" data-reveal>
                <span class="eyebrow">Apply Now</span>
                <h2>Submit your application.</h2>
            </div>

            <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
                <div style="background:#E7F5EC; border:1px solid var(--color-success); color:#256b42; padding:16px 20px; border-radius:var(--radius-sm); margin-bottom:24px; font-size:0.92rem;">
                    Thanks — your application has been received. We'll review it and get back to you if there's a match.
                </div>
            <?php endif; ?>
            <form action="forms/send-application.php" method="POST" enctype="multipart/form-data" data-reveal
                  style="display:flex; flex-direction:column; gap:20px; background:var(--color-white); border:1px solid var(--color-border); border-radius:var(--radius-md); padding:36px;">
                <?php echo honeypotField(); ?>

                <div class="features-grid" style="gap: 20px;">
                    <div>
                        <label for="app-name" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Full Name *</label>
                        <input type="text" id="app-name" name="name" required
                               style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                    </div>
                    <div>
                        <label for="app-email" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Email Address *</label>
                        <input type="email" id="app-email" name="email" required
                               style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                    </div>
                </div>

                <div class="features-grid" style="gap: 20px;">
                    <div>
                        <label for="app-phone" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Phone / WhatsApp *</label>
                        <input type="tel" id="app-phone" name="phone" required
                               style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                    </div>
                    <div>
                        <label for="app-experience" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Years of Experience</label>
                        <select id="app-experience" name="experience"
                                style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                            <option value="">Select range</option>
                            <option value="Fresher / 0-1 years">Fresher / 0–1 years</option>
                            <option value="1-3 years">1–3 years</option>
                            <option value="3-5 years">3–5 years</option>
                            <option value="5+ years">5+ years</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="app-position" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Position You're Applying For *</label>
                    <select id="app-position" name="position" required
                            style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                        <option value="">Select a position</option>
                        <?php foreach ($jobOpenings as $job): ?>
                            <option value="<?php echo e($job['title']); ?>"><?php echo e($job['title']); ?></option>
                        <?php endforeach; ?>
                        <option value="General Application">General Application / Other</option>
                    </select>
                </div>

                <div>
                    <label for="app-portfolio" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Portfolio / LinkedIn / GitHub URL</label>
                    <input type="url" id="app-portfolio" name="portfolio_url" placeholder="https://"
                           style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                </div>

                <div>
                    <label for="app-resume" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Resume / CV (PDF, DOC — max 5MB) *</label>
                    <input type="file" id="app-resume" name="resume" accept=".pdf,.doc,.docx" required
                           style="width:100%; padding:12px 14px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.9rem; background:var(--color-white);">
                </div>

                <div>
                    <label for="app-message" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Why do you want to work with us?</label>
                    <textarea id="app-message" name="message" rows="5" placeholder="A few lines about your background and why this role interests you."
                              style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white); resize:vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="border:none; width: fit-content;">Submit Application</button>
                <p style="font-size:0.85rem; margin-top:-8px;">We review every application — if there's a fit, we'll reach out within a week.</p>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>