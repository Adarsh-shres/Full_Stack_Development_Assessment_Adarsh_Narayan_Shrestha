<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  exit('Not found');
}

$cats = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetch_all(MYSQLI_ASSOC);


$stmt = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
  http_response_code(404);
  exit('Not found');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $sku = trim($_POST['sku'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $supplier_id = (int)($_POST['supplier_id'] ?? 0);
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock_qty'] ?? 0);
  $low = (int)($_POST['low_stock_threshold'] ?? 5);
  $is_active = (int)($_POST['is_active'] ?? 1);

  if ($sku === '' || $name === '' || $category_id <= 0 || $supplier_id <= 0) {
    $error = 'SKU, Name, Category, Supplier are required.';
  } else {
    try {
      $u = $conn->prepare("
        UPDATE products
        SET sku=?, name=?, category_id=?, supplier_id=?, price=?, stock_qty=?, low_stock_threshold=?, is_active=?
        WHERE id=?
      ");
      $u->bind_param("ssiidiiii", $sku, $name, $category_id, $supplier_id, $price, $stock, $low, $is_active, $id);
      $u->execute();

      flash_set('success', 'Product updated.');
      header('Location: ' . BASE_URL . '/../admin/products.php');
      exit;
    } catch (Throwable $e) {
      $error = 'Could not update product (SKU duplicate maybe).';
    }
  }

  $p['sku'] = $sku;
  $p['name'] = $name;
  $p['category_id'] = $category_id;
  $p['supplier_id'] = $supplier_id;
  $p['price'] = $price;
  $p['stock_qty'] = $stock;
  $p['low_stock_threshold'] = $low;
  $p['is_active'] = $is_active;
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Edit Product</h1>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>SKU</label>
    <input name="sku" value="<?= e($p['sku']) ?>" required>

    <label>Name</label>
    <input name="name" value="<?= e($p['name']) ?>" required>

    <label>Category</label>
    <select name="category_id" required>
      <option value="">Select...</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$p['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Supplier</label>
    <select name="supplier_id" required>
      <option value="">Select...</option>
      <?php foreach ($sups as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)$p['supplier_id'] === (int)$s['id']) ? 'selected' : '' ?>>
          <?= e($s['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= e($p['price']) ?>" required>

    <label>Stock</label>
    <input type="number" step="1" name="stock_qty" value="<?= (int)$p['stock_qty'] ?>" required>

    <label>Low Stock Threshold</label>
    <input type="number" step="1" name="low_stock_threshold" value="<?= (int)$p['low_stock_threshold'] ?>" required>

    <label>Status</label>
    <select name="is_active">
      <option value="1" <?= ((int)$p['is_active'] === 1) ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= ((int)$p['is_active'] === 0) ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit" class="btn">Save</button>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/../admin/products.php">Cancel</a>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
