<?php
// /admin/orders.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }

expireStaleOrders($pdo);

/* ---------- helper: แปลง/แสดงสถานะ ---------- */
function normalize_status(?string $code): ?string {
  if ($code === null) return null;
  $c = strtoupper(trim($code));
  if ($c === '') return null;
  // map สถานะรุ่นเก่า → ใหม่
  if ($c === 'PENDING') $c = 'PENDING_PAYMENT';
  if ($c === 'CANCELLED_INVALID')  $c = 'CANCELLED';
  if (in_array($c, ['CANCELLED_TIMEOUT','TIMEOUT'], true)) $c = 'EXPIRED';
  if (in_array($c, ['CANCELLED_BY_CUSTOMER','BY_CUSTOMER'], true)) $c = 'CANCELLED';
  return $c;
}
function human_status(?string $code, ?string $reason=null, ?string $courier=null, ?string $track=null): string {
  $code = normalize_status($code);
  if (!$code) return '-';
  switch ($code) {
    case 'PENDING_PAYMENT': return 'ยืนยันการสั่งซื้อและรอชำระเงิน';
    case 'PAID_CHECKING'  : return 'ชำระเงินสำเร็จรอการตรวจสอบ';
    case 'PAID_CONFIRMED' : return 'ชำระเงินสมบูรณ์รอจัดส่ง';
    case 'SHIPPING'       :
      $txt = 'ดำเนินการจัดส่งเรียบร้อย';
      if ($courier || $track) $txt .= ' • '.trim(($courier ?: '-') . ' / ' . ($track ?: '-'));
      return $txt;
    case 'COMPLETED'      : return 'เสร็จสิ้น';
    case 'EXPIRED'        : return 'ยกเลิกคำสั่งซื้อเนื่องจากไม่ชำระเงินตามกำหนด (โดยระบบ)';
    case 'CANCELLED'      :
      $r = strtoupper(trim((string)$reason));
      if     ($r === 'INVALID_PAYMENT')   return 'ยกเลิกคำสั่งซื้อเนื่องจากชำระเงินไม่ถูกต้อง (โดยผู้ดูแลระบบ)';
      elseif ($r === 'BY_CUSTOMER')       return 'ยกเลิกโดยผู้สั่งซื้อ';
      elseif ($r === 'TIMEOUT_BY_SYSTEM') return 'ยกเลิกคำสั่งซื้อเนื่องจากไม่ชำระเงินตามกำหนด (โดยระบบ)';
      return 'ยกเลิกคำสั่งซื้อ';
  }
  return $code;
}
function status_badge_class(?string $code): string {
  $code = normalize_status($code);
  if ($code === 'PENDING_PAYMENT') return 'orange';
  if ($code === 'PAID_CHECKING')   return 'blue';
  if ($code === 'PAID_CONFIRMED')  return 'purple';
  if ($code === 'SHIPPING')        return 'blue';
  if ($code === 'COMPLETED')       return 'green';
  if (in_array($code, ['EXPIRED','CANCELLED'], true)) return 'red';
  return 'gray';
}

/* ---------- filter ---------- */
$filter = strtoupper(trim($_GET['status'] ?? 'ALL'));
$allowed = ['ALL','PENDING_PAYMENT','PAID_CHECKING','PAID_CONFIRMED','SHIPPING','COMPLETED','EXPIRED','CANCELLED'];
if (!in_array($filter,$allowed,true)) $filter='ALL';

/* ---------- query ---------- */
$params=[];
$sql="
 SELECT o.id,
        UPPER(TRIM(o.status))        AS order_status,
        UPPER(TRIM(o.cancel_reason)) AS cancel_reason,
        o.total_amount, o.placed_at, o.expires_at,
        o.courier, o.tracking_no,
        u.email,
        (SELECT p.slip_path FROM payments p WHERE p.order_id=o.id ORDER BY p.id DESC LIMIT 1) AS slip_path,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_rows,
        (SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi WHERE oi.order_id=o.id) AS total_qty
 FROM orders o
 LEFT JOIN users u ON u.id=o.user_id
 WHERE 1";
if ($filter!=='ALL'){ $sql.=" AND UPPER(TRIM(o.status))=?"; $params[]=$filter; }
$sql.=" ORDER BY o.id DESC LIMIT 200";

$st=$pdo->prepare($sql); $st->execute($params);
$orders=$st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>แอดมิน • คำสั่งซื้อ</title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
.badge{padding:.15rem .5rem;border-radius:999px;font-size:.8rem;display:inline-block}
.badge.gray{background:#e5e7eb}
.badge.blue{background:#bfdbfe}
.badge.green{background:#bbf7d0}
.badge.orange{background:#fed7aa}
.badge.purple{background:#e9d5ff}
.badge.red{background:#fecaca}
.muted{color:#6b7280}
.actions form{display:inline}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="products.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="products.php" style="margin-right:8px">สินค้า</a>
  <a class="btn-outline" href="orders.php">คำสั่งซื้อ</a>
</header>

<main class="container">
  <h2 class="section-title">คำสั่งซื้อ (แอดมิน)</h2>

  <form method="get" style="margin-bottom:10px">
    <label>สถานะ:
      <select name="status" onchange="this.form.submit()">
        <?php foreach($allowed as $opt): ?>
          <option value="<?= $opt ?>" <?= $opt===$filter?'selected':'' ?>>
            <?= $opt==='ALL'?'ทั้งหมด':human_status($opt) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <table class="cart-table">
    <tr>
      <th>#</th><th>ผู้ใช้</th><th>รายการ</th><th>ยอดรวม</th>
      <th>สถานะ</th><th>หมายเหตุ</th><th>ขนส่ง</th><th>สลิป</th><th>จัดการ</th>
    </tr>
    <?php foreach($orders as $o): ?>
      <?php
        $code   = $o['order_status'] ?? null;
        $reason = $o['cancel_reason'] ?? null;
        $label  = human_status($code, $reason, $o['courier'] ?? null, $o['tracking_no'] ?? null);
        $cls    = status_badge_class($code);
      ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <td><?= htmlspecialchars($o['email']??'-') ?></td>
        <!-- แสดง จำนวนชนิด · จำนวนชิ้นรวม -->
        <td><?= (int)$o['total_qty'] ?> ชิ้น</td>
        <td><?= number_format((float)$o['total_amount']) ?> THB</td>
        <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($label) ?></span></td>
        <td><?= htmlspecialchars($reason ?: '-') ?></td>
        <td><?= $o['courier'] ? htmlspecialchars($o['courier'].' / '.$o['tracking_no']) : '-' ?></td>
        <td><?= $o['slip_path'] ? '<a class="btn-outline" target="_blank" href="../'.htmlspecialchars($o['slip_path']).'">ดูสลิป</a>' : '-' ?></td>
        <td class="actions">
          <a class="btn-outline" href="order_view.php?id=<?= (int)$o['id'] ?>">แก้ไขสถานะ</a>
        </td>
      </tr>
    <?php endforeach; if(empty($orders)): ?>
      <tr><td colspan="9" class="muted">ไม่มีข้อมูล</td></tr>
    <?php endif; ?>
  </table>
</main>
</body>
</html>
