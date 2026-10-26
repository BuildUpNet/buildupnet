<?php
require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Contact Buildupnet | Website & App Development, Sangrur';
$pageDescription = 'Get in touch with Buildupnet for web development, app development, SEO and digital marketing enquiries. Based in Sangrur, Punjab, serving clients across India.';

include __DIR__ . '/includes/header.php';
?>

<!-- ===== Page Hero ===== -->
<section class="hero" style="padding: 60px 0;">
    <div class="container">
        <div data-reveal style="max-width: 700px;">
            <span class="eyebrow">Contact</span>
            <h1>Let's talk about your project.</h1>
            <p class="lead">
                Send us a message, call, or message us on WhatsApp — whichever's easiest.
                We typically reply within one business day.
            </p>
        </div>
    </div>
</section>

<!-- ===== Contact Grid ===== -->
<section class="section">
    <div class="container">
        <div class="features-grid" style="align-items: start;">

            <!-- Contact Form -->
            <div data-reveal>
                <h2 style="margin-bottom: 20px;">Get in Touch</h2>

                <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
                    <div style="background:#E7F5EC; border:1px solid var(--color-success); color:#256b42; padding:16px 20px; border-radius:var(--radius-sm); margin-bottom:24px; font-size:0.92rem;">
                        Thanks — your message has been sent. We'll get back to you shortly.
                    </div>
                <?php endif; ?>

                <form action="forms/send-quote.php" method="POST" style="display:flex; flex-direction:column; gap:18px;">
                    <input type="hidden" name="form_type" value="contact">
                    <?php echo honeypotField(); ?>

                    <div>
                        <label for="name" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Full Name</label>
                        <input type="text" id="name" name="name" required
                               style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                    </div>

                    <div>
                        <label for="email" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Email Address</label>
                        <input type="email" id="email" name="email" required
                               style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                    </div>

                    <div>
                        <label for="phone" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white);">
                    </div>

                    <div>
                        <label for="message" style="display:block; font-size:0.88rem; font-weight:600; color:var(--color-ink); margin-bottom:6px;">Your Query</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Send us your query anytime!"
                                  style="width:100%; padding:13px 16px; border:1.5px solid var(--color-border); border-radius:var(--radius-sm); font-family:var(--font-body); font-size:0.95rem; background:var(--color-white); resize:vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="border:none; width: fit-content;">Send</button>
                </form>
            </div>

            <!-- Contact Details -->
            <div data-reveal>
                <h2 style="margin-bottom: 20px;">Reach us directly</h2>

                <div style="display:flex; flex-direction:column; gap:20px;">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <h4>Office Address</h4>
                            <p><?php echo SITE_ADDRESS; ?></p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.68 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.74.32 1.53.55 2.34.68A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <h4>Phone</h4>
                            <p><a href="tel:<?php echo SITE_PHONE_RAW; ?>"><?php echo SITE_PHONE; ?></a></p>
                            <p style="margin-top:-6px;"><a href="tel:<?php echo SITE_PHONE2_RAW; ?>"><?php echo SITE_PHONE2; ?></a></p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <h4>Email</h4>
                            <p><a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></p>
                            <p style="margin-top:-6px; font-size:0.85rem;">Send us your query anytime!</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <h4>Business Hours</h4>
                            <p><?php echo SITE_HOURS_LONG; ?></p>
                        </div>
                    </div>
                </div>

                <a href="https://wa.me/<?php echo SITE_PHONE_RAW; ?>" target="_blank" rel="noopener" class="btn btn-primary" style="margin-top:28px;">
                    Chat on WhatsApp
                </a>

                <!-- Map embed — replace src with your actual Google Maps embed link from Google Maps > Share > Embed -->
                <div style="margin-top:28px; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--color-border);">
                    <iframe
                        src="https://www.google.com/maps?q=Bhalla+Street,+Outside+Sunami+Gate,+Sangrur,+Punjab+148001&output=embed"
                        width="100%" height="240" style="border:0; display:block;"
                        allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Buildupnet office location map">
                    </iframe>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>