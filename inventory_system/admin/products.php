<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_role('admin');

$sql = "
SELECT p.id, p.sku, p.name, p.price, p.stock_qty, p.low_stock_threshold,
       c.name AS category_name, s.name AS supplier_name
FROM products p
JOIN categories c ON c.id=p.category_id
JOIN suppliers s ON s.id=p.supplier_id
ORDER BY p.id DESC
LIMIT 300
";
$rows = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h1>Products</h1>

<div class="card">
  <a class="btn" href="<?= BASE_URL ?>/../admin/product_add.php">+ Add Product</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>SKU</th><th>Name</th><th>Category</th><th>Supplier</th>
          <th>Price</th><th>Stock</th><th>Low</th><th>View</th><th>Edit</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="9" class="muted">No products yet.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e($r['sku']) ?></td>
              <td><?= e($r['name']) ?></td>
              <td><?= e($r['category_name']) ?></td>
              <td><?= e($r['supplier_name']) ?></td>
              <td><?= e($r['price']) ?></td>
              <td><?= (int)$r['stock_qty'] ?></td>
              <td><?= (int)$r['low_stock_threshold'] ?></td>
              <td><a class="btn btn-secondary" href="<?= BASE_URL ?>/product.php?id=<?= (int)$r['id'] ?>">View</a></td>
              <td><a class="btn" href="<?= BASE_URL ?>/../admin/product_edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
