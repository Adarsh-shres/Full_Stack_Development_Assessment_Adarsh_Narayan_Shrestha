<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_login();

$res = $conn->query("SELECT COUNT(*) AS c FROM products WHERE is_active=1 AND stock_qty <= low_stock_threshold");
echo json_encode(['count' => (int)$res->fetch_assoc()['c']]);
