<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

if (!empty($_SESSION['user'])) {
  header('Location: ' . BASE_URL . '/index.php');
  exit;
}

$cntRes = $conn->query("SELECT COUNT(*) AS c FROM users");
$userCount = (int)($cntRes ? $cntRes->fetch_assoc()['c'] : 0);

if ($userCount > 0) {
  flash_set('error', 'Registration is disabled. Please contact an admin.');
  header('Location: ' . BASE_URL . '/login.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  if ($username === '' || $password === '' || $confirm === '') {
    $error = 'All fields are required.';
  } elseif (strlen($username) < 3) {
    $error = 'Username must be at least 3 characters.';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();

    if ($exists) {
      $error = 'Username already taken.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $role = 'admin';

      $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)");
      $stmt->bind_param("sss", $username, $hash, $role);
      $stmt->execute();

      flash_set('success', 'Admin account created. Please login.');
      header('Location: ' . BASE_URL . '/login.php');
      exit;
    }
  }
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Initial Setup (Create Admin)</h1>

<p class="muted">This page is only available before the first user is created.</p>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>Admin Username</label>
    <input name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm" required>

    <button type="submit" class="btn">Create Admin Account</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
