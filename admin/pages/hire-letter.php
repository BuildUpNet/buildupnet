<?php
// Sends the Offer / Joining letter as a PDF DOWNLOAD. Built fresh from the database each time (nothing is stored on disk).
// It is sent as an attachment (not shown inside the browser) because the admin security headers block the browser's built-in PDF viewer.
ob_start();   // anything a PHP notice might print must never end up inside the PDF
ini_set('display_errors', '0');
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/pdf.php';
requireLogin();
requirePermission('hiring.manage');

$id   = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['type'] ?? '');
$h    = $id ? dbRow("SELECT * FROM hire_requests WHERE id = ?", 'i', [$id]) : null;
if (!$h || !in_array($type, ['offer', 'joining'], true) || !hireLetterAllowed($h, $type)) { http_response_code(404); exit('Letter not available.'); }

try { $pdf = hireLetterPdf($h, $type); }
catch (Throwable $e) { error_log('letter failed: ' . $e->getMessage()); http_response_code(500); exit('Could not create the letter. Check that upgrade-hiring.sql was run.'); }

$draft = !hireLetterReady($h, $type);     // owner took the letter early: file name says DRAFT so it is not sent by mistake
hireLog($id, 'letter_downloaded', ucfirst($type) . ' letter downloaded' . ($draft ? ' as a draft (not yet issued).' : '.'));
$name = ($draft ? 'DRAFT-' : '') . ($type === 'offer' ? 'Offer-Letter-' : 'Joining-Letter-') . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $h['full_name']), '-') . '.pdf';
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
