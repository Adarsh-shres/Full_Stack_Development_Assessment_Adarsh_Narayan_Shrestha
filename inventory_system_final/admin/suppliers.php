<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

$editId = (int)($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';

  if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') flash_set('error','Supplier name required.');
    else {
      $stmt = $conn->prepare("INSERT INTO suppliers (name, email, phone) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $name, $email, $phone);
      $stmt->execute();
      flash_set('success','Supplier added.');
    }
    header('Location: ' . BASE_URL . '/../admin/suppliers.php'); exit;
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($id<=0 || $name==='') flash_set('error','Invalid update data.');
    else {
      $stmt = $conn->prepare("UPDATE suppliers SET name=?, email=?, phone=? WHERE id=?");
      $stmt->bind_param("sssi", $name, $email, $phone, $id);
      $stmt->execute();
      flash_set('success','Supplier updated.');
    }
    header('Location: ' . BASE_URL . '/../admin/suppliers.php'); exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE supplier_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];

    if ($count > 0) {
      flash_set('error',"Can't delete: used by {$count} product(s).");
    } else {
      $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      flash_set('success','Supplier deleted.');
    }

    header('Location: ' . BASE_URL . '/../admin/suppliers.php'); exit;
  }

  flash_set('error','Invalid action.');
  header('Location: ' . BASE_URL . '/../admin/suppliers.php'); exit;
}

$rows = $conn->query("SELECT id, name, email, phone FROM suppliers ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h1>Suppliers</h1>

<div class="card">
  <h2>Add Supplier</h2>
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label>Name</label><input name="name" required>
    <label>Email</label><input type="email" name="email">
    <label>Phone</label><input
  type="tel"
  name="phone"
  pattern="[0-9+\-\s]{7,15}"
  title="Phone number should contain only digits, +, - or spaces"
>

    <button class="btn" type="submit">Add</button>
  </form>
</div>

<div class="card">
  <h2>All Suppliers</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>

          <?php if ($editId === (int)$r['id']): ?>
            <td colspan="3">
              <form method="post" class="form inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input name="name" value="<?= e($r['name']) ?>" required>
                <input name="email" value="<?= e($r['email']) ?>" placeholder="email">
                <input name="phone" value="<?= e($r['phone']) ?>" placeholder="phone">
                <button class="btn" type="submit">Save</button>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>/../admin/suppliers.php">Cancel</a>
              </form>
            </td>
            <td><span class="muted">Editing…</span></td>
          <?php else: ?>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['email']) ?></td>
            <td><?= e($r['phone']) ?></td>
            <td>
              <a class="btn btn-secondary" href="<?= BASE_URL ?>/../admin/suppliers.php?edit=<?= (int)$r['id'] ?>">Edit</a>
              <form method="post" class="inline" onsubmit="return confirm('Delete this supplier?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger" type="submit">Delete</button>
              </form>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="muted">No suppliers yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
