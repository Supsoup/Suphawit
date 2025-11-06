<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/user.php';

require_user('account/orders.php');

// 1) เคลียร์ออเดอร์ที่หมดเวลา (ระบบยกเลิกและคืนสต๊อก)
expireStaleOrders($pdo);

$uid  = (int) current_user()['id'];
$csrf = ensure_csrf();

// 2) ดึงประวัติออเดอร์ของผู้ใช้ (รวมเวลาคงเหลือแบบวินาทีที่ MySQL คำนวณให้)
$sql = "
  SELECT
    o.id,
    o.status,
    o.total_amount,
    o.placed_at,
    o.expires_at,

    -- จำนวนรายการ (กี่ชนิด)
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_rows,

    -- จำนวนชิ้นรวม (qty รวมทั้งหมด)
    (SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi WHERE oi.order_id = o.id) AS total_qty,

    (SELECT p.slip_path FROM payments p WHERE p.order_id=o.id ORDER BY p.id DESC LIMIT 1) AS slip_path,
    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), o.expires_at)) AS pay_remain,
    GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), LEAST(DATE_ADD(o.placed_at, INTERVAL 5 MINUTE), o.expires_at))) AS cancel_remain
  FROM orders o
  WHERE o.user_id = ?
  ORDER BY o.placed_at DESC
  LIMIT 100
";

$st = $pdo->prepare($sql);
$st->execute([$uid]);
$orders = $st->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันแปลง badge สถานะ
function badge(string $status): string {
  $map = [
    'PENDING_PAYMENT'       => ['#9a3412', '#fff7ed', 'รอชำระเงิน'],
    'PAID_CHECKING'         => ['#0ea5e9', '#ecfeff', 'รอตรวจสอบ'],
    'PAID_CONFIRMED'        => ['#16a34a', '#ecfdf5', 'ชำระแล้ว'],
    'SHIPPING'              => ['#4f46e5', '#eef2ff', 'กำลังจัดส่ง'],
    'COMPLETED'             => ['#155e75', '#e0f2fe', 'เสร็จสิ้น'],
    'TIMEOUT_BY_SYSTEM'     => ['#b91c1c', '#fee2e2', 'หมดเวลาชำระ (ระบบยกเลิก)'],
    'CANCELLED_BY_CUSTOMER' => ['#991b1b', '#fee2e2', 'ยกเลิกโดยผู้สั่งซื้อ'],
    'CANCELLED_INVALID'     => ['#991b1b', '#fee2e2', 'สลิปไม่ถูกต้อง/ยกเลิกโดยแอดมิน'],
  ];
  [$c,$bg,$label] = $map[$status] ?? ['#374151','#f3f4f6',$status];
  return '<span style="display:inline-block;padding:.2rem .6rem;border-radius:999px;font-weight:700;color:'.$c.';background:'.$bg.'">'.$label.'</span>';
}

// flash message
$flash = '';
if (!empty($_SESSION['flash_info'])) { $flash = $_SESSION['flash_info']; unset($_SESSION['flash_info']); }
if (!empty($_SESSION['flash_error'])){ $flash = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>คำสั่งซื้อของฉัน</title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
.table{width:100%;border-collapse:collapse;background:#fff;border-radius:14px;overflow:hidden}
.table th,.table td{border-bottom:1px solid #e5e7eb;padding:12px;text-align:left}
.table th{background:#f8fafc;font-weight:800}
.kpill{display:inline-block;border:1px solid #cbd5e1;border-radius:10px;padding:8px 12px;text-decoration:none;color:#111827}
.kpill.primary{background:#1d4ed8;color:#fff;border-color:#1d4ed8}
.muted{color:#64748b}
.count{font-weight:800}
.msg{margin-bottom:12px;padding:10px;border-radius:10px}
.msg.ok{background:#dcfce7;color:#065f46;border:1px solid #bbf7d0}
.msg.err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
.badge-note{display:inline-block;padding:.2rem .6rem;border-radius:8px;background:#f1f5f9;color:#334155}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="../index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <span class="muted" style="margin-right:8px">สวัสดี, <?=htmlspecialchars(current_user()['name'] ?? current_user()['email'])?></span>
  <a class="kpill" href="../auth/logout.php">ออก</a>
</header>

<main class="container">
  <h2 class="section-title">คำสั่งซื้อของฉัน</h2>

  <?php if ($flash): ?>
    <div class="msg ok"><?=htmlspecialchars($flash)?></div>
  <?php endif; ?>

  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>สถานะ</th>
        <th>รายการ</th>
        <th>ยอดรวม</th>
        <th>สั่งเมื่อ</th>
        <th>การชำระเงิน</th>
        <th>การดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="7" class="muted" style="text-align:center">ยังไม่มีคำสั่งซื้อ</td></tr>
      <?php endif; ?>

      <?php foreach ($orders as $o): ?>
        <?php
          $id = (int)$o['id'];
          $remainPay = (int)$o['pay_remain'];
          $remainCan = (int)$o['cancel_remain'];
          $payable   = ($o['status']==='PENDING_PAYMENT' && $remainPay>0);
          $cancelable= ($o['status']==='PENDING_PAYMENT' && $remainCan>0);
        ?>
        <tr>
          <td>#<?=$id?></td>
          <td><?=badge($o['status'])?></td>
          <td><?= (int)$o['total_qty'] ?> ชิ้น</td>
          <td><?=number_format((float)$o['total_amount'])?> THB</td>
          <td><?=htmlspecialchars($o['placed_at'])?></td>
          <td>
            <?php if ($payable): ?>
              <a class="kpill primary" href="../upload-slip.php?id=<?=$id?>">ชำระเงิน/อัปโหลดสลิป</a>
              <div class="muted" style="margin-top:6px">
                เหลือเวลาโอน: <span class="count" data-pay="<?=$remainPay?>"></span>
              </div>
            <?php else: ?>
              <?=$o['slip_path'] ? '<span class="badge-note">มีสลิปแล้ว</span>' : '<span class="muted">-</span>'?>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($cancelable): ?>
              <form method="post" action="cancel_order.php" onsubmit="return confirm('ยืนยันยกเลิกคำสั่งซื้อ #<?=$id?> ?');" style="display:inline-block">
                <input type="hidden" name="csrf" value="<?=$csrf?>">
                <input type="hidden" name="order_id" value="<?=$id?>">
                <button class="kpill" type="submit">ยกเลิก (ภายใน 5 นาที)</button>
              </form>
              <div class="muted" style="margin-top:6px">
                เหลือเวลายกเลิก: <span class="count" data-can="<?=$remainCan?>"></span>
              </div>
            <?php else: ?>
              <span class="muted">-</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<script>
// นับถอยหลังตามวินาทีที่ MySQL คำนวณให้ — ไม่ผูกกับ timezone ของ browser
(function(){
  function fmt(sec){ if (sec<0) sec=0; var m=Math.floor(sec/60); var s=('0'+(sec%60)).slice(-2); return (m<10?'0':'')+m+':'+s; }
  function tick(){
    document.querySelectorAll('[data-pay]').forEach(el=>{
      var v = parseInt(el.getAttribute('data-pay'),10); el.textContent = fmt(v); v--; el.setAttribute('data-pay', v);
      if (v<=0) location.reload(); // ให้ reload เพื่ออัปเดตสถานะหลังหมดเวลา
    });
    document.querySelectorAll('[data-can]').forEach(el=>{
      var v = parseInt(el.getAttribute('data-can'),10); el.textContent = fmt(v); v--; el.setAttribute('data-can', v);
    });
    setTimeout(tick, 1000);
  }
  tick();
})();
</script>
</body>
</html>
