<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

$f = flash_get();
$user = $_SESSION['user'] ?? null;

$lowCount = 0;
$setupOpen = false;

if ($user) {
  $res = $conn->query("SELECT COUNT(*) AS c FROM products WHERE is_active=1 AND stock_qty <= low_stock_threshold");
  $lowCount = (int)$res->fetch_assoc()['c'];
} else {
  $cntRes = $conn->query("SELECT COUNT(*) AS c FROM users");
  $userCount = (int)($cntRes ? $cntRes->fetch_assoc()['c'] : 0);
  $setupOpen = ($userCount === 0);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(APP_NAME) ?></title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
</head>
<body>
<header class="topbar">
  <div class="container topbar-row">
    <div class="brand"><?= e(APP_NAME) ?></div>

    <nav class="nav">
      <?php if ($user): ?>
        <a href="<?= BASE_URL ?>/index.php">Dashboard</a>

        <a href="<?= BASE_URL ?>/low_stock.php">
          Low Stock
          <?php if ($lowCount > 0): ?>
            <span id="lowStockBadge" class="badge low"><?= $lowCount ?></span>
          <?php else: ?>
            <span id="lowStockBadge" class="badge low" style="display:none;">0</span>
          <?php endif; ?>
        </a>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
          <a href="<?= BASE_URL ?>/../admin/products.php">Products</a>
          <a href="<?= BASE_URL ?>/../admin/categories.php">Categories</a>
          <a href="<?= BASE_URL ?>/../admin/suppliers.php">Suppliers</a>
          <a href="<?= BASE_URL ?>/../admin/users.php">Users</a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/../clerk/stock_update.php">Stock Update</a>

        <span class="pill"><?= e($user['username']) ?> (<?= e($user['role']) ?>)</span>
        <a class="danger" href="<?= BASE_URL ?>/logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php">Login</a>
        <?php if ($setupOpen): ?>
          <a href="<?= BASE_URL ?>/register.php">Initial Setup</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container">
<?php if ($f): ?>
  <div class="alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endif; ?>
