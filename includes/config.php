<?php
// ==========================================================
// Buildupnet — Site Configuration
// Place this file at: /includes/config.php
// All other PHP files include this first.
// ==========================================================

// All dates and times (blog dates, attendance) use Indian time, whatever the hosting server's clock says
define('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

// Base URL — change this when moving from localhost to live server
define('SITE_URL', 'https://buildupnet.com'); // no trailing slash (must match the .htaccess redirect: non-www)

// Company info (used in header, footer, contact page, schema)
define('SITE_NAME', 'Buildupnet');
define('SITE_TAGLINE', 'Your Trusted IT Partner');
define('SITE_PHONE', '+91 98887 47494');
define('SITE_PHONE_RAW', '919888747494'); // for tel: and wa.me links
define('SITE_PHONE2', '+91 98788 69927');
define('SITE_PHONE2_RAW', '919878869927');
define('SITE_EMAIL', 'support@buildupnet.com');
define('SITE_ADDRESS', 'Co Grover Mechanical Shop, Bhalla Street, Outside Sunami Gate, Sangrur, Punjab 148001');

// Social links
define('SOCIAL_FACEBOOK', 'https://www.facebook.com/share/18VHzhbdUK/');
define('SOCIAL_TWITTER', 'https://x.com/buildupnet86816');
define('SOCIAL_LINKEDIN', 'https://www.linkedin.com/in/buildupnet-team-759a6516a');
define('SOCIAL_YOUTUBE', 'https://youtube.com/@buildupnetteam');

// ---------- SEO / Schema constants ----------
// Default social-share image — replace with a real 1200x630 image at this path.
// Uses og-image.jpg if you upload one, otherwise falls back to the logo (avoids a 404)
define('OG_IMAGE_DEFAULT', SITE_URL . (file_exists(__DIR__ . '/../assets/img/og-image.jpg') ? '/assets/img/og-image.jpg' : '/assets/img/logo/logo.webp'));

// Logo used in Organization schema (square/landscape works for schema, unlike OG image)
define('SCHEMA_LOGO', SITE_URL . '/assets/img/logo/logo.webp');

// ---------- Search engine verification & analytics (leave '' until you have the codes) ----------
// Google Search Console -> Settings -> Ownership verification -> "HTML tag": copy ONLY the content="..." value.
define('GOOGLE_SITE_VERIFICATION', '');
define('BING_SITE_VERIFICATION', '');       // Bing Webmaster Tools -> msvalidate.01 content value
define('GA4_MEASUREMENT_ID', '');           // Google Analytics 4, looks like G-XXXXXXXXXX

/** Cut text to $max characters at a word boundary (Google cuts long titles/descriptions anyway). */
function seoTrim(string $text, int $max, string $suffix = ''): string {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $max) return $text;
    $cut = mb_substr($text, 0, $max - mb_strlen($suffix));
    $sp  = mb_strrpos($cut, ' ');
    return rtrim($sp > 20 ? mb_substr($cut, 0, $sp) : $cut, " ,.;:-—|") . $suffix;
}

// Approx. coordinates for Sangrur, Punjab (used in geo meta + LocalBusiness schema)
define('GEO_LAT', '30.2458');
define('GEO_LNG', '75.8421');

define('SITE_FOUNDING_YEAR', '2020');

// Business hours — ONE source used by the top bar, contact page and schema (they used to disagree).
// >>> Confirm these are correct for your business, then edit here only. <<<
define('SITE_HOURS_TEXT', 'Mon–Fri, 9:00 AM – 6:00 PM');
define('SITE_HOURS_LONG', 'Monday – Friday, 9:00 AM – 6:00 PM');

// Blog categories + badge colours (used by the blog pages and the admin post form)
const BLOG_CATEGORIES = [
    'Web Development'   => ['bg' => '#E8F4FB', 'color' => '#005470'],
    'SEO'               => ['bg' => '#E8F5EE', 'color' => '#2D7A52'],
    'Digital Marketing' => ['bg' => '#FEF3E8', 'color' => '#B25A00'],
    'App Development'   => ['bg' => '#F0EAFF', 'color' => '#5B3EA6'],
    'Networking'        => ['bg' => '#FDE8EE', 'color' => '#A62D48'],
    'Business Tips'     => ['bg' => '#E7F0F4', 'color' => '#005470'],
];

/**
 * The services shown in the footer, blog boxes, Work page and "You may also need".
 * They come from the service_pages table (Admin -> Service Pages). Until upgrade-service-pages.sql has been run,
 * the list below is used and every link goes to the matching card on services.php, so nothing breaks.
 */
const SERVICE_FALLBACK = [
    ['slug' => 'web-development',    'label' => 'Web Development',    'anchor' => 'web-development',    'cat' => 'Web Development',   'kw' => 'website,web development,web design,portal,school,landing'],
    ['slug' => 'ecommerce-websites', 'label' => 'Ecommerce Websites', 'anchor' => 'ecommerce',          'cat' => 'Web Development',   'kw' => 'ecommerce,e-commerce,shop,store'],
    ['slug' => 'app-development',    'label' => 'App Development',    'anchor' => 'app-development',    'cat' => 'App Development',   'kw' => 'app,android,ios,mobile'],
    ['slug' => 'seo-services',       'label' => 'SEO Services',       'anchor' => 'seo',                'cat' => 'SEO',               'kw' => 'seo,local seo,google business'],
    ['slug' => 'aeo-services',       'label' => 'AEO (Answer Engine Optimization)',      'anchor' => 'aeo', 'cat' => 'SEO', 'kw' => 'seo,website,local seo,google business'],
    ['slug' => 'geo-services',       'label' => 'GEO (Generative Engine Optimization)',  'anchor' => 'geo', 'cat' => 'SEO', 'kw' => 'seo,website,local seo,google business'],
    ['slug' => 'digital-marketing',  'label' => 'Digital Marketing',  'anchor' => 'digital-marketing',  'cat' => 'Digital Marketing', 'kw' => 'marketing,social,ads,content,campaign'],
    ['slug' => 'networking-amc',     'label' => 'Networking & AMC',   'anchor' => 'networking',         'cat' => 'Networking',        'kw' => 'network,amc,cisco'],
];

