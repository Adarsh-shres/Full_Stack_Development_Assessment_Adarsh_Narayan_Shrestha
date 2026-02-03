<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

if (!empty($_SESSION['user'])) {
  header('Location: ' . BASE_URL . '/index.php');
  exit;
}

$cntRes = $conn->query("SELECT COUNT(*) AS c FROM users");
$userCount = (int)($cntRes ? $cntRes->fetch_assoc()['c'] : 0);
$setupOpen = ($userCount === 0);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $error = 'Username and password are required.';
  } else {
    $stmt = $conn->prepare("SELECT id, username, password_hash, role, is_active FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($password, $u['password_hash'])) {
      if ((int)$u['is_active'] !== 1) {
        $error = 'This account is disabled. Contact admin.';
      } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
          'id' => (int)$u['id'],
          'username' => $u['username'],
          'role' => $u['role']
        ];
        header('Location: ' . BASE_URL . '/index.php');
        exit;
      }
    } else {
      $error = 'Invalid login.';
    }
  }
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Login</h1>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>Username</label>
    <input name="username" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit" class="btn">Login</button>

    <?php if ($setupOpen): ?>
      <p class="muted">First time setup? <a href="<?= BASE_URL ?>/register.php">Create admin account</a></p>
    <?php endif; ?>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
