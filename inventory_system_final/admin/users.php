<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

$errorCreate = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $action = $_POST['action'] ?? '';
  $current = (int)($_SESSION['user']['id'] ?? 0);

  if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $role     = $_POST['role'] ?? 'clerk';

    if ($username === '' || $password === '' || $confirm === '') {
      $errorCreate = 'All fields are required.';
    } elseif (!in_array($role, ['admin','clerk'], true)) {
      $errorCreate = 'Invalid role.';
    } elseif (strlen($username) < 3) {
      $errorCreate = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
      $errorCreate = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
      $errorCreate = 'Passwords do not match.';
    } else {
      $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $exists = $stmt->get_result()->fetch_assoc();

      if ($exists) {
        $errorCreate = 'Username already taken.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $username, $hash, $role);
        $stmt->execute();

        flash_set('success', 'User account created.');
        header('Location: ' . BASE_URL . '/../admin/users.php');
        exit;
      }
    }
  }

  if ($action === 'disable' || $action === 'enable') {
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
      flash_set('error', 'Invalid request.');
    } elseif ($user_id === $current) {
      flash_set('error', "You can't disable your own account.");
    } else {
      if ($action === 'disable') {
        $stmt = $conn->prepare("UPDATE users SET is_active=0 WHERE id=? AND role='clerk'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        flash_set('success', 'Clerk disabled.');
      } else {
        $stmt = $conn->prepare("UPDATE users SET is_active=1 WHERE id=? AND role='clerk'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        flash_set('success', 'Clerk enabled.');
      }
    }

    header('Location: ' . BASE_URL . '/../admin/users.php');
    exit;
  }

  if ($action !== 'create' && $action !== 'disable' && $action !== 'enable') {
    flash_set('error', 'Invalid action.');
    header('Location: ' . BASE_URL . '/../admin/users.php');
    exit;
  }
}

$rows = $conn->query("SELECT id, username, role, is_active, created_at FROM users ORDER BY role ASC, username ASC")
             ->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h1>Users</h1>

<div class="card" style="margin-bottom: 12px;">
  <h3 style="margin-top:0;">Create User</h3>

  <?php if ($errorCreate): ?>
    <div class="alert error"><?= e($errorCreate) ?></div>
  <?php endif; ?>

  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="create">

    <label>Username</label>
    <input name="username" required>

    <label>Role</label>
    <select name="role" required>
      <option value="clerk">Clerk</option>
      <option value="admin">Admin</option>
    </select>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm" required>

    <button class="btn" type="submit">Create</button>
  </form>

  <p class="muted" style="margin-bottom:0;">
    Tip: You can later disable/enable clerk accounts below. Admin accounts cannot be disabled from here to prevent lockouts.
  </p>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['role']) ?></td>
            <td><span class="badge <?= ((int)$r['is_active'] === 1) ? 'ok' : 'low' ?>">
              <?= ((int)$r['is_active'] === 1) ? 'ACTIVE' : 'DISABLED' ?>
            </span></td>
            <td><?= e($r['created_at']) ?></td>
            <td>
              <?php if ($r['role'] === 'clerk'): ?>
                <form method="post" class="inline" onsubmit="return confirm('Are you sure?');">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                  <?php if ((int)$r['is_active'] === 1): ?>
                    <input type="hidden" name="action" value="disable">
                    <button class="btn btn-danger" type="submit">Disable</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="enable">
                    <button class="btn btn-secondary" type="submit">Enable</button>
                  <?php endif; ?>
                </form>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="muted">No users found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
