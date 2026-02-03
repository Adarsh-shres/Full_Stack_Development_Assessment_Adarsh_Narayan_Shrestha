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
    if ($name === '') flash_set('error','Category name required.');
    else {
      try {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        flash_set('success','Category added.');
      } catch (Throwable $e) {
        flash_set('error','Could not add (duplicate maybe).');
      }
    }
    header('Location: ' . BASE_URL . '/../admin/categories.php'); exit;
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id<=0 || $name==='') flash_set('error','Invalid update data.');
    else {
      try {
        $stmt = $conn->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        flash_set('success','Category updated.');
      } catch (Throwable $e) {
        flash_set('error','Could not update (duplicate maybe).');
      }
    }
    header('Location: ' . BASE_URL . '/../admin/categories.php'); exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];

    if ($count > 0) {
      flash_set('error',"Can't delete: used by {$count} product(s).");
    } else {
      $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      flash_set('success','Category deleted.');
    }
    header('Location: ' . BASE_URL . '/../admin/categories.php'); exit;
  }

  flash_set('error','Invalid action.');
  header('Location: ' . BASE_URL . '/../admin/categories.php'); exit;
}

$rows = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h1>Categories</h1>

<div class="card">
  <h2>Add Category</h2>
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label>Name</label>
    <input name="name" required>
    <button class="btn" type="submit">Add</button>
  </form>
</div>

<div class="card">
  <h2>All Categories</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td>
            <?php if ($editId === (int)$r['id']): ?>
              <form method="post" class="form inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input name="name" value="<?= e($r['name']) ?>" required>
                <button class="btn" type="submit">Save</button>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>/../admin/categories.php">Cancel</a>
              </form>
            <?php else: ?>
              <?= e($r['name']) ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($editId !== (int)$r['id']): ?>
              <a class="btn btn-secondary" href="<?= BASE_URL ?>/../admin/categories.php?edit=<?= (int)$r['id'] ?>">Edit</a>

              <form method="post" class="inline" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-danger" type="submit">Delete</button>
              </form>
            <?php else: ?>
              <span class="muted">Editing…</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="3" class="muted">No categories yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
