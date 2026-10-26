<?php
// ==========================================================
// Buildupnet — Header / Navigation
// Place this file at: /includes/header.php
// Included at the top of every page via: include 'includes/header.php';
// Each page should set $pageTitle and $pageDescription before including this.
// ==========================================================

if (!defined('SITE_URL')) { require_once __DIR__ . '/config.php'; }

if (!isset($pageTitle)) $pageTitle = SITE_NAME . ' | ' . SITE_TAGLINE;
if (!isset($pageDescription)) $pageDescription = 'Buildupnet is a trusted IT partner in India delivering web design, app development and digital marketing with full transparency and long-term support.';
if (!isset($pageImage)) $pageImage = OG_IMAGE_DEFAULT;
if (!isset($pageType)) $pageType = 'website';

$canonical = $canonicalUrl ?? canonicalUrl();      // blog posts set their own pretty URL
if (!isset($pageRobots)) $pageRobots = 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
if (!isset($headExtra)) $headExtra = '';
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(seoTrim($pageTitle, 60)); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(seoTrim($pageDescription, 158, '…')); ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
<?php if (GOOGLE_SITE_VERIFICATION !== ''): ?>    <meta name="google-site-verification" content="<?php echo htmlspecialchars(GOOGLE_SITE_VERIFICATION); ?>">
<?php endif; if (BING_SITE_VERIFICATION !== ''): ?>    <meta name="msvalidate.01" content="<?php echo htmlspecialchars(BING_SITE_VERIFICATION); ?>">
<?php endif; ?>
    <meta name="robots" content="<?php echo htmlspecialchars($pageRobots); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical); ?>">
    <link rel="alternate" hreflang="en-in" href="<?php echo htmlspecialchars($canonical); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($canonical); ?>">
    <meta name="theme-color" content="#005470">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo htmlspecialchars($pageType); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical); ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="en_IN">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($pageImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars(SITE_NAME . ' - ' . SITE_TAGLINE); ?>">

<?php echo $headExtra; ?>
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@buildupnet86816">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($pageImage); ?>">

    <!-- Geo / Local SEO -->
    <meta name="geo.placename" content="Sangrur, Punjab, India">
    <meta name="geo.region" content="IN-PB">
    <meta name="geo.position" content="<?php echo GEO_LAT; ?>;<?php echo GEO_LNG; ?>">
    <meta name="ICBM" content="<?php echo GEO_LAT; ?>, <?php echo GEO_LNG; ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/img/logo/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/assets/img/logo/apple-touch-icon.png">

    <!-- ===== Organization / LocalBusiness Schema (sitewide) =====
         This structured data helps search engines AND AI answer engines
         (Google AI Overviews, ChatGPT, Perplexity, etc.) understand who
         Buildupnet is, where it's based, how to contact it, and which
         social profiles belong to it. -->
<?php
    // ===== Organization / LocalBusiness + WebSite schema (sitewide). People come from the `authors` table. =====
    $orgSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => ['LocalBusiness', 'ProfessionalService'],
                '@id' => SITE_URL . '/#organization',
                'name' => SITE_NAME, 'alternateName' => 'BuildUpNet',
                'url' => SITE_URL . '/', 'logo' => SCHEMA_LOGO, 'image' => OG_IMAGE_DEFAULT,
                'description' => 'Buildupnet is a full-service website design and digital marketing agency based in Punjab, India, offering web development, app development, SEO and IT support.',
                'telephone' => SITE_PHONE, 'email' => SITE_EMAIL, 'priceRange' => '₹₹',
                'foundingDate' => SITE_FOUNDING_YEAR,
                'areaServed' => ['@type' => 'Country', 'name' => 'India'],
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Bhalla Street, Outside Sunami Gate', 'addressLocality' => 'Sangrur',
                              'addressRegion' => 'Punjab', 'postalCode' => '148001', 'addressCountry' => 'IN'],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => GEO_LAT, 'longitude' => GEO_LNG],
                'openingHoursSpecification' => ['@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '09:00', 'closes' => '18:00'],
                'contactPoint' => ['@type' => 'ContactPoint', 'telephone' => SITE_PHONE, 'email' => SITE_EMAIL, 'contactType' => 'customer support',
                                   'areaServed' => 'IN', 'availableLanguage' => ['English', 'Hindi', 'Punjabi']],
                'employee' => array_map(fn($a) => ['@type' => 'Person', 'name' => $a['name'], 'jobTitle' => $a['role']], authors()),
                'sameAs' => [SOCIAL_FACEBOOK, SOCIAL_TWITTER, SOCIAL_LINKEDIN, SOCIAL_YOUTUBE],
            ],
            ['@type' => 'WebSite', '@id' => SITE_URL . '/#website', 'url' => SITE_URL . '/', 'name' => SITE_NAME,
             'inLanguage' => 'en-IN', 'publisher' => ['@id' => SITE_URL . '/#organization']],
        ],
    ];
    if (empty($orgSchema['@graph'][0]['employee'])) unset($orgSchema['@graph'][0]['employee']);

    // ===== Breadcrumbs for every inner page (blog posts add their own, home has one in index.php) =====
    $crumbNames = ['about.php' => 'About', 'services.php' => 'Services', 'portfolio.php' => 'Our Work', 'careers.php' => 'Careers',
                   'blog.php' => 'Blog', 'contact.php' => 'Contact', 'privacy-policy.php' => 'Privacy Policy', 'terms.php' => 'Terms & Conditions'];
    $thisPage = basename($_SERVER['SCRIPT_NAME']);
    $schemas  = [$orgSchema];
    if (isset($crumbNames[$thisPage])) {
        $schemas[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => SITE_URL . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $crumbNames[$thisPage], 'item' => $canonical],
        ]];
    }
    foreach ($schemas as $schema) echo '<script type="application/ld+json">' . ldJson($schema) . '</script>' . "\n";
