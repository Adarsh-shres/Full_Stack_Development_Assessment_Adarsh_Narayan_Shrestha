<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_any_role(['admin','clerk']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

csrf_check();

$product_id = (int)($_POST['product_id'] ?? 0);
$type = $_POST['movement_type'] ?? 'sale';
$qty_change = (int)($_POST['qty_change'] ?? 0);
$note = trim($_POST['note'] ?? '');

if ($product_id <= 0 || $qty_change === 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid input']);
  exit;
}

$conn->begin_transaction();

try {
  $stmt = $conn->prepare("SELECT stock_qty, low_stock_threshold FROM products WHERE id=? FOR UPDATE");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $p = $stmt->get_result()->fetch_assoc();
  if (!$p) throw new Exception("Not found");

  $newQty = (int)$p['stock_qty'] + $qty_change;
  if ($newQty < 0) $newQty = 0;

  $stmt = $conn->prepare("UPDATE products SET stock_qty=? WHERE id=?");
  $stmt->bind_param("ii", $newQty, $product_id);
  $stmt->execute();

  $user_id = (int)($_SESSION['user']['id'] ?? 0);
  $stmt = $conn->prepare("
    INSERT INTO stock_movements (product_id, user_id, movement_type, qty_change, note)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("iisis", $product_id, $user_id, $type, $qty_change, $note);
  $stmt->execute();

  $conn->commit();

  $isLow = ($newQty <= (int)$p['low_stock_threshold']);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'newQty' => $newQty, 'isLow' => $isLow]);
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Server error']);
}
