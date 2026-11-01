<?php
// Streams the Offer / Joining letter as a PDF. Built fresh from the database each time (nothing is stored on disk).
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

hireLog($id, 'letter_downloaded', ucfirst($type) . ' letter downloaded.');
$name = ($type === 'offer' ? 'Offer-Letter-' : 'Joining-Letter-') . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $h['full_name']), '-') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
