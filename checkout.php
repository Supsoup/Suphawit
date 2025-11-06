<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/lib/helpers.php';
require_once __DIR__.'/lib/user.php';

require_user('checkout.php');
$csrf = ensure_csrf();

$cart = $_SESSION['cart'] ?? [];
if (!$cart) { header('Location: cart.php'); exit; }

/* --------- แปลงตะกร้าให้เป็นรูปแบบ pid => qty อย่างแน่นอน --------- */
$itemsByPid = [];
foreach ($cart as $k => $v) {
  // รองรับทั้ง 2 โครงสร้าง
  $pid = is_array($v) ? (int)($v['product_id'] ?? $k) : (int)$k;
  $qty = is_array($v) ? (int)($v['qty'] ?? $v['quantity'] ?? 1) : (int)$v;
  if ($qty < 1) $qty = 1;
  if ($pid <= 0) continue;
  $itemsByPid[$pid] = ($itemsByPid[$pid] ?? 0) + $qty; // รวมจำนวนเผื่อซ้ำ
}
if (!$itemsByPid) { header('Location: cart.php'); exit; }

$productIds = array_keys($itemsByPid);
$in = implode(',', array_fill(0, count($productIds), '?'));
$st = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($in)");
$st->execute($productIds);
$products = [];
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
  $row['price'] = (float)$row['price']; // กันกรณี price เป็น VARCHAR
  $products[(int)$row['id']] = $row;
}

/* ----------------- คำนวณยอดรวม/เตรียมแสดงผล ----------------- */
$total = 0.0;
$items = [];
foreach ($itemsByPid as $pid => $qty) {
  if (!isset($products[$pid])) continue;
  $p = $products[$pid];
  $line = $p['price'] * $qty;
  $total += $line;
  $items[] = ['id'=>$pid,'name'=>$p['name'],'price'=>$p['price'],'qty'=>$qty,'line'=>$line];
}

/* --------- ข้อมูลจัดส่งจากโปรไฟล์ (อ่านอย่างเดียว) --------- */
$st = $pdo->prepare("SELECT name, phone, address FROM users WHERE id=?");
$st->execute([ current_user()['id'] ]);
$uinfo = $st->fetch(PDO::FETCH_ASSOC) ?: ['name'=>null,'phone'=>null,'address'=>null];

$ship_name  = $uinfo['name'] ?: current_user()['email'];
$ship_phone = trim((string)$uinfo['phone']);
$ship_addr  = trim((string)$uinfo['address']);
$needProfile = ($ship_phone === '' || $ship_addr === '');
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>เช็คเอาต์</title>
<link rel="stylesheet" href="assets/styles.css">
<style>
.summary{border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:12px}
.ship-card{border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:16px}
.ship-row{margin:6px 0}
.warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:10px;padding:10px;margin-top:10px}
.btn-primary{background:#1d4ed8;color:#fff;border:none;border-radius:12px;padding:10px 16px;font-weight:700;cursor:pointer}
.btn-primary[disabled]{opacity:.55;cursor:not-allowed}
.btn-outline{border:1px solid #cbd5e1;border-radius:12px;padding:10px 16px;color:#111827;text-decoration:none}
.cart-table{width:100%;border-collapse:collapse}
.cart-table th,.cart-table td{border-bottom:1px solid #eceff3;padding:10px 8px;text-align:left}
.cart-table tr:last-child td{border-bottom:none}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="account/orders.php">คำสั่งซื้อของฉัน</a>
  <a class="btn-outline" href="logout.php">ออกจากระบบ</a>
</header>

<main class="container" style="max-width:1000px">
  <h2 class="section-title">เช็คเอาต์</h2>

  <div class="summary" style="margin-bottom:16px">
    <table class="cart-table">
      <tr><th>สินค้า</th><th>ราคา</th><th>จำนวน</th><th>รวม</th></tr>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?=htmlspecialchars($it['name'])?></td>
          <td><?=number_format($it['price'])?> THB</td>
          <td><?=$it['qty']?></td>
          <td><?=number_format($it['line'])?> THB</td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="3" style="text-align:right"><b>รวมทั้งหมด</b></td>
        <td><b><?=number_format($total)?> THB</b></td>
      </tr>
    </table>
  </div>

  <div class="ship-card">
    <h3 style="margin-top:0">ข้อมูลจัดส่ง (จากโปรไฟล์)</h3>
    <div class="ship-row"><b>ชื่อ-นามสกุล:</b> <?=htmlspecialchars($ship_name)?></div>
    <div class="ship-row"><b>ที่อยู่จัดส่ง:</b> <?=nl2br(htmlspecialchars($ship_addr)) ?: '<span class="muted">- ยังไม่ได้กรอก -</span>'?></div>
    <div class="ship-row"><b>เบอร์โทร:</b> <?=htmlspecialchars($ship_phone) ?: '<span class="muted">- ยังไม่ได้กรอก -</span>'?></div>

    <?php if ($needProfile): ?>
      <div class="warn">กรุณาเพิ่ม <b>ที่อยู่</b> และ <b>เบอร์โทร</b> ในโปรไฟล์ก่อนทำการสั่งซื้อ</div>
      <div style="margin-top:10px">
        <a class="btn-outline" href="account/profile.php">แก้ไขโปรไฟล์</a>
        <a class="btn-outline" href="cart.php" style="margin-left:8px">กลับตะกร้า</a>
      </div>
    <?php else: ?>
      <form method="post" action="place_order.php" style="margin-top:12px">
        <input type="hidden" name="csrf" value="<?=$csrf?>">
        <input type="hidden" name="action" value="place">
        <button class="btn-primary">ยืนยันคำสั่งซื้อ</button>
        <a class="btn-outline" href="cart.php" style="margin-left:8px">กลับตะกร้า</a>
        <a class="btn-outline" href="account/profile.php" style="margin-left:8px">แก้ไขที่อยู่</a>
      </form>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
