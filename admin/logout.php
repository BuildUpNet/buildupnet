<?php
// Logout. The sidebar button sends a POST form with the CSRF token, so nobody can log you out by a link on another website.
require_once __DIR__ . '/includes/bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrfCheck();
    destroyAdminSession();
    header('Location: index.php');
    exit;
}
header('Location: dashboard.php');   // opened as a plain link: do nothing, go back to the dashboard
exit;
