<?php
// admin/order_view.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';

// (หากมีระบบตรวจสิทธิ์แอดมิน ให้เช็คที่นี่)
// if (empty($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) { ... }

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
  http_response_code(404);
  exit('ไม่พบคำสั่งซื้อ');
}

/* โหลดหัวออเดอร์ + พาธสลิปล่าสุด */
$st = $pdo->prepare("
  SELECT o.*,
         (SELECT slip_path
          FROM payments p
          WHERE p.order_id = o.id
          ORDER BY id DESC
          LIMIT 1) AS slip_path
  FROM orders o
  WHERE o.id = ?
  LIMIT 1
");
$st->execute([$order_id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) {
  http_response_code(404);
  exit('ไม่พบคำสั่งซื้อ');
}

/* ========== อัปเดตสถานะ (POST) ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
  $new = trim($_POST['status'] ?? '');
  // รายการสถานะที่อนุญาตให้แอดมินเลือก
  $allowed = [
    'PENDING_PAYMENT',
    'PAID_CHECKING',
    'PAID_CONFIRMED',
    'SHIPPING',
    'COMPLETED',
    'CANCELLED_INVALID',
    'CANCELLED_BY_CUSTOMER',   // เผื่อกรณีต้องเปลี่ยนย้อนหลัง
    'TIMEOUT_BY_SYSTEM',       // เผื่อกรณีต้องเปลี่ยนย้อนหลัง
  ];
  if (!in_array($new, $allowed, true)) {
    $_SESSION['flash_error'] = 'สถานะไม่ถูกต้อง';
    header("Location: order_view.php?id={$order_id}");
    exit;
  }

  // อัปเดตเฉพาะ status (ปลอดภัยกับ schema ที่ต่างกัน)
  $pdo->beginTransaction();
  $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$new, $order_id]);

  // ถ้าสถานะเป็น “ยกเลิก” ให้บันทึกเวลา cancelled_at หากมีคอลัมน์นี้
  if (in_array($new, ['CANCELLED_INVALID','CANCELLED_BY_CUSTOMER','TIMEOUT_BY_SYSTEM'], true)) {
    try {
      $pdo->prepare("UPDATE orders SET cancelled_at = NOW() WHERE id=?")->execute([$order_id]);
    } catch (Throwable $e) {
      // เงียบไว้ ถ้าไม่มีคอลัมน์ cancelled_at
    }
  }

  $pdo->commit();

  $_SESSION['flash_success'] = 'บันทึกสถานะเรียบร้อย';
  header("Location: order_view.php?id={$order_id}");
  exit;
}

/* โหลดรายการสินค้าในออเดอร์
   - ใช้ราคาใน order_items.price ถ้ามี
   - ถ้าไม่มี fallback เป็น products.price */
$it = $pdo->prepare("
  SELECT
    oi.product_id,
    oi.qty,
    COALESCE(oi.price, p.price)   AS unit_price,
    (oi.qty * COALESCE(oi.price, p.price)) AS subtotal,
    p.name
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = ?
  ORDER BY oi.id ASC
");
$it->execute([$order_id]);
$items = $it->fetchAll(PDO::FETCH_ASSOC);

/* formatter */
function dt($s){ return $s ? htmlspecialchars($s) : '-'; }
function badge($status){
  $map = [
    'PENDING_PAYMENT'         => ['#f59e0b','#fff7ed','รอชำระเงิน'],
    'PAID_CHECKING'           => ['#0ea5e9','#ecfeff','รอตรวจสอบการชำระ'],
    'PAID_CONFIRMED'          => ['#16a34a','#ecfdf5','ชำระเงินสมบูรณ์'],
    'SHIPPING'                => ['#6366f1','#eef2ff','กำลังจัดส่ง'],
    'COMPLETED'               => ['#0ea5e9','#ecfeff','สำเร็จ'],
    'TIMEOUT_BY_SYSTEM'       => ['#ef4444','#fef2f2','หมดเวลาชำระ / ระบบยกเลิก'],
    'CANCELLED_BY_CUSTOMER'   => ['#ef4444','#fef2f2','ยกเลิกโดยผู้สั่งซื้อ'],
    'CANCELLED_INVALID'       => ['#ef4444','#fef2f2','สลิปไม่ถูกต้อง / ยกเลิกโดยแอดมิน'],
  ];
  [$c,$bg,$label] = $map[$status] ?? ['#6b7280','#f3f4f6',$status];
  return '<span style="display:inline-block;padding:.2rem .6rem;border-radius:999px;font-weight:700;color:'.$c.';background:'.$bg.'">'.$label.'</span>';
}

// ตัวเลือกสถานะพร้อม label ไทย สำหรับ dropdown
$statusOptions = [
  'PENDING_PAYMENT'    => 'รอชำระเงิน',
  'PAID_CHECKING'      => 'รอตรวจสอบสลิป',
  'PAID_CONFIRMED'     => 'ชำระเงินสมบูรณ์',
  'SHIPPING'           => 'กำลังจัดส่ง',
  'COMPLETED'          => 'สำเร็จ',
  'CANCELLED_INVALID'  => 'ยกเลิก (สลิปไม่ถูกต้อง)',
  'CANCELLED_BY_CUSTOMER' => 'ยกเลิกโดยผู้สั่งซื้อ',
  'TIMEOUT_BY_SYSTEM'  => 'หมดเวลา/ระบบยกเลิก',
];
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>รายละเอียดคำสั่งซื้อ #<?= (int)$order_id ?></title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
.box{border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:16px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media (max-width: 900px){ .grid{grid-template-columns:1fr} }
.kv{display:grid;grid-template-columns:160px 1fr;gap:10px 14px}
.table{width:100%;border-collapse:collapse}
.table th,.table td{border-bottom:1px solid #e5e7eb;padding:10px;text-align:left}
.total{font-weight:800;font-size:18px}
.badge-note{display:inline-block;padding:.2rem .6rem;border-radius:8px;background:#f1f5f9;color:#334155}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="../admin/products.php">Admin • Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="orders.php">← กลับรายการสั่งซื้อ</a>
  <a class="btn-outline" href="../auth/logout.php">ออก</a>
</header>

<main class="container">
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <p style="color:#16a34a"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
  <?php unset($_SESSION['flash_success']); endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <p style="color:#ef4444"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
  <?php unset($_SESSION['flash_error']); endif; ?>

  <h2 class="section-title">คำสั่งซื้อ #<?= (int)$order_id ?></h2>

  <div class="grid">
    <!-- สรุปคำสั่งซื้อ -->
    <div class="box">
      <h3 style="margin:0 0 8px">ข้อมูลออเดอร์</h3>
      <div class="kv">
        <div>สถานะ</div><div><?= badge($order['status']) ?></div>
        <div>ยอดรวม</div><div><?= number_format((float)$order['total_amount']) ?> THB</div>
        <div>สั่งเมื่อ</div><div><?= dt($order['placed_at']) ?></div>
        <div>หมดเวลาชำระ</div><div><?= dt($order['expires_at']) ?></div>
        <div>ยกเลิกเมื่อ</div><div><?= dt($order['cancelled_at'] ?? null) ?></div>
        <div>หมายเหตุ</div>
        <div><?= !empty($order['remark'] ?? '') ? '<span class="badge-note">'.htmlspecialchars($order['remark']).'</span>' : '-' ?></div>
      </div>

      <?php if (!empty($order['slip_path'])): ?>
        <div style="margin-top:12px">
          <div style="font-weight:700;margin-bottom:6px">สลิปที่แนบ</div>
          <a href="../<?= htmlspecialchars($order['slip_path']) ?>" target="_blank">
            <img src="../<?= htmlspecialchars($order['slip_path']) ?>" alt="สลิป" style="max-width:260px;border:1px solid #e5e7eb;border-radius:12px">
          </a>
        </div>
      <?php endif; ?>

      <!-- ฟอร์มแก้ไขสถานะ -->
      <div style="margin-top:16px;border-top:1px dashed #e5e7eb;padding-top:12px">
        <form method="post" class="kv">
          <input type="hidden" name="action" value="update_status">
          <div>เปลี่ยนสถานะ</div>
          <div>
            <select name="status" required>
              <?php foreach ($statusOptions as $val=>$label): ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $order['status']===$val?'selected':'' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="primary-btn" type="submit" style="margin-left:8px">บันทึกสถานะ</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ที่อยู่จัดส่ง -->
    <div class="box">
      <h3 style="margin:0 0 8px">ที่อยู่สำหรับจัดส่ง</h3>
      <div class="kv">
        <div>ผู้รับ</div><div><?= htmlspecialchars($order['shipping_name'] ?? '-') ?></div>
        <div>เบอร์</div><div><?= htmlspecialchars($order['shipping_phone'] ?? '-') ?></div>
        <div>ที่อยู่</div><div><?= nl2br(htmlspecialchars($order['shipping_address'] ?? '-')) ?></div>
      </div>
    </div>
  </div>

  <!-- รายการสินค้า -->
  <div class="box" style="margin-top:16px">
    <h3 style="margin:0 0 8px">สินค้าในคำสั่งซื้อ</h3>
    <table class="table">
      <thead>
        <tr><th>สินค้า</th><th>จำนวน</th><th>ราคา/ชิ้น</th><th>รวม</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= htmlspecialchars($it['name']) ?></td>
            <td><?= (int)$it['qty'] ?></td>
            <td><?= number_format((float)$it['unit_price']) ?> THB</td>
            <td><?= number_format((float)$it['subtotal']) ?> THB</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="total" style="text-align:right">รวมทั้งสิ้น</td>
          <td class="total"><?= number_format((float)$order['total_amount']) ?> THB</td>
        </tr>
      </tfoot>
    </table>
  </div>
</main>
</body>
</html>
