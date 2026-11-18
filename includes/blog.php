<?php
// ==========================================================
// Blog + authors — everything comes from the database (tables: blog_posts, authors).
// Manage posts in Admin → Blog Posts. Authors: edit the `authors` table in phpMyAdmin.
// ==========================================================
if (!defined('SITE_URL')) { http_response_code(403); exit; }

/** Public URL of a post: /blog/<slug> (rewritten to blog-post.php in .htaccess). */
function postUrl(string $slug): string {
    return SITE_URL . '/blog/' . rawurlencode($slug);
}

/** Turn a DB row into the array the templates use. */
function mapPost(array $r): array {
    $created = strtotime($r['created_at']) ?: time();
    $updated = !empty($r['updated_at']) ? strtotime($r['updated_at']) : null;
    $thumb   = (string)($r['thumbnail'] ?? '');
    if ($thumb !== '' && !is_file(__DIR__ . '/../' . $thumb)) $thumb = '';   // avoid 404 image requests
    return [
        'slug'         => $r['slug'],
        'title'        => $r['title'],
        'excerpt'      => plainText((string)$r['excerpt']),          // old posts may contain HTML here
        'category'     => $r['category'] ?: 'General',
        'author'       => $r['author'] ?: SITE_NAME,
        'author_role'  => $r['author_role'] ?? null,        // set only for people listed in the authors table
        'date'         => date('Y-m-d', $created),
        'date_display' => date('F j, Y', $created),
        'updated'      => $updated ? date('Y-m-d', $updated) : date('Y-m-d', $created),
        'read_time'    => $r['read_time'] ?: '5 min read',
        'thumbnail'    => $thumb,
        'content'      => (string)$r['content'],
        'meta_title'   => trim((string)($r['meta_title'] ?? '')),
        'meta_desc'    => plainText((string)($r['meta_description'] ?? '')),
        'keyword'      => trim((string)($r['focus_keyword'] ?? '')),
        'noindex'      => !empty($r['noindex']),
    ];
}

/** True once upgrade-blog-seo.sql has been run (the site keeps working before that, just without the SEO fields). */
function blogHasSeoColumns(): bool {
    static $has = null;
    if ($has === null) $has = (bool)dbRow("SHOW COLUMNS FROM blog_posts LIKE 'meta_title'");
    return $has;
}

function postSelect(): string {
    return "SELECT p.slug, p.title, p.excerpt, p.category, p.author, p.read_time, p.content, p.thumbnail, "
         . (blogHasSeoColumns() ? "p.meta_title, p.meta_description, p.focus_keyword, p.noindex, " : "")
         . "p.created_at, p.updated_at, a.role AS author_role
            FROM blog_posts p LEFT JOIN authors a ON a.name = p.author AND a.is_active = 1";
}

/** Published posts, newest first. */
function blogPosts(int $limit = 0): array {
    $rows = dbRows(postSelect() . " WHERE p.status = 'published' ORDER BY p.created_at DESC" . ($limit > 0 ? " LIMIT " . (int)$limit : ''));
    return array_map('mapPost', $rows);
}

function blogPostBySlug(string $slug): ?array {
    $row = dbRow(postSelect() . " WHERE p.status = 'published' AND p.slug = ? LIMIT 1", 's', [$slug]);
    return $row ? mapPost($row) : null;
}

function relatedPosts(string $category, string $currentSlug, int $limit = 2): array {
    $rows = dbRows(postSelect() . " WHERE p.status = 'published' AND p.category = ? AND p.slug <> ? ORDER BY p.created_at DESC LIMIT " . (int)$limit,
                   'ss', [$category, $currentSlug]);
    return array_map('mapPost', $rows);
}

/** Team members shown on the About page, in author boxes and in the Organization schema. */
function authors(): array {
    static $cache = null;
    if ($cache === null) $cache = dbRows("SELECT name, role, bio, photo FROM authors WHERE is_active = 1 ORDER BY sort_order, id");
    return $cache;
}
