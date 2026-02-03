<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_any_role(['admin','clerk']);

include __DIR__ . '/../includes/header.php';
?>

<h1>Stock Update</h1>

<div class="card">
  <form id="stockForm" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label>Find product (SKU / Name)</label>
    <input id="stock_search" placeholder="type SKU or name..." autocomplete="off">
    <div id="stock_autocomplete" class="autocomplete"></div>

    <input type="hidden" name="product_id" id="product_id">

    <label>Type</label>
    <select name="movement_type">
      <option value="sale">Sale</option>
      <option value="restock">Restock</option>
      <option value="adjustment">Adjustment</option>
    </select>

    <label>Quantity change (negative = sale)</label>
    <input type="number" step="1" name="qty_change" required>

    <label>Note (optional)</label>
    <input name="note">

    <button type="submit">Apply</button>
  </form>

  <div id="stockResult" class="alert"></div>
</div>

<script>window.APP_BASE_URL = "<?= e(BASE_URL) ?>";</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
