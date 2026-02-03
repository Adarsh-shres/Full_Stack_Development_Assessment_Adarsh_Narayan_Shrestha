<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

require_login();

$term = trim($_GET['term'] ?? '');
if ($term === '' || strlen($term) < 2) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([]);
  exit;
}

$like = "%{$term}%";
$stmt = $conn->prepare("
  SELECT id, sku, name
  FROM products
  WHERE is_active=1 AND (sku LIKE ? OR name LIKE ?)
  ORDER BY sku ASC
  LIMIT 10
");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();

$out = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
  $out[] = [
    'id' => (int)$r['id'],
    'label' => $r['sku'] . ' — ' . $r['name']
  ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out);
