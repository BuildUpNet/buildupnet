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
    if (rateLimitCount($bucket, $windowSeconds) >= $max) return true;
    try {
        $st = $conn->prepare("INSERT INTO rate_limits (bucket, ip) VALUES (?, ?)");
        $ip = clientIp(); $st->bind_param('ss', $bucket, $ip); $st->execute();
        if (random_int(1, 50) === 1) $conn->query("DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)");  // housekeeping
    } catch (Throwable $e) { error_log('rate limit insert failed: ' . $e->getMessage()); }
    return false;
}
function rateLimitClear(string $bucket): void {
    global $conn;
    if (!$conn) return;
    try { $st = $conn->prepare("DELETE FROM rate_limits WHERE bucket = ? AND ip = ?"); $ip = clientIp(); $st->bind_param('ss', $bucket, $ip); $st->execute(); }
    catch (Throwable $e) { error_log('rate limit clear failed: ' . $e->getMessage()); }
}

/** Hidden anti-bot field. Real users never fill it. */
function honeypotField(): string {
    return '<div style="position:absolute;left:-9999px;top:-9999px;height:0;overflow:hidden;" aria-hidden="true">'
         . '<label>Leave this empty<input type="text" name="website_url" value="" tabindex="-1" autocomplete="off"></label></div>';
}
function honeypotTripped(): bool {
    return trim((string)($_POST['website_url'] ?? '')) !== '';
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
    $allowed = [
        'p','br','hr','h2','h3','h4','h5','h6','strong','b','em','i','u','s','blockquote','q','code','pre','small','sup','sub','mark',
        'ul','ol','li','dl','dt','dd','a','img','figure','figcaption','table','thead','tbody','tfoot','tr','th','td','div','span','section','article',
    ];
    $attrs = ['href','title','alt','src','width','height','loading','class','id','colspan','rowspan','target','rel','lang'];
    $prev = libxml_use_internal_errors(true);
    $doc  = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) return '';

    $walk = function (DOMNode $node) use (&$walk, $allowed, $attrs) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) { $node->removeChild($child); continue; }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->nodeName);
            if (in_array($tag, ['script','style','iframe','object','embed','form','input','button','textarea','select','link','meta','base','svg','math','noscript'], true)) {
                $node->removeChild($child); continue;                         // drop entirely, with content
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
    return $out;
}
