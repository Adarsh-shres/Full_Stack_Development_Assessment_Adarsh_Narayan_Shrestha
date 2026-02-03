<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$cats = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { http_response_code(404); exit('Not found'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $sku = trim($_POST['sku'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $supplier_id = (int)($_POST['supplier_id'] ?? 0);
  $price = (float)($_POST['price'] ?? 0);
  $low = (int)($_POST['low_stock_threshold'] ?? 5);
  $is_active = (int)($_POST['is_active'] ?? 1);

  $remove_image = (int)($_POST['remove_image'] ?? 0) === 1;
  $hasUpload = isset($_FILES['image']) && (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
  if ($hasUpload) {
    $err = (int)($_FILES['image']['error'] ?? UPLOAD_ERR_OK);
    if ($err !== UPLOAD_ERR_OK) {
      $error = 'Image upload failed. Try a smaller JPG/PNG/WEBP file.';
    } else {
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
        UPDATE products
        SET sku=?, name=?, category_id=?, supplier_id=?, price=?, low_stock_threshold=?, is_active=?
        WHERE id=?
      ");
      $stmt->bind_param("ssiidiii", $sku, $name, $category_id, $supplier_id, $price, $low, $is_active, $id);
      $stmt->execute();

      if ($remove_image && !empty($p['image_path']) && str_starts_with($p['image_path'], 'uploads/products/')) {
        $oldFs = __DIR__ . '/../' . $p['image_path'];
        if (is_file($oldFs)) { @unlink($oldFs); }
        $u = $conn->prepare("UPDATE products SET image_path=NULL WHERE id=?");
        $u->bind_param("i", $id);
        $u->execute();
        $p['image_path'] = null;
      }

      if ($hasUpload) {
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
          $filename = 'p' . $id . '_' . $token . '.' . $ext;
          $destFs = $dirFs . '/' . $filename;

          if (move_uploaded_file($tmp, $destFs)) {
            if (!empty($p['image_path']) && str_starts_with($p['image_path'], 'uploads/products/')) {
              $oldFs = __DIR__ . '/../' . $p['image_path'];
              if (is_file($oldFs)) { @unlink($oldFs); }
            }
            $rel = 'uploads/products/' . $filename;
            $u = $conn->prepare("UPDATE products SET image_path=? WHERE id=?");
            $u->bind_param("si", $rel, $id);
            $u->execute();
          }
        }
      }

      flash_set('success', 'Product updated.');
      header('Location: ' . BASE_URL . '/../admin/products.php');
      exit;
    } catch (Throwable $e) {
      $error = 'Could not update product (SKU duplicate maybe).';
    }
  }
}

include __DIR__ . '/../includes/header.php';
?>

<h1>Edit Product</h1>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
  <form method="post" class="form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>SKU</label>
    <input name="sku" value="<?= e($p['sku']) ?>" required>

    <label>Name</label>
    <input name="name" value="<?= e($p['name']) ?>" required>

    <label>Category</label>
    <select name="category_id" required>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ((int)$p['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Supplier</label>
    <select name="supplier_id" required>
      <?php foreach ($sups as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ((int)$p['supplier_id'] === (int)$s['id']) ? 'selected' : '' ?>>
          <?= e($s['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= e($p['price']) ?>" required>

    <label>Low Stock Threshold</label>
    <input type="number" step="1" name="low_stock_threshold" value="<?= (int)$p['low_stock_threshold'] ?>" required>

    <label>Status</label>
    <select name="is_active">
      <option value="1" <?= ((int)$p['is_active'] === 1) ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= ((int)$p['is_active'] === 0) ? 'selected' : '' ?>>Inactive</option>
    </select>

    <label>Product Image <span class="muted">(optional: JPG/PNG/WEBP, max 2MB)</span></label>
    <?php if (!empty($p['image_path'])): ?>
      <div class="image-inline">
        <img src="<?= BASE_URL ?>/../<?= e($p['image_path']) ?>" alt="Current product image">
        <label class="inline-check">
          <input type="checkbox" name="remove_image" value="1"> Remove current image
        </label>
      </div>
    <?php endif; ?>
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">

    <button type="submit">Save</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
