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
    return ($current === $page) ? 'active' : '';
}

require_once __DIR__ . '/blog.php';
