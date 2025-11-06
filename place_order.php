<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/lib/helpers.php';
require_once __DIR__.'/lib/user.php';

require_user('place_order.php');
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '') || ($_POST['action'] ?? '') !== 'place') {
  header('Location: checkout.php'); exit;
}

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { header('Location: cart.php'); exit; }

/* แปลงเป็น pid => qty */
$itemsByPid = [];
foreach ($cart as $k=>$v) {
  $pid = is_array($v) ? (int)($v['product_id'] ?? $k) : (int)$k;
  $qty = is_array($v) ? (int)($v['qty'] ?? $v['quantity'] ?? 1) : (int)$v;
  if ($qty < 1) $qty = 1;
  if ($pid <= 0) continue;
  $itemsByPid[$pid] = ($itemsByPid[$pid] ?? 0) + $qty;
}
if (!$itemsByPid) { header('Location: cart.php'); exit; }

$st = $pdo->prepare("SELECT name, phone, address FROM users WHERE id=?");
$st->execute([ current_user()['id'] ]);
$uinfo = $st->fetch(PDO::FETCH_ASSOC) ?: [];
$ship_name  = $uinfo['name'] ?: current_user()['email'];
$ship_phone = trim((string)($uinfo['phone'] ?? ''));
$ship_addr  = trim((string)($uinfo['address'] ?? ''));
if ($ship_phone === '' || $ship_addr === '') {
  $_SESSION['flash_error'] = 'กรุณากรอกที่อยู่และเบอร์โทรในโปรไฟล์ก่อนทำการสั่งซื้อ';
  header('Location: account/profile.php'); exit;
}

$productIds = array_keys($itemsByPid);
$in = implode(',', array_fill(0, count($productIds), '?'));
$pdo->beginTransaction();
$st = $pdo->prepare("SELECT id,name,price,stock FROM products WHERE id IN ($in) FOR UPDATE");
$st->execute($productIds);
$products = [];
while ($r=$st->fetch(PDO::FETCH_ASSOC)) {
  $r['price'] = (float)$r['price'];
  $products[(int)$r['id']] = $r;
}

$total = 0.0;
foreach ($itemsByPid as $pid=>$qty) {
  if (!isset($products[$pid])) { $pdo->rollBack(); header('Location: cart.php'); exit; }
  if ($products[$pid]['stock'] < $qty) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = 'สต็อกสินค้า "' . $products[$pid]['name'] . '" ไม่เพียงพอ';
    header('Location: cart.php'); exit;
  }
  $total += $products[$pid]['price'] * $qty;
}

// หมดอายุ 10 นาที
$expireMinutes = 10;
$ins = $pdo->prepare("INSERT INTO orders
  (user_id, status, total_amount, placed_at, expires_at, shipping_name, shipping_address, shipping_phone)
  VALUES (?, 'PENDING_PAYMENT', ?, NOW(), DATE_ADD(NOW(), INTERVAL {$expireMinutes} MINUTE), ?, ?, ?)");
$ins->execute([ current_user()['id'], $total, $ship_name, $ship_addr, $ship_phone ]);
$orderId = (int)$pdo->lastInsertId();

$itemIns = $pdo->prepare("INSERT INTO order_items(order_id, product_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)");
$upd    = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?");
foreach ($itemsByPid as $pid=>$qty) {
  $price = $products[$pid]['price'];
  $itemIns->execute([$orderId, $pid, $qty, $price, $price*$qty]);
  $upd->execute([$qty, $pid]);
}

$pdo->commit();
unset($_SESSION['cart']);
header('Location: upload-slip.php?id='.$orderId);
exit;
