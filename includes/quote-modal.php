<?php
if (!defined('SITE_URL')) { require_once __DIR__ . '/config.php'; }
?>

<div class="quote-modal-overlay" id="quoteModalOverlay">
    <div class="quote-modal" role="dialog" aria-modal="true" aria-labelledby="quoteModalTitle">
        <button class="quote-modal-close" id="quoteModalClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="quote-modal-head">
            <span class="eyebrow">Get a Free Quote</span>
            <h2 id="quoteModalTitle">Get in Touch</h2>
            <p style="font-size:0.92rem;">Tell us what you need and we'll get back to you within one business day.</p>
        </div>

        <?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
            <div style="background:#E7F5EC; border:1px solid var(--color-success); color:#256b42; padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:18px; font-size:0.9rem;">
                Thanks — your request has been sent. We'll get back to you shortly.
            </div>
        <?php endif; ?>

        <form action="<?php echo SITE_URL; ?>/forms/send-quote.php" method="POST" class="quote-modal-form">
            <input type="hidden" name="form_type" value="quote">
            <?php echo honeypotField(); ?>

            <div>
                <label for="qm-name">Name *</label>
                <input type="text" id="qm-name" name="name" required>
            </div>

            <div>
                <label for="qm-email">Email *</label>
                <input type="email" id="qm-email" name="email" required>
            </div>

            <div>
                <label for="qm-subject">Subject *</label>
                <input type="text" id="qm-subject" name="subject" required placeholder="e.g. New website for my business">
            </div>

            <div>
                <label for="qm-scope">Scope of Work *</label>
                <select id="qm-scope" name="service" required>
                    <option value="">Select scope of work</option>
                    <option value="Web Development">Web Development</option>
                    <option value="Ecommerce Website">Ecommerce Website</option>
                    <option value="Android App">Android App</option>
                    <option value="iOS App">iOS App</option>
                    <option value="SEO & Digital Marketing">SEO & Digital Marketing</option>
                    <option value="Networking / AMC">Networking / AMC</option>
                    <option value="Not sure yet">Not sure yet</option>
                </select>
            </div>

            <div>
                <label for="qm-message">Message *</label>
                <textarea id="qm-message" name="details" rows="4" required placeholder="Send us your query anytime!"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="border:none; width:100%; justify-content:center;">Send</button>
        </form>
    </div>
</div>