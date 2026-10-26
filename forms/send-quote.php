<?php
// Handles the Quote modal (form_type=quote) and the Contact page form.
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { goBack('contact.php', ''); }

$formType = field('form_type', 20) === 'quote' ? 'quote' : 'contact';
$name  = oneLine(field('name', 120));
$email = oneLine(field('email', 160));
$phone = oneLine(field('phone', 40));
$page  = $formType === 'quote' ? 'index.php' : 'contact.php';

guardForm('quote', $page, 'sent=1', 'sent=0', 4, 600);
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { goBack($page, 'sent=0'); }

if ($formType === 'quote') {
    $company  = oneLine(field('company', 160));
    $service  = oneLine(field('service', 120));
    $budget   = oneLine(field('budget', 120));
    $timeline = oneLine(field('timeline', 120));
    $details  = field('details', 4000);
    $subject  = 'New Quote Request — ' . $name;
    $message  = ($company !== '' ? "Company: $company\n" : '') . $details;
    $body = "New quote request from the website.\n\nName: $name\nCompany: $company\nEmail: $email\nPhone: $phone\n"
          . "Service: $service\nBudget: $budget\nTimeline: $timeline\n\nProject details:\n$details\n";
} else {
    $service = $budget = $timeline = '';
    $topic   = oneLine(field('subject', 160)) ?: 'New Contact Form Message';
    $message = field('message', 4000);
    $subject = 'Contact Form: ' . $topic;
    $body = "New message from the contact form.\n\nName: $name\nEmail: $email\nPhone: $phone\nSubject: $topic\n\nMessage:\n$message\n";
}

// Save to admin inbox (Enquiries) — failure here must not lose the lead
$saved = false;
if ($conn = dbOrNull()) {
    try {
        $stmt = $conn->prepare("INSERT INTO enquiries (name,email,phone,form_type,service,budget,timeline,message) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssssss', $name, $email, $phone, $formType, $service, $budget, $timeline, $message);
        $saved = $stmt->execute();
    } catch (Throwable $e) { error_log('enquiry save failed: ' . $e->getMessage()); }
}
$mailed = sendNotification($subject, $body, $email);

goBack($page, ($saved || $mailed) ? 'sent=1' : 'sent=0');
