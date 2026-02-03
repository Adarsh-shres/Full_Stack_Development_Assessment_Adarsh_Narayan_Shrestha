<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$cats = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<h1>Dashboard</h1>

<div class="card">
  <h2>Search</h2>

  <div class="filters">
    <div class="field wide">
      <label>Search (SKU / Name)</label>
      <input id="q" placeholder="type SKU or name..." autocomplete="off">
      <div id="autocomplete" class="autocomplete"></div>
    </div>

    <div class="field">
      <label>Category</label>
      <select id="category_id">
        <option value="0">All</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Supplier</label>
      <select id="supplier_id">
        <option value="0">All</option>
        <?php foreach ($sups as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Min Price</label>
      <input id="min_price" type="number" step="0.01" placeholder="0.00">
    </div>

    <div class="field">
      <label>Max Price</label>
      <input id="max_price" type="number" step="0.01" placeholder="0.00">
    </div>

    <div class="field">
      <label>Min Stock</label>
      <input id="min_stock" type="number" step="1" placeholder="0">
    </div>

    <div class="field">
      <label>Max Stock</label>
      <input id="max_stock" type="number" step="1" placeholder="0">
    </div>

    <div class="field">
      <label>&nbsp;</label>
      <label class="checkline">
        <input id="low_only" type="checkbox">
        <span>Low stock only</span>
      </label>
    </div>

    <div class="field actions">
      <button id="runSearchBtn" type="button" class="btn">Refresh</button>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>SKU</th><th>Name</th><th>Category</th><th>Supplier</th>
          <th>Price</th><th>Stock</th><th>Status</th><th>Quick Stock</th><th>View</th>
        </tr>
      </thead>
      <tbody id="resultsBody"></tbody>
    </table>
  </div>
</div>

<script>
  window.APP_BASE_URL = "<?= e(BASE_URL) ?>";
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
