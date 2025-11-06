<?php
// admin/product_restore.php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: products.php?error=' . urlencode('ไม่พบรหัสสินค้า')); exit; }

try {
  $st = $pdo->prepare("UPDATE products SET is_active = 1, deleted_at = NULL WHERE id = ?");
  $st->execute([$id]);
  header('Location: products.php?msg=' . urlencode('กู้คืนสินค้าสำเร็จ'));
  exit;
} catch (Throwable $e) {
  header('Location: products.php?error=' . urlencode('กู้คืนไม่สำเร็จ: ' . $e->getMessage()));
  exit;
}
