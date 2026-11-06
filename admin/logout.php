<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Only allow logout via POST with a valid CSRF token to prevent logout-CSRF attacks.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
    // GET request or missing/invalid token — redirect to dashboard (no action taken).
    header('Location: ' . adminUrl('dashboard.php'));
    exit;
}

destroyAdminSession();
header('Location: index.php');
exit;
