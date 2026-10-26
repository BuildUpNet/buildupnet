<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $current = (string)($_POST['current'] ?? '');
    $new     = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    $adminId = (int)$_SESSION['admin_id'];
    $user    = dbRow("SELECT password FROM admin_users WHERE id = ?", 'i', [$adminId]);

    if (rateLimitHit('pwchange', 5, 900))            $error = 'Too many attempts. Try again in 15 minutes.';
    elseif (!$user || !password_verify($current, $user['password'])) $error = 'Current password is incorrect.';
    elseif (strlen($new) < 10)                       $error = 'New password must be at least 10 characters.';
    elseif ($new !== $confirm)                       $error = 'The two new passwords do not match.';
    elseif ($new === $current)                       $error = 'Choose a password different from the current one.';
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $st = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $st->bind_param('si', $hash, $adminId); $st->execute();
        session_regenerate_id(true);
        flashMessage('success', 'Password changed.');
        header('Location: password.php'); exit;
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<div class="card">
    <div class="card-header"><h2>Change Password</h2></div>
    <div class="card-body">
        <?php if ($error): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($error); ?></div><?php endif; ?>
        <form method="POST" autocomplete="off" style="max-width:420px;">
            <?php echo csrfField(); ?>
            <div class="form-group"><label>Current password</label><input type="password" name="current" required autocomplete="current-password"></div>
            <div class="form-group"><label>New password (min 10 characters)</label><input type="password" name="new" required minlength="10" autocomplete="new-password"></div>
            <div class="form-group"><label>Repeat new password</label><input type="password" name="confirm" required minlength="10" autocomplete="new-password"></div>
            <div class="form-actions" style="margin-top:20px;"><button type="submit" class="abtn abtn-primary">Update Password</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
