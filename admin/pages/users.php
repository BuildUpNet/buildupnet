<?php
// Team accounts and roles — owner only.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
requirePermission('users');

/** How many active owners exist (the last one can never be switched off). */
function activeOwners(): int { return (int)(dbRow("SELECT COUNT(*) AS c FROM admin_users WHERE role = 'owner' AND is_active = 1")['c'] ?? 0); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $uid    = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $target = $uid ? dbRow("SELECT id, name, role, is_active FROM admin_users WHERE id = ?", 'i', [$uid]) : null;

    if (!$target) { flashMessage('error', 'User not found.'); }
    elseif ($action === 'toggle') {
        $turningOff = (int)$target['is_active'] === 1;
        if ($turningOff && $target['role'] === 'owner' && activeOwners() <= 1) {
            flashMessage('error', 'You cannot deactivate the last active owner.');
        } elseif ($turningOff && $uid === currentUserId()) {
            flashMessage('error', 'You cannot deactivate your own account.');
        } else {
            $st = $conn->prepare("UPDATE admin_users SET is_active = 1 - is_active WHERE id = ?"); $st->bind_param('i', $uid); $st->execute();
            flashMessage('success', $target['name'] . ($turningOff ? ' can no longer log in.' : ' can log in again.'));
        }
    } elseif ($action === 'reset') {
        $temp = generateTempPassword();
        $hash = password_hash($temp, PASSWORD_DEFAULT);
        $st = $conn->prepare("UPDATE admin_users SET password = ?, must_change_password = 1 WHERE id = ?"); $st->bind_param('si', $hash, $uid); $st->execute();
        $_SESSION['temp_password'] = ['name' => $target['name'], 'password' => $temp];       // shown once, then gone
    }
    header('Location: users.php'); exit;
}

$tempShown = $_SESSION['temp_password'] ?? null; unset($_SESSION['temp_password']);
$users = dbRows("SELECT u.*, (SELECT COUNT(*) FROM project_developers pd WHERE pd.user_id = u.id) AS project_count
                 FROM admin_users u ORDER BY is_active DESC, FIELD(role,'owner','manager','developer'), name");
$pageTitle = 'Users & Roles';
include __DIR__ . '/../includes/admin-header.php';
?>
<?php renderFlash(); ?>
<?php if ($tempShown): ?>
<div class="temp-pass">
    <strong>Temporary password for <?php echo clean($tempShown['name']); ?> — shown only once:</strong>
    <code><?php echo clean($tempShown['password']); ?></code>
    <span>Send it to them privately. They must choose their own password at first login.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h2><?php echo count($users); ?> team member<?php echo count($users) === 1 ? '' : 's'; ?></h2><a href="user-edit.php" class="abtn abtn-primary">+ Add user</a></div>
    <div class="role-legend">
        <span><b>Owner</b> — everything</span>
        <span><b>Manager</b> — projects &amp; prices, enquiries, applications, jobs</span>
        <span><b>Developer</b> — only their own projects, no prices</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table table-cards">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last login</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td data-label="Name" class="td-title"><?php echo clean($u['name']); ?><?php echo (int)$u['id'] === currentUserId() ? ' <span class="td-muted">(you)</span>' : ''; ?>
                        <div class="td-muted"><?php echo clean($u['email'] ?: $u['phone'] ?: ''); ?></div></td>
                    <td data-label="Username" class="td-muted"><?php echo clean($u['username']); ?></td>
                    <td data-label="Role"><span class="abadge <?php echo ['owner' => 'abadge-orange', 'manager' => 'abadge-blue'][$u['role']] ?? 'abadge-grey'; ?>"><?php echo clean(ROLE_LABELS[$u['role']] ?? $u['role']); ?></span></td>
                    <td data-label="Status"><span class="abadge <?php echo $u['is_active'] ? 'abadge-green' : 'abadge-red'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Disabled'; ?></span><?php echo $u['must_change_password'] ? ' <span class="td-muted">· temp password</span>' : ''; ?></td>
                    <td data-label="Last login" class="td-muted"><?php echo $u['last_login_at'] ? clean(timeAgo($u['last_login_at'])) : 'never'; ?></td>
                    <td data-label="Actions"><div class="btn-group">
                        <a href="user-edit.php?id=<?php echo (int)$u['id']; ?>" class="abtn abtn-outline abtn-sm">Edit</a>
                        <?php echo postButton('users.php', ['action' => 'reset', 'id' => $u['id']], 'Reset password', 'abtn abtn-outline abtn-sm', 'Create a new temporary password for ' . $u['name'] . '?'); ?>
                        <?php echo postButton('users.php', ['action' => 'toggle', 'id' => $u['id']], $u['is_active'] ? 'Disable' : 'Enable', 'abtn ' . ($u['is_active'] ? 'abtn-danger' : 'abtn-outline') . ' abtn-sm', $u['is_active'] ? 'Disable ' . $u['name'] . '? They will be logged out immediately.' : ''); ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
