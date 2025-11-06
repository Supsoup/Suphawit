<?php
// admin/product_delete.php
session_start();
require_once __DIR__ . '/../config/db.php';

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
  header('Location: products.php?error=' . urlencode('ไม่พบรหัสสินค้า')); exit;
}

try {
  // ถูกใช้งานในออเดอร์หรือยัง
  $st = $pdo->prepare("SELECT EXISTS(SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1)");
  $st->execute([$id]);
  $used = (int)$st->fetchColumn();

  if ($used) {
    // Soft delete (ซ่อน)
    $st = $pdo->prepare("UPDATE products SET is_active = 0, deleted_at = NOW() WHERE id = ?");
    $st->execute([$id]);
    header('Location: products.php?msg=' . urlencode('ซ่อนสินค้าแล้ว (มีประวัติคำสั่งซื้อจึงลบถาวรไม่ได้)'));
    exit;
  }

  // ลบถาวรได้
  $pdo->beginTransaction();

  if ($pdo->query("SHOW TABLES LIKE 'product_genres'")->rowCount()) {
    $pdo->prepare("DELETE FROM product_genres WHERE product_id = ?")->execute([$id]);
  }
  if ($pdo->query("SHOW TABLES LIKE 'cart_items'")->rowCount()) {
    $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?")->execute([$id]);
  }

  // (ออปชัน) ลบไฟล์รูป
  $img = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
  $img->execute([$id]);
  $path = $img->fetchColumn();
  if ($path && is_file(__DIR__ . '/../' . $path)) {
    @unlink(__DIR__ . '/../' . $path);
  }

  $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
  $pdo->commit();

  header('Location: products.php?msg=' . urlencode('ลบสินค้าเรียบร้อย'));
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: products.php?error=' . urlencode('ลบไม่สำเร็จ: ' . $e->getMessage()));
  exit;
}
