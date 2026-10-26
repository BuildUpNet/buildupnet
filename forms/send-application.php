<?php
// Handles the Careers application form (with resume upload).
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { goBack('careers.php', ''); }

guardForm('application', 'careers.php', 'sent=1#apply', 'sent=0#apply', 3, 900);
$name  = oneLine(field('name', 120));
$email = oneLine(field('email', 160));
$phone = oneLine(field('phone', 40));
$position   = oneLine(field('position', 160));
$experience = oneLine(field('experience', 80));
$portfolio  = oneLine(field('portfolio_url', 300));
$message    = field('message', 4000);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $position === '') { goBack('careers.php', 'sent=0#apply'); }
if ($portfolio !== '' && !preg_match('#^https?://#i', $portfolio)) { $portfolio = ''; }

// ---- Resume upload: pdf/doc/docx, max 5 MB, real type checked, random name ----
$resumeFile = '';
$f = $_FILES['resume'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024 || !is_uploaded_file($f['tmp_name'])) {
    goBack('careers.php', 'sent=0#apply');
}
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
$okTypes = [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
];
if (!isset($okTypes[$ext]) || !in_array($mime, $okTypes[$ext], true)) { goBack('careers.php', 'sent=0#apply'); }

// Content check on top of the MIME check (a renamed random file must not pass)
$head = (string)file_get_contents($f['tmp_name'], false, null, 0, 8);
$valid = false;
if ($ext === 'pdf')  { $valid = strncmp($head, '%PDF-', 5) === 0; }
if ($ext === 'doc')  { $valid = strncmp($head, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 8) === 0; }
if ($ext === 'docx') {
    $valid = strncmp($head, "PK", 2) === 0;
    if ($valid && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $valid = $zip->open($f['tmp_name']) === true && $zip->locateName('word/document.xml') !== false;
        if ($valid) $zip->close();
    }
}
if (!$valid) { goBack('careers.php', 'sent=0#apply'); }

$dir = __DIR__ . '/../admin/uploads/resumes/';
if (!is_dir($dir)) { mkdir($dir, 0750, true); }
if (!is_file($dir . '.htaccess')) { @file_put_contents($dir . '.htaccess', "Require all denied\n"); }   // never web-accessible
$resumeFile = 'resume_' . bin2hex(random_bytes(12)) . '.' . $ext;
if (!move_uploaded_file($f['tmp_name'], $dir . $resumeFile)) { goBack('careers.php', 'sent=0#apply'); }

$saved = false;
if ($conn = dbOrNull()) {
    try {
        $stmt = $conn->prepare("INSERT INTO job_applications (name,email,phone,position,experience,portfolio_url,resume_file,message) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssssss', $name, $email, $phone, $position, $experience, $portfolio, $resumeFile, $message);
        $saved = $stmt->execute();
    } catch (Throwable $e) { error_log('application save failed: ' . $e->getMessage()); }
}
$body = "New job application.\n\nName: $name\nEmail: $email\nPhone: $phone\nPosition: $position\nExperience: $experience\nPortfolio: $portfolio\nResume file: $resumeFile\n\nMessage:\n$message\n";
$mailed = sendNotification('Job Application — ' . $position . ' — ' . $name, $body, $email);

if (!$saved && !$mailed) { @unlink($dir . $resumeFile); }   // nothing recorded -> do not leave an orphan file
goBack('careers.php', ($saved || $mailed) ? 'sent=1#apply' : 'sent=0#apply');
