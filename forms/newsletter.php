<?php
// Handles the blog newsletter sign-up.
require_once __DIR__ . '/_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { goBack('blog.php', ''); }
guardForm('newsletter', 'blog.php', 'subscribed=1', 'subscribed=0', 3, 600);
$email = oneLine(field('email', 160));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { goBack('blog.php', 'subscribed=0'); }

sendNotification('Newsletter signup', "New newsletter subscriber: $email\n", $email);
goBack('blog.php', 'subscribed=1');
