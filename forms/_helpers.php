<?php
// Shared helpers for form handlers. Not a public page.
require_once __DIR__ . '/../includes/config.php';
if (basename($_SERVER['SCRIPT_NAME']) === '_helpers.php') { http_response_code(403); exit; }

function field(string $key, int $max = 2000): string {
    $v = trim((string)($_POST[$key] ?? ''));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v);   // strip control chars (keep \n \t \r)
    return mb_substr($v, 0, $max);
}
function oneLine(string $v): string {            // for headers / subject: no line breaks
    return trim(preg_replace('/[\r\n]+/', ' ', $v));
}
function goBack(string $page, string $query): void {
    header('Location: ' . SITE_URL . '/' . $page . '?' . $query);
    exit;
}
function sendNotification(string $subject, string $body, string $replyTo = ''): bool {
    $headers  = 'From: ' . SITE_NAME . ' Website <no-reply@buildupnet.com>' . "\r\n";
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . oneLine($replyTo) . "\r\n";
    }
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n" . 'X-Mailer: Buildupnet-Website';
    return @mail(SITE_EMAIL, oneLine($subject), $body, $headers);
}
/** Returns the shared mysqli connection or null (forms still work by email if the DB is down). */
function dbOrNull(): ?mysqli {
    global $conn;
    return ($conn instanceof mysqli) ? $conn : null;
}

/**
 * Anti-spam gate for every public form: honeypot + per-IP rate limit.
 * Bots that fill the hidden field are told "sent" (so they do not retry); flooders are told "not sent".
 */
function guardForm(string $name, string $page, string $okQuery, string $failQuery, int $max = 5, int $window = 600): void {
    if (honeypotTripped()) { goBack($page, $okQuery); }
    if (rateLimitHit('form-' . $name, $max, $window)) { goBack($page, $failQuery); }
}
