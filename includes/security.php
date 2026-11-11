<?php
// ==========================================================
// Buildupnet — Shared security helpers (public site + admin)
// ==========================================================
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'security.php') { http_response_code(403); exit; }

/** Visitor IP. Uses REMOTE_ADDR only (X-Forwarded-For can be forged by the visitor). */
function clientIp(): string {
    return preg_replace('/[^0-9a-fA-F:.]/', '', (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) ?: '0.0.0.0';
}

/**
 * Rate limiting stored in the `rate_limits` table (no files). Fails open if the DB is down.
 * rateLimitHit()      -> records a hit, returns TRUE when the limit is already reached
 * rateLimitExceeded() -> only checks
 * rateLimitClear()    -> forget this visitor for the bucket (after a successful login)
 */
function rateLimitCount(string $bucket, int $windowSeconds): int {
    $row = dbRow("SELECT COUNT(*) AS c FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at > (NOW() - INTERVAL ? SECOND)",
                 'ssi', [$bucket, clientIp(), $windowSeconds]);
    return (int)($row['c'] ?? 0);
}
function rateLimitExceeded(string $bucket, int $max, int $windowSeconds): bool {
    return rateLimitCount($bucket, $windowSeconds) >= $max;
}
function rateLimitHit(string $bucket, int $max, int $windowSeconds): bool {
    global $conn;
    if (!$conn) return false;
    try {
        // Insert first, then count — atomic pattern avoids TOCTOU race where concurrent
        // requests could all pass the SELECT check before any INSERT completes.
        $st = $conn->prepare("INSERT INTO rate_limits (bucket, ip) VALUES (?, ?)");
        $ip = clientIp(); $st->bind_param('ss', $bucket, $ip); $st->execute();
        if (random_int(1, 50) === 1) $conn->query("DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)");  // housekeeping
    } catch (Throwable $e) { error_log('rate limit insert failed: ' . $e->getMessage()); }
    // Now check: if the count (including the row we just inserted) exceeds the max, the limit is hit.
    return rateLimitCount($bucket, $windowSeconds) > $max;
}
function rateLimitClear(string $bucket): void {
    global $conn;
    if (!$conn) return;
    try { $st = $conn->prepare("DELETE FROM rate_limits WHERE bucket = ? AND ip = ?"); $ip = clientIp(); $st->bind_param('ss', $bucket, $ip); $st->execute(); }
    catch (Throwable $e) { error_log('rate limit clear failed: ' . $e->getMessage()); }
}

/**
 * Multi-layer anti-bot & anti-spam field generator.
 * 1) Classic decoy: website_url
 * 2) Secondary decoy: phone_confirm (bots eagerly auto-fill fields with "phone" or "email")
 * 3) Cryptographic time-gate token: signs current timestamp with HMAC so automated bots (< 3 sec) can be rejected.
 */
function honeypotField(): string {
    $now = time();
    $key = defined('FORM_SECRET_KEY') ? FORM_SECRET_KEY : 'bun_default_salt';
    $hash = hash_hmac('sha256', (string)$now, $key);
    $token = $now . ':' . $hash;

    return '<div style="position:absolute;left:-9999px;top:-9999px;height:0;overflow:hidden;opacity:0;pointer-events:none;" aria-hidden="true">'
         . '<label>Leave empty<input type="text" name="website_url" value="" tabindex="-1" autocomplete="off"></label>'
         . '<label>Do not fill<input type="text" name="phone_confirm" value="" tabindex="-1" autocomplete="off"></label>'
         . '<input type="hidden" name="form_time_token" value="' . e($token) . '">'
         . '</div>';
}

function honeypotTripped(): bool {
    if (trim((string)($_POST['website_url'] ?? '')) !== '') return true;
    if (trim((string)($_POST['phone_confirm'] ?? '')) !== '') return true;
    return false;
}

/**
 * Validates that the form was filled out by a human:
 * Rejects requests submitted faster than $minSeconds (default 3s) or older than $maxSeconds (default 24h).
 */
function formTimingSuspicious(int $minSeconds = 3, int $maxSeconds = 86400): bool {
    $token = (string)($_POST['form_time_token'] ?? '');
    if ($token === '' || strpos($token, ':') === false) {
        return true; // Token missing -> direct bot script POST
    }
    [$timestampStr, $hash] = explode(':', $token, 2);
    $timestamp = (int)$timestampStr;
    $key = defined('FORM_SECRET_KEY') ? FORM_SECRET_KEY : 'bun_default_salt';
    $expectedHash = hash_hmac('sha256', $timestampStr, $key);
    if (!hash_equals($expectedHash, $hash)) {
        return true; // Forged or tampered token
    }
    $elapsed = time() - $timestamp;
    if ($elapsed < $minSeconds) {
        return true; // Bot submitted faster than human speed
    }
    if ($elapsed > $maxSeconds) {
        return true; // Expired / stale form
    }
    return false;
}

/**
 * Intelligent content heuristic check:
 * Detects Cyrillic text, excessive links, spam TLDs, and commercial promotional keywords.
 */
function isSpamContent(string ...$texts): bool {
    $combined = implode(' ', $texts);
    if (trim($combined) === '') return false;

    // 1. Cyrillic / Russian script check (Buildupnet is an Indian IT firm; Cyrillic is 100% spam)
    if (preg_match('/[\p{Cyrillic}]/u', $combined)) {
        return true;
    }

    // 2. Excessive links (> 1 link in a contact inquiry is almost always link-building spam)
    $linkCount = preg_match_all('#https?://#i', $combined, $matches);
    if ($linkCount > 1) {
        return true;
    }

    // 3. Known spam TLDs or link shorteners
    if (preg_match('#https?://[^\s/]*\.(ru|xyz|top|click|buzz|rest|work|loan|stream|gq|cf|tk|ml)\b#i', $combined)) {
        return true;
    }
    if (preg_match('#\b(t\.me|telegram\.me|bit\.ly|tinyurl\.com|wa\.me)/#i', $combined)) {
        return true;
    }

    // 4. Common automated commercial spam keywords
    $spamKeywords = [
        'guest post', 'guest posting', 'sponsored post', 'sponsored article',
        'seo ranking', 'seo service', 'backlink', 'backlinks', 'da/pa', 'domain authority',
        'increase your ranking', 'ranking on google', 'organic traffic increase',
        'casino', 'crypto investment', 'forex trading', 'telegram channel',
        'viagra', 'cialis', 'adult dating', 'whatsapp marketing software',
        'bulk email service', 'unsolicited commercial', 'opt-in list',
        'commercial proposal', 'b2b leads', 'targeted leads'
    ];
    $lower = strtolower($combined);
    foreach ($spamKeywords as $kw) {
        if (strpos($lower, $kw) !== false) {
            return true;
        }
    }

    return false;
}

/** Only allow http(s) links (blocks javascript:, data:, etc.). Returns '' if unsafe. */
function safeUrl(?string $url): string {
    $url = trim((string)$url);
    return preg_match('#^https?://[^\s<>"\']+$#i', $url) ? $url : '';
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
 * Allow-list HTML sanitizer for blog content: keeps normal formatting (headings, lists, links, images,
 * tables, quotes) and removes <script>, <iframe>, <style>, on*= handlers and javascript:/data: URLs.
 */
function sanitizeHtml(string $html): string {
    if (trim($html) === '') return '';
    // Plain text pasted without any HTML tags: keep the paragraphs / line breaks
    if ($html === strip_tags($html)) {
        $paras = preg_split('/\R{2,}/', trim($html));
        return implode("\n", array_map(fn($p) => '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8'), false) . '</p>', $paras));
    }
    // A full page was pasted (<html><head>…<body>): keep only what is inside <body>
    if (preg_match('#<body[^>]*>(.*)</body>#is', $html, $m)) $html = $m[1];
    else $html = preg_replace('#<head\b.*?</head>#is', '', $html);
    $html = preg_replace('#<!DOCTYPE[^>]*>#i', '', $html);
    if (!class_exists('DOMDocument')) return sanitizeHtmlBasic($html);   // PHP "dom" extension switched off on the server
    $allowed = [
        'p','br','hr','h2','h3','h4','h5','h6','strong','b','em','i','u','s','blockquote','q','code','pre','small','sup','sub','mark',
        'ul','ol','li','dl','dt','dd','details','summary','a','img','figure','figcaption','table','thead','tbody','tfoot','tr','th','td','div','span','section','article',
    ];
    $attrs = ['href','title','alt','src','width','height','loading','class','id','colspan','rowspan','target','rel','lang'];
    $prev = libxml_use_internal_errors(true);
    $doc  = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    $root = $doc->getElementsByTagName('div')->item(0);          // our wrapper div (getElementById is unreliable on some libxml builds)
    if (!$root) return sanitizeHtmlBasic($html);

    $walk = function (DOMNode $node) use (&$walk, $allowed, $attrs) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) { $node->removeChild($child); continue; }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->nodeName);
            if (in_array($tag, ['script','style','iframe','object','embed','form','input','button','textarea','select','link','meta','base','svg','math','noscript','title','head'], true)) {
                $node->removeChild($child); continue;                         // drop entirely, with content
            }
            if ($tag === 'h1') {                                                // the page already has one <h1> (the post title)
                $h = $child->ownerDocument->createElement('h2');
                while ($child->firstChild) $h->appendChild($child->firstChild);
                $node->replaceChild($h, $child); $child = $h; $tag = 'h2';
            }
            if (!in_array($tag, $allowed, true)) {                              // unknown tag: keep its text/children
                $walk($child);
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child); continue;
            }
            foreach (iterator_to_array($child->attributes) as $a) {
                $n = strtolower($a->nodeName); $val = trim($a->nodeValue);
                $bad = !in_array($n, $attrs, true) || str_starts_with($n, 'on');
                if (!$bad && in_array($n, ['href','src'], true)) {
                    $clean = preg_replace('/[\x00-\x20]+/', '', $val);
                    $okRel = $clean === '' || $clean[0] === '#' || $clean[0] === '/' || !preg_match('/^[a-z][a-z0-9+.-]*:/i', $clean);
                    $okAbs = preg_match('#^(https?:|mailto:|tel:)#i', $clean);
                    if (!$okRel && !$okAbs) $bad = true;
                    // Posts live under /blog/..., so make site-relative links absolute (contact.php -> https://site/contact.php)
                    if (!$bad && $okRel && $clean !== '' && $clean[0] !== '#' && defined('SITE_URL')) {
                        $a->nodeValue = '';
                        $child->setAttribute($n, SITE_URL . '/' . ltrim($val, '/'));
                    }
                }
                if ($bad) $child->removeAttribute($a->nodeName);
            }
            if ($tag === 'a' && strtolower($child->getAttribute('target')) === '_blank') $child->setAttribute('rel', 'noopener noreferrer');
            $walk($child);
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out) !== '' ? $out : sanitizeHtmlBasic($html);          // never show an empty article because of a parser problem
}

