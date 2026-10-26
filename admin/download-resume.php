<?php
// Streams a resume to a logged-in admin only. Resumes are NOT web-accessible any other way.
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$file = (string)($_GET['file'] ?? '');
if (!preg_match('/^resume_[a-f0-9]{20,48}\.(pdf|doc|docx)$/', $file)) { http_response_code(404); exit('Not found'); }

// must belong to a real application row
$st = $conn->prepare("SELECT id FROM job_applications WHERE resume_file = ? LIMIT 1");
$st->bind_param('s', $file); $st->execute();
$path = __DIR__ . '/uploads/resumes/' . $file;
if (!$st->get_result()->fetch_assoc() || !is_file($path)) { http_response_code(404); exit('Not found'); }

$types = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
          'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$ext = pathinfo($file, PATHINFO_EXTENSION);
header('Content-Type: ' . $types[$ext]);
header('Content-Disposition: attachment; filename="' . $file . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
readfile($path);
