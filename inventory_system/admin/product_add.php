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

  $hasUpload = isset($_FILES['image']) && (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
  if ($hasUpload) {
    $err = (int)($_FILES['image']['error'] ?? UPLOAD_ERR_OK);
    if ($err !== UPLOAD_ERR_OK) {
      $error = 'Image upload failed. Try a smaller JPG/PNG/WEBP file.';
    } else {
      //Max 2 mb image size
      $maxBytes = 2 * 1024 * 1024; 
      if (($_FILES['image']['size'] ?? 0) > $maxBytes) {
        $error = 'Image is too large (max 2MB).';
      } else {
        $tmp = $_FILES['image']['tmp_name'] ?? '';
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $tmp ? ($finfo->file($tmp) ?: '') : '';
        $allowed = [
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
          $error = 'Invalid image type. Allowed: JPG, PNG, WEBP.';
        }
      }
    }
  }

  if ($sku === '' || $name === '' || $category_id <= 0 || $supplier_id <= 0) {
    $error = 'SKU, Name, Category, Supplier are required.';
  } elseif ($error === '') {
    try {
      $stmt = $conn->prepare("
        INSERT INTO products (sku, name, category_id, supplier_id, price, stock_qty, low_stock_threshold)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("ssiidii", $sku, $name, $category_id, $supplier_id, $price, $stock, $low);
      $stmt->execute();

      if ($hasUpload) {
        $pid = (int)$conn->insert_id;
        $tmp = $_FILES['image']['tmp_name'] ?? '';
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $tmp ? ($finfo->file($tmp) ?: '') : '';
        $allowed = [
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
        ];
        $ext = $allowed[$mime] ?? null;
        if ($ext) {
          $dirFs = __DIR__ . '/../uploads/products';
          if (!is_dir($dirFs)) { @mkdir($dirFs, 0775, true); }

          $token = bin2hex(random_bytes(8));
          $filename = 'p' . $pid . '_' . $token . '.' . $ext;
          $destFs = $dirFs . '/' . $filename;
          if (move_uploaded_file($tmp, $destFs)) {
            $rel = 'uploads/products/' . $filename;
            $u = $conn->prepare("UPDATE products SET image_path=? WHERE id=?");
            $u->bind_param("si", $rel, $pid);
            $u->execute();
          }
        }
      }

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
  <form method="post" class="form" enctype="multipart/form-data">
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

    <label>Product Image <span class="muted">(optional: JPG/PNG/WEBP, max 2MB)</span></label>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">

    <button type="submit">Create</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
