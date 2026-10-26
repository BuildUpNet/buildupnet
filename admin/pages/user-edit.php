<?php
// Add / edit a team account — owner only.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('users');

$id   = (int)($_GET['id'] ?? 0);
$user = $id ? dbRow("SELECT * FROM admin_users WHERE id = ?", 'i', [$id]) : null;
if ($id && !$user) { header('Location: users.php'); exit; }

$errors = [];
$in = $user ?? ['name' => '', 'username' => '', 'role' => 'developer', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $name     = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 120);
    $username = strtolower(trim((string)($_POST['username'] ?? '')));
    $role     = (string)($_POST['role'] ?? '');
    $email    = mb_substr(trim((string)($_POST['email'] ?? '')), 0, 160);
    $phone    = mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 40);
    $in = array_merge($in, compact('name', 'username', 'role', 'email', 'phone'));

    if ($name === '') $errors[] = 'Name is required.';
    if (!preg_match('/^[a-z0-9._-]{3,60}$/', $username)) $errors[] = 'Username: 3–60 characters, only letters, numbers, dot, dash, underscore.';
    if (!isset(ROLE_PERMISSIONS[$role])) $errors[] = 'Choose a valid role.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
    if (!$errors && dbRow("SELECT id FROM admin_users WHERE username = ? AND id <> ?", 'si', [$username, $id])) $errors[] = 'This username is already taken.';

    // Never leave the system without an active owner (or lock yourself out by changing your own role)
    if (!$errors && $user && $user['role'] === 'owner' && $role !== 'owner') {
        $owners = (int)(dbRow("SELECT COUNT(*) AS c FROM admin_users WHERE role = 'owner' AND is_active = 1")['c'] ?? 0);
        if ($owners <= 1) $errors[] = 'This is the last active owner — make someone else an owner first.';
        elseif ($id === currentUserId()) $errors[] = 'You cannot remove your own owner role.';
    }

    if (!$errors) {
        if ($id) {
            $st = $conn->prepare("UPDATE admin_users SET name=?, username=?, role=?, email=?, phone=? WHERE id=?");
            $st->bind_param('sssssi', $name, $username, $role, $email, $phone, $id); $st->execute();
            flashMessage('success', 'User updated.');
        } else {
            $temp = generateTempPassword();
            $hash = password_hash($temp, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO admin_users (name, username, password, role, email, phone, must_change_password) VALUES (?,?,?,?,?,?,1)");
            $st->bind_param('ssssss', $name, $username, $hash, $role, $email, $phone); $st->execute();
            $_SESSION['temp_password'] = ['name' => $name, 'password' => $temp];
        }
        header('Location: users.php'); exit;
    }
}

$pageTitle = $user ? 'Edit User' : 'Add User';
$v = fn($k) => clean((string)($in[$k] ?? ''));
include __DIR__ . '/../includes/admin-header.php';
?>
<?php foreach ($errors as $err): ?><div class="flash flash-error" style="background:#FDECEA;border-left:4px solid #c0392b;color:#c0392b;"><?php echo clean($err); ?></div><?php endforeach; ?>
<div class="card">
    <div class="card-header"><h2><?php echo $pageTitle; ?></h2><a href="users.php" class="abtn abtn-outline abtn-sm">← Back</a></div>
    <div class="card-body">
        <form method="POST" autocomplete="off"><?php echo csrfField(); ?>
            <div class="form-grid">
                <div class="form-group"><label>Full name *</label><input type="text" name="name" required maxlength="120" value="<?php echo $v('name'); ?>"></div>
                <div class="form-group"><label>Username *</label><input type="text" name="username" required maxlength="60" value="<?php echo $v('username'); ?>" autocapitalize="none"></div>
                <div class="form-group"><label>Role *</label>
                    <select name="role"><?php foreach (ROLE_LABELS as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($in['role'] ?? '') === $k ? 'selected' : ''; ?>><?php echo clean($l); ?></option><?php endforeach; ?></select>
                    <span class="hint">Owner: everything · Manager: projects, prices, enquiries, jobs · Developer: only assigned projects, no prices.</span></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" maxlength="160" value="<?php echo $v('email'); ?>"></div>
                <div class="form-group"><label>Phone</label><input type="tel" name="phone" maxlength="40" value="<?php echo $v('phone'); ?>"></div>
            </div>
            <?php if (!$user): ?><p class="hint" style="margin-top:14px;">A temporary password is created automatically and shown to you once after saving.</p><?php endif; ?>
            <div class="form-actions" style="margin-top:20px;"><button type="submit" class="abtn abtn-primary"><?php echo $user ? 'Save changes' : 'Create user'; ?></button><a href="users.php" class="abtn abtn-outline">Cancel</a></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