/** Fallback cleaner without the DOM extension: allow-list of tags, every attribute removed except safe link/image URLs. */
function sanitizeHtmlBasic(string $html): string {
    $html = preg_replace('#<(script|style|iframe|object|embed|form|svg|head|title)\b.*?</\1>#is', '', $html);
    $html = preg_replace('#<(/?)h1\b#i', '<$1h2', $html);
    $html = strip_tags($html, '<p><br><hr><h2><h3><h4><strong><b><em><i><u><blockquote><ul><ol><li><a><img><table><thead><tbody><tr><th><td><details><summary><code><pre>');
    return preg_replace_callback('#<([a-z0-9]+)\b([^>]*)>#i', function ($m) {
        $tag = strtolower($m[1]); $keep = '';
        if (in_array($tag, ['a', 'img'], true)) {
            foreach (($tag === 'a' ? ['href'] : ['src', 'alt']) as $attr) {
                if (preg_match('#\b' . $attr . '\s*=\s*(["\'])(.*?)\1#is', $m[2], $a)) {
                    $v = trim(html_entity_decode($a[2]));
                    if (in_array($attr, ['href', 'src'], true) && !preg_match('#^(https?://|/|mailto:|tel:|\#)#i', $v)) continue;
                    $keep .= ' ' . $attr . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
        }
        return '<' . $tag . $keep . '>';
    }, $html);
}

/** Plain text from anything that may contain HTML (excerpts, meta descriptions). */
function plainText(string $text): string {
    $text = html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', '', $text)), ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $text));
}