?>
<?php if (GA4_MEASUREMENT_ID !== ''): ?>
    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo urlencode(GA4_MEASUREMENT_ID); ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo e(GA4_MEASUREMENT_ID); ?>',{anonymize_ip:true});</script>
<?php endif; ?>
</head>
<body>

<!-- ===== Top Trust Bar ===== -->
<div class="top-bar">
    <div class="container top-bar-inner">
        <div class="top-bar-item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?php echo SITE_HOURS_TEXT; ?>
        </div>
        <div class="top-bar-item">
            <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>
            <span class="divider">|</span>
            <a href="tel:<?php echo SITE_PHONE_RAW; ?>"><?php echo SITE_PHONE; ?></a>
        </div>
    </div>
</div>

<!-- ===== Main Navigation ===== -->
<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a href="<?php echo SITE_URL; ?>/" class="logo">
            <img loading="lazy" decoding="async" src="<?php echo SITE_URL; ?>/assets/img/logo/logo.webp" alt="<?php echo SITE_NAME; ?>" class="logo-img" width="150" height="62" fetchpriority="high">
        </a>

        <nav class="main-nav" id="mainNav">
            <a href="<?php echo SITE_URL; ?>/" class="<?php echo activePage('index.php'); ?>">Home</a>
            <a href="<?php echo SITE_URL; ?>/about.php" class="<?php echo activePage('about.php'); ?>">About</a>
            <a href="<?php echo SITE_URL; ?>/services.php" class="<?php echo activePage('services.php'); ?>">Services</a>
            <a href="<?php echo SITE_URL; ?>/portfolio.php" class="<?php echo activePage('portfolio.php'); ?>">Work</a>
            <a href="<?php echo SITE_URL; ?>/careers.php" class="<?php echo activePage('careers.php'); ?>">Careers</a>
            <a href="<?php echo SITE_URL; ?>/blog.php" class="<?php echo activePage('blog.php'); ?>">Blog</a>
            <a href="<?php echo SITE_URL; ?>/contact.php" class="<?php echo activePage('contact.php'); ?>">Contact</a>
        </nav>

        <div class="header-cta">
            <a href="https://wa.me/<?php echo SITE_PHONE_RAW; ?>" target="_blank" rel="noopener" class="btn-whatsapp" aria-label="Chat on WhatsApp">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.85.5 3.58 1.38 5.07L2 22l5.07-1.33C8.55 21.5 10.27 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.5 0-2.9-.4-4.13-1.1l-.3-.17-3.07.8.82-3-.18-.31A8 8 0 1 1 20 12a8 8 0 0 1-8 8z"/></svg>
            </a>
            <a href="#" data-open-quote class="btn btn-primary">Get Free Quote</a>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main id="main-content">
