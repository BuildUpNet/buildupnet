<?php
// ==========================================================
// Database connection (loaded by includes/config.php).
// Public pages keep working when the DB is down ($conn stays null);
// admin pages show a short, safe error page instead.
// ==========================================================
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'db.php') { http_response_code(403); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);   // errors become exceptions on every PHP version

$conn = null;
$isAdminRequest = strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false;

try {
    $cfg  = require __DIR__ . '/db-config.php';
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('DB connection failed: ' . $e->getMessage());   // details only in the server log, never on screen
    $conn = null;
}
unset($cfg);

if ($isAdminRequest) {
    // Last-resort handler: log the real error, show a generic page (no passwords, no stack trace).
    set_exception_handler(function (Throwable $e) {
        error_log('Admin error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) http_response_code(500);
        exit('<!DOCTYPE html><meta charset="utf-8"><title>Error</title><body style="font-family:sans-serif;padding:40px;color:#444">'
           . '<h2>Something went wrong</h2><p>The action could not be completed. Check the server error log.</p>'
           . '<p><a href="javascript:history.back()">Go back</a></p></body>');
    });
    if ($conn === null) {
        http_response_code(503);
        exit('<!DOCTYPE html><meta charset="utf-8"><title>Database unavailable</title><body style="font-family:sans-serif;padding:40px;color:#444">'
           . '<h2>Database connection failed</h2><p>Check the 4 values in <code>includes/db-config.php</code> '
           . 'and that <code>database.sql</code> was imported.</p></body>');
    }
}

/** Run a SELECT and return all rows. Returns [] if the DB is down — public pages must never crash. */
function dbRows(string $sql, string $types = '', array $params = []): array {
    global $conn;
    if (!$conn) return [];
    try {
        if ($params) {
            $st = $conn->prepare($sql);
            $st->bind_param($types, ...$params);
            $st->execute();
            return $st->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) {
        error_log('dbRows failed: ' . $e->getMessage());
        return [];
    }
}

/** First row or null. */
function dbRow(string $sql, string $types = '', array $params = []): ?array {
    return dbRows($sql, $types, $params)[0] ?? null;
}
