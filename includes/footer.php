<?php
// ==========================================================
// Buildupnet — Footer
// Place this file at: /includes/footer.php
// Included at the bottom of every page via: include 'includes/footer.php';
// ==========================================================
if (!defined('SITE_URL')) { require_once __DIR__ . '/config.php'; }
?>

<!-- ===== CTA Band ===== -->
<section class="cta-band">
    <div class="container cta-band-inner">
        <div>
            <h2>Have a project in mind?</h2>
            <p>Tell us what you're trying to build — we'll reply with a clear plan, timeline and price. No pressure, no jargon.</p>
        </div>
        <a href="#" data-open-quote class="btn btn-light">Start the Conversation</a>
    </div>
</section>

</main>

<!-- ===== Footer ===== -->
<footer class="site-footer">
    <div class="container footer-grid">

        <div class="footer-col footer-brand">
            <a href="<?php echo SITE_URL; ?>/" class="footer-logo">
                Build<span class="footer-logo-accent">Up</span>Net
            </a>
            <p>We design, build and maintain digital products for businesses across India — and stay around long after launch.</p>
            <div class="footer-social">
                <a href="<?php echo SOCIAL_FACEBOOK; ?>" target="_blank" rel="noopener" aria-label="Facebook">FB</a>
                <a href="<?php echo SOCIAL_TWITTER; ?>" target="_blank" rel="noopener" aria-label="X / Twitter">X</a>
                <a href="<?php echo SOCIAL_LINKEDIN; ?>" target="_blank" rel="noopener" aria-label="LinkedIn">in</a>
                <a href="<?php echo SOCIAL_YOUTUBE; ?>" target="_blank" rel="noopener" aria-label="YouTube">YT</a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Company</h4>
            <a href="<?php echo SITE_URL; ?>/about.php">About Us</a>
            <a href="<?php echo SITE_URL; ?>/portfolio.php">Our Work</a>
            <a href="<?php echo SITE_URL; ?>/careers.php">Careers</a>
            <a href="<?php echo SITE_URL; ?>/blog.php">Blog</a>
            <a href="<?php echo SITE_URL; ?>/services.php">Services</a>
            <a href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
        </div>

        <div class="footer-col">
            <h4>Services</h4>
            <a href="<?php echo SITE_URL; ?>/services.php#web-development">Web Development</a>
            <a href="<?php echo SITE_URL; ?>/services.php#app-development">App Development</a>
            <a href="<?php echo SITE_URL; ?>/services.php#digital-marketing">Digital Marketing</a>
            <a href="<?php echo SITE_URL; ?>/services.php#networking">Networking & AMC</a>
        </div>

        <div class="footer-col">
            <h4>Get in Touch</h4>
            <p><?php echo SITE_ADDRESS; ?></p>
            <p><a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a></p>
            <p><a href="tel:<?php echo SITE_PHONE_RAW; ?>"><?php echo SITE_PHONE; ?></a></p>
        </div>

    </div>

    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p>&copy; <?php echo date('Y'); ?> Buildupnet. All rights reserved.</p>
            <div class="footer-legal">
                <a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a>
                <a href="<?php echo SITE_URL; ?>/terms.php">Terms & Conditions</a>
            </div>
        </div>
    </div>
</footer>

<a href="https://wa.me/<?php echo SITE_PHONE_RAW; ?>" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Chat on WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.07L2 22l5.07-1.33C8.55 21.5 10.27 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.5 0-2.9-.4-4.13-1.1l-.3-.17-3.07.8.82-3-.18-.31A8 8 0 1 1 20 12a8 8 0 0 1-8 8z"/></svg>
</a>

<?php include __DIR__ . '/quote-modal.php'; ?>

<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
