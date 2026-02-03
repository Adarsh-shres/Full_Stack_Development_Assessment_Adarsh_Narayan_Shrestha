<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('Not found'); }

$stmt = $conn->prepare("
  SELECT p.*, c.name AS category_name, s.name AS supplier_name
  FROM products p
  JOIN categories c ON c.id=p.category_id
  JOIN suppliers s ON s.id=p.supplier_id
  WHERE p.id=? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) { http_response_code(404); exit('Not found'); }

$m = $conn->prepare("
  SELECT sm.created_at, sm.movement_type, sm.qty_change, sm.note, u.username
  FROM stock_movements sm
  JOIN users u ON u.id=sm.user_id
  WHERE sm.product_id=?
  ORDER BY sm.created_at DESC
  LIMIT 50
");
$m->bind_param("i", $id);
$m->execute();
$movements = $m->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';

$isLow = ((int)$p['stock_qty'] <= (int)$p['low_stock_threshold']);
?>
<h1>Product View</h1>

<div class="card">
  <div class="product-view">
    <div class="product-image">
      <?php if (!empty($p['image_path'])): ?>
        <img src="<?= BASE_URL ?>/../<?= e($p['image_path']) ?>" alt="Product image">
      <?php else: ?>
        <div class="img-placeholder"></div>
      <?php endif; ?>
    </div>

    <div class="product-info">
      <div class="kv"><span>SKU</span><span><?= e($p['sku']) ?></span></div>
      <div class="kv"><span>Name</span><span><?= e($p['name']) ?></span></div>
      <div class="kv"><span>Category</span><span><?= e($p['category_name']) ?></span></div>
      <div class="kv"><span>Supplier</span><span><?= e($p['supplier_name']) ?></span></div>
      <div class="kv"><span>Price</span><span><?= e($p['price']) ?></span></div>
      <div class="kv"><span>Stock</span><span><?= (int)$p['stock_qty'] ?></span></div>
      <div class="kv"><span>Status</span>
        <span class="badge <?= $isLow ? 'low' : 'ok' ?>"><?= $isLow ? 'LOW' : 'OK' ?></span>
      </div>


      <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
        <a class="btn" href="<?= BASE_URL ?>/../admin/product_edit.php?id=<?= (int)$p['id'] ?>">Edit Product</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card">
  <h2>Stock Movements</h2>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Date</th><th>User</th><th>Type</th><th>Qty</th><th>Note</th></tr>
      </thead>
      <tbody>
        <?php if (!$movements): ?>
          <tr><td colspan="5" class="muted">No movements yet.</td></tr>
        <?php else: ?>
          <?php foreach ($movements as $x): ?>
            <tr>
              <td><?= e($x['created_at']) ?></td>
              <td><?= e($x['username']) ?></td>
              <td><?= e($x['movement_type']) ?></td>
              <td><?= (int)$x['qty_change'] ?></td>
              <td><?= e($x['note']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
