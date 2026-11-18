<?php
require_once __DIR__ . '/includes/config.php';
http_response_code(404);
$pageRobots = 'noindex, follow';
$pageTitle = 'Page Not Found | ' . SITE_NAME;
$pageDescription = 'The page you are looking for could not be found.';
include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container" style="text-align:center; padding: 80px 0;">
        <h1>404 — Page Not Found</h1>
        <p style="margin: 16px 0 28px;">The page you are looking for doesn't exist or may have moved.</p>
        <a href="<?php echo SITE_URL; ?>/" class="btn btn-primary">Back to Home</a>
        <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline" style="margin-left:8px;">Contact Us</a>
        <p style="margin-top:28px;">Or look at our
            <a href="<?php echo SITE_URL; ?>/services.php">services</a>,
            <a href="<?php echo SITE_URL; ?>/portfolio.php">work</a> or
            <a href="<?php echo SITE_URL; ?>/blog.php">blog</a>.</p>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