/** True when the service_pages table exists and has published pages (checked once per request). */
function servicePagesLive(): bool {
    return count(servicesList(true)) > 0;
}

/** Services in menu order: [slug, label, url, cat, kw, live]. */
function servicesList(bool $liveOnly = false): array {
    static $live = null, $list = null;
    if ($list === null) {
        $list = [];
        try {
            foreach (dbRows("SELECT slug, nav_label, blog_category, work_keywords FROM service_pages WHERE status = 'published' ORDER BY sort_order, id") as $r) {
                $list[] = ['slug' => $r['slug'], 'label' => $r['nav_label'], 'url' => SITE_URL . '/services/' . rawurlencode($r['slug']),
                           'cat' => (string)$r['blog_category'], 'kw' => (string)$r['work_keywords']];
            }
        } catch (Throwable $e) { $list = []; }
        $live = $list !== [];
        if (!$live) {
            foreach (SERVICE_FALLBACK as $f) $list[] = ['slug' => $f['slug'], 'label' => $f['label'], 'url' => SITE_URL . '/services.php#' . $f['anchor'], 'cat' => $f['cat'], 'kw' => $f['kw']];
        }
    }
    return ($liveOnly && !$live) ? [] : $list;
}

/** Address of a service (by slug); unknown slugs go to the Services page. */
function serviceUrl(string $slug): string {
    foreach (servicesList() as $s) if ($s['slug'] === $slug) return $s['url'];
    return SITE_URL . '/services.php';
}

/** Services for a blog category; categories with none get the first three services. */
function servicesForCategory(string $category): array {
    $all = servicesList();
    $hit = array_values(array_filter($all, fn($s) => $s['cat'] !== '' && strcasecmp($s['cat'], $category) === 0));
    return $hit ?: array_slice($all, 0, 3);
}

/** The service a portfolio category belongs to (first service whose keywords appear in the category; else the first service). */
function serviceForWork(?string $category): array {
    $c = strtolower((string)$category);
    $all = servicesList();
    foreach ($all as $s) {
        foreach (explode(',', strtolower($s['kw'])) as $w) {
            $w = trim($w);
            if ($w !== '' && strpos($c, $w) !== false) return $s;
        }
    }
    return $all[0];
}

/** Colours of a blog category chip (same look as on the blog page). */
function categoryChipStyle(?string $category): string {
    $c = BLOG_CATEGORIES[(string)$category] ?? ['bg' => '#E7F0F4', 'color' => '#005470'];
    return 'background:' . $c['bg'] . '; color:' . $c['color'] . ';';
}

/** Lines "Left :: Right" -> [[left, right], ...]. A line without "::" becomes [line, '']. */
function pairLines(?string $text): array {
    $out = [];
    foreach (preg_split('/\R/', (string)$text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = explode('::', $line, 2);
        $out[] = [trim($parts[0]), trim($parts[1] ?? '')];
    }
    return $out;
}

/** Newest posts for the footer; a database problem must never break a page. */
function footerLatestPosts(int $limit = 3): array {
    try { return blogPosts($limit); } catch (Throwable $e) { return []; }
}

/** Visible breadcrumb line. $trail = [[label, url or null], ...] after "Home"; the last item is the current page. */
function breadcrumbHtml(array $trail): string {
    $out = '<nav class="breadcrumb" aria-label="Breadcrumb"><div class="container"><a href="' . e(SITE_URL) . '/">Home</a>';
    $last = count($trail) - 1;
    foreach ($trail as $i => [$label, $url]) {
        $out .= '<span class="bc-sep" aria-hidden="true">/</span>';
        $out .= ($i === $last || !$url) ? '<span aria-current="page">' . e($label) . '</span>' : '<a href="' . e($url) . '">' . e($label) . '</a>';
    }
    return $out . '</div></nav>' . "\n";
}

/** JSON for <script type="application/ld+json"> — safe against quotes, </script>, unicode. */
function ldJson(array $data): string {
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRETTY_PRINT);
}

// Helper: build the canonical URL for the current page
function canonicalUrl() {
    // SCRIPT_NAME never contains attacker-supplied PATH_INFO (PHP_SELF does) -> no XSS
    $path = basename($_SERVER['SCRIPT_NAME']);
    if ($path === 'index.php') {
        return SITE_URL . '/';
    }
    return SITE_URL . '/' . rawurlencode($path);
}

// Helper: highlight active nav link
function activePage($page) {
    $current = basename($_SERVER['SCRIPT_NAME']);
    if ($current === 'service.php') $current = 'services.php';     // a service page lights up "Services"
    if ($current === 'blog-post.php') $current = 'blog.php';       // a blog post lights up "Blog"
    return ($current === $page) ? 'active' : '';
}

require_once __DIR__ . '/blog.php';
