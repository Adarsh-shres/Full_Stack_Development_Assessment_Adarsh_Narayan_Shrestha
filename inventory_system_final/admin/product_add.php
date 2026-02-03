<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_role('admin');

$cats = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetch_all(MYSQLI_ASSOC);

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

  if ($sku === '' || $name === '' || $category_id <= 0 || $supplier_id <= 0) {
    $error = 'SKU, Name, Category, Supplier are required.';
  } else {
    try {
      $stmt = $conn->prepare("
        INSERT INTO products (sku, name, category_id, supplier_id, price, stock_qty, low_stock_threshold)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("ssiidii", $sku, $name, $category_id, $supplier_id, $price, $stock, $low);
      $stmt->execute();

      flash_set('success', 'Product added.');
      header('Location: ' . BASE_URL . '/../admin/products.php');
      exit;
    } catch (Throwable $e) {
      $error = 'Could not add product (SKU duplicate maybe).';
    }
  }
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Add Product</h1>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>SKU</label>
    <input name="sku" required>

    <label>Name</label>
    <input name="name" required>

    <label>Category</label>
    <select name="category_id" required>
      <option value="">Select...</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Supplier</label>
    <select name="supplier_id" required>
      <option value="">Select...</option>
      <?php foreach ($sups as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Price</label>
    <input type="number" step="0.01" name="price" value="0.00" required>

    <label>Initial Stock</label>
    <input type="number" step="1" name="stock_qty" value="0" required>

    <label>Low Stock Threshold</label>
    <input type="number" step="1" name="low_stock_threshold" value="5" required>

    <button type="submit">Create</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
