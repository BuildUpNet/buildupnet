<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');          // do not trim passwords
    $ipKey    = 'login-ip';
    $userKey  = 'login-user-' . strtolower($username);

    if (rateLimitExceeded($ipKey, 8, 900) || rateLimitExceeded($userKey, 5, 900)) {
        http_response_code(429);
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif ($username !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT id, name, username, password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // always run a hash check so response time does not reveal whether the username exists
        $hash = $user['password'] ?? '$2y$10$usesomesillystringforsalt$2y$10$abcdefghijklmnopqrstuv';
        $ok   = password_verify($password, $hash) && $user;

        if ($ok) {
            rateLimitClear($ipKey); rateLimitClear($userKey);
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $nh = password_hash($password, PASSWORD_DEFAULT);
                $u  = $conn->prepare("UPDATE admin_users SET password=? WHERE id=?");
                $uid = (int)$user['id']; $u->bind_param('si', $nh, $uid); $u->execute();
            }
            loginAdmin($user);
            header('Location: dashboard.php');
            exit;
        }
        rateLimitHit($ipKey, 8, 900); rateLimitHit($userKey, 5, 900);
        usleep(400000);                                   // slow down guessing
        $error = 'Incorrect username or password.';
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Buildupnet</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="login-logo-mark"><img
                src="assets/images/logo/logo.webp"
                alt="Buildupnet Logo">
                 </div>
            <h1>Buildupnet Admin</h1>
            <p>Sign in to manage your website</p>
        </div>

        <?php if ($error): ?>
            <div class="login-error"><?php echo clean($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'session'): ?>
            <div class="login-error">Your session expired. Please sign in again.</div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus
                       value="<?php echo clean($_POST['username'] ?? ''); ?>" autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div style="margin-top: 20px;">
                <button type="submit" class="abtn abtn-primary">Sign In</button>
            </div>
        </form>
    </div>
    <script src="assets/js/admin.js"></script>
</body>
</html>
