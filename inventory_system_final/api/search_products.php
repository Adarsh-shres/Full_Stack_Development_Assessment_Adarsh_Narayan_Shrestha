<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_login();

$q = trim($_GET['q'] ?? '');
$category = (int)($_GET['category_id'] ?? 0);
$supplier = (int)($_GET['supplier_id'] ?? 0);

$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$min_stock = $_GET['min_stock'] ?? '';
$max_stock = $_GET['max_stock'] ?? '';
$low_only = (int)($_GET['low_only'] ?? 0);

$sql = "
SELECT p.id, p.sku, p.name, p.price, p.stock_qty, p.low_stock_threshold,
       c.name AS category_name, s.name AS supplier_name
FROM products p
JOIN categories c ON c.id=p.category_id
JOIN suppliers s ON s.id=p.supplier_id
WHERE p.is_active=1
";

$params = [];
$types = "";

if ($q !== '') {
  $sql .= " AND (p.sku LIKE ? OR p.name LIKE ?) ";
  $like = "%{$q}%";
  $params[] = $like; $params[] = $like;
  $types .= "ss";
}
if ($category > 0) { $sql .= " AND p.category_id=? "; $params[] = $category; $types .= "i"; }
if ($supplier > 0) { $sql .= " AND p.supplier_id=? "; $params[] = $supplier; $types .= "i"; }

if ($min_price !== '' && is_numeric($min_price)) { $sql .= " AND p.price >= ? "; $params[] = (float)$min_price; $types .= "d"; }
if ($max_price !== '' && is_numeric($max_price)) { $sql .= " AND p.price <= ? "; $params[] = (float)$max_price; $types .= "d"; }

if ($min_stock !== '' && is_numeric($min_stock)) { $sql .= " AND p.stock_qty >= ? "; $params[] = (int)$min_stock; $types .= "i"; }
if ($max_stock !== '' && is_numeric($max_stock)) { $sql .= " AND p.stock_qty <= ? "; $params[] = (int)$max_stock; $types .= "i"; }

if ($low_only === 1) {
  $sql .= " AND p.stock_qty <= p.low_stock_threshold ";
}

$sql .= " ORDER BY p.name ASC LIMIT 200";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();

$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo json_encode($rows);
