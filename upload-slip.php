<?php
// upload-slip.php — อัปสลิป + ยกเลิกภายใน 5 นาที + หมดเวลา 10 นาทีจะยกเลิกอัตโนมัติ (เวลาคงเหลือคำนวณที่ MySQL)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/lib/helpers.php';
require_once __DIR__.'/lib/user.php';
require_user('upload-slip.php');

$order_id = (int)($_GET['id'] ?? 0);
$csrf = ensure_csrf();

// โหลดออเดอร์ + เวลาคงเหลือ
$sql = "SELECT o.*,
               TIMESTAMPDIFF(SECOND, NOW(), o.expires_at) AS pay_remain,
               TIMESTAMPDIFF(SECOND, NOW(), LEAST(DATE_ADD(o.placed_at, INTERVAL 5 MINUTE), o.expires_at)) AS cancel_remain
        FROM orders o
        WHERE o.id=? AND o.user_id=?";
$st = $pdo->prepare($sql);
$st->execute([$order_id, current_user()['id']]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { header('Location: account/orders.php'); exit; }

$payRemain    = max(0, (int)$order['pay_remain']);     // เหลือเวลาโอน (10 นาที)
$cancelRemain = max(0, (int)$order['cancel_remain']);  // เหลือเวลายกเลิก (≤ 5 นาที)

/* ---------- ฟังก์ชันคืนสต๊อก ---------- */
function restock_order(PDO $pdo, int $order_id): void {
  $q = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id=?");
  $q->execute([$order_id]);
  $upd = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?");
  while ($r = $q->fetch(PDO::FETCH_ASSOC)) $upd->execute([(int)$r['qty'], (int)$r['product_id']]);
}

/* ---------- หมดเวลาโอน → ยกเลิกอัตโนมัติ + คืนสต๊อก ---------- */
if ($order['status']==='PENDING_PAYMENT' && $payRemain<=0) {
  $pdo->beginTransaction();
  try {
    restock_order($pdo, $order_id);
    $up = $pdo->prepare("UPDATE orders
                         SET status='TIMEOUT_BY_SYSTEM', remark='EXPIRED/TIMEOUT_BY_SYSTEM', cancelled_at=NOW()
                         WHERE id=? AND status='PENDING_PAYMENT'");
    $up->execute([$order_id]);
    $pdo->commit();
    $_SESSION['flash_info'] = "ออเดอร์ #$order_id หมดเวลาชำระเงิน ระบบยกเลิกและคืนสต๊อกแล้ว";
    header('Location: account/orders.php'); exit;
  } catch(Throwable $e) { $pdo->rollBack(); }
}

$msg = $err = '';

/* ---------- ยกเลิกด้วยผู้ใช้ (ภายใน 5 นาทีแรก) ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='cancel') {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? ''))       $err = 'Invalid CSRF token';
  elseif ($order['status'] !== 'PENDING_PAYMENT')                 $err = 'ออเดอร์นี้ยกเลิกไม่ได้แล้ว';
  elseif ($cancelRemain <= 0)                                     $err = 'เกินเวลายกเลิกแล้ว';
  else {
    $pdo->beginTransaction();
    try {
      restock_order($pdo, $order_id);
      $up = $pdo->prepare("UPDATE orders
                           SET status='CANCELLED_BY_CUSTOMER', remark='BY_CUSTOMER', cancelled_at=NOW()
                           WHERE id=? AND status='PENDING_PAYMENT'");
      $up->execute([$order_id]);
      $pdo->commit();
      $_SESSION['flash_info'] = "ยกเลิกออเดอร์ #$order_id แล้ว และคืนสต๊อกเรียบร้อย";
      header('Location: account/orders.php'); exit;
    } catch(Throwable $e) { $pdo->rollBack(); $err = 'ยกเลิกไม่สำเร็จ กรุณาลองใหม่'; }
  }
}

/* ---------- อัปโหลดสลิป (จ่ายเงินโอน) ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='upload') {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? ''))       $err = 'Invalid CSRF token';
  elseif ($order['status'] !== 'PENDING_PAYMENT' || $payRemain<=0)$err = 'ออเดอร์นี้ไม่อยู่ในสถานะอัปโหลดสลิปได้';
  elseif (!isset($_FILES['slip']) || $_FILES['slip']['error']!==UPLOAD_ERR_OK) $err = 'กรุณาเลือกไฟล์สลิป';
  else {
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $type = mime_content_type($_FILES['slip']['tmp_name']);
    if (!isset($allowed[$type]))                   $err = 'ชนิดไฟล์ไม่รองรับ (JPG/PNG/WEBP)';
    elseif ($_FILES['slip']['size'] > 5*1024*1024) $err = 'ไฟล์ใหญ่เกิน 5MB';
    else {
      $dir = __DIR__.'/uploads/slips'; if (!is_dir($dir)) @mkdir($dir,0777,true);
      $fname = 'order_'.$order_id.'_'.time().'.'.$allowed[$type];
      $path  = 'uploads/slips/'.$fname;
      move_uploaded_file($_FILES['slip']['tmp_name'], __DIR__.'/'.$path);

      $pdo->beginTransaction();
      $pdo->prepare("INSERT INTO payments(order_id, slip_path, uploaded_at) VALUES(?,?,NOW())")
          ->execute([$order_id, $path]);
      $pdo->prepare("UPDATE orders SET status='PAID_CHECKING' WHERE id=? AND status='PENDING_PAYMENT'")
          ->execute([$order_id]);
      $pdo->commit();

      $msg = 'อัปโหลดสลิปเรียบร้อยแล้ว — รอตรวจสอบจากผู้ดูแล';
      // reload ค่าจากฐานข้อมูล เพื่อให้เวลาคงเหลืออัปเดต
      $st->execute([$order_id, current_user()['id']]);
      $order = $st->fetch(PDO::FETCH_ASSOC);
      $payRemain    = max(0, (int)$order['pay_remain']);
      $cancelRemain = max(0, (int)$order['cancel_remain']);
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>อัปโหลดสลิป • ออเดอร์ #<?=$order_id?></title>
<link rel="stylesheet" href="assets/styles.css">
<style>
/* ============== Theme ============== */
:root{
  --bg:#eef5f8;
  --card:#ffffff;
  --ink:#0b1b28;
  --muted:#6b7b86;
  --line:#dbe5ec;
  --primary:#2a8ec9;
  --primary-ink:#fff;
  --danger:#e11d48;
  --warn:#f59e0b;
  --success:#059669;
  --soft:#f6fafc;
  --shadow:0 10px 25px rgba(13,34,51,.07);
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:Inter,system-ui,-apple-system,Segoe UI,Helvetica,Arial}
a{color:var(--primary);text-decoration:none}
.muted{color:var(--muted)}
.container{max-width:1100px;margin:28px auto;padding:0 18px}
.section-title{font-size:28px;margin:18px 0 8px}

/* Topbar */
.topbar{position:sticky;top:0;z-index:5;display:flex;align-items:center;gap:10px;padding:10px 14px;background:linear-gradient(180deg,#ffffffcc,#ffffffcc);backdrop-filter:saturate(180%) blur(8px);border-bottom:1px solid var(--line)}
.brand-pill{background:#0e2a3f;color:#fff;padding:8px 14px;border-radius:999px;font-weight:800}
.btn-outline{border:1px solid var(--line);border-radius:12px;padding:8px 12px;color:var(--ink);background:#fff}

/* Cards & Layout */
.grid{display:grid;grid-template-columns:1.1fr 1fr .9fr;gap:18px}
@media (max-width:1024px){ .grid{grid-template-columns:1fr} }
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow)}
.card > .card-body{padding:16px}

/* Status row */
.statusbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:6px 0 16px}
.pill{display:inline-flex;align-items:center;gap:6px;padding:.38rem .7rem;border-radius:999px;font-weight:700;border:1px solid var(--line);background:var(--soft)}
.pill.warn{border-color:#fde68a;background:#fffbeb;color:#7c5306}
.pill.info{border-color:#bae6fd;background:#eff6ff;color:#0b5394}

/* QR box */
.qr-wrap{padding:12px;text-align:center}
.qr-img{max-width:320px;width:100%;height:auto;border-radius:12px;border:1px solid var(--line);display:block;margin:0 auto 10px}
.meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px}
.meta .box{background:var(--soft);border:1px solid var(--line);border-radius:12px;padding:10px 12px}
.meta .k{display:block;font-size:12px;color:var(--muted);margin-bottom:4px}
.meta .v{font-weight:800}

/* Upload panel */
.uploader{border:2px dashed #c7d7e2;border-radius:14px;padding:16px;text-align:center;transition:.2s}
.uploader:hover{background:#f7fbff}
.uploader input[type=file]{display:none}
.uploader .choose{display:inline-block;border:1px solid var(--line);border-radius:12px;padding:10px 14px;background:#fff;cursor:pointer}
.buttons{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.btn{border:none;border-radius:12px;padding:10px 14px;font-weight:800;cursor:pointer}
.btn-primary{background:var(--primary);color:var(--primary-ink)}
.btn-danger{background:var(--danger);color:#fff}
.btn:disabled{opacity:.55;cursor:not-allowed}
.helper{font-size:12px;color:var(--muted);margin-top:8px}

/* Alerts */
.alert{border-radius:12px;padding:10px 12px;margin:10px 0;border:1px solid}
.alert.ok{color:var(--success);background:#e7f7ee;border-color:#bfead1}
.alert.err{color:#b91c1c;background:#fee2e2;border-color:#fecaca}

/* Timers */
.badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:.35rem .65rem;font-weight:800;border:1px solid var(--line);background:#fff}
.badge .dot{width:7px;height:7px;border-radius:999px;background:var(--warn)}
.time{font-variant-numeric:tabular-nums}

/* Footer note */
.note{color:var(--muted);margin-top:12px}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="account/orders.php">คำสั่งซื้อของฉัน</a>
  <a class="btn-outline" href="logout.php">ออกจากระบบ</a>
</header>

<main class="container">
  <h2 class="section-title">อัปโหลดสลิป • ออเดอร์ #<?=$order_id?></h2>

  <div class="statusbar">
    <span class="pill info">สถานะ: <b><?=htmlspecialchars($order['status'])?></b></span>
    <?php if ($order['status']==='PENDING_PAYMENT'): ?>
      <span class="pill warn">เหลือเวลาโอน: <span id="cdPay" class="time"></span></span>
      <span class="pill">ยกเลิกได้ภายใน: <span id="cdCancel" class="time"></span></span>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?><div class="alert ok"><?=$msg?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert err"><?=$err?></div><?php endif; ?>

  <div class="grid">
    <!-- QR การจ่าย -->
    <div class="card">
      <div class="card-body qr-wrap">
        <img class="qr-img" src="assets/qr_promptpay.jpg" alt="QR โอนเงิน" onerror="this.style.display='none'">
        <div class="meta">
          <div class="box">
            <span class="k">ยอดที่ต้องชำระ</span>
            <span class="v"><?=number_format($order['total_amount'])?> THB</span>
          </div>
          <div class="box">
            <span class="k">เลขที่ออเดอร์</span>
            <span class="v">#<?=$order_id?></span>
          </div>
        </div>
        <p class="helper" style="margin-top:10px">แสกนด้วยแอปธนาคารของคุณเพื่อชำระเงิน</p>
      </div>
    </div>

    <!-- อัปโหลดสลิป -->
    <div class="card">
      <div class="card-body">
        <form id="uploadForm" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?=$csrf?>">
          <input type="hidden" name="action" value="upload">

          <div id="dropzone" class="uploader" <?=($order['status']!=='PENDING_PAYMENT' || $payRemain<=0)?'data-disabled="1"':''?>>
            <input id="slipFile" type="file" name="slip" accept=".jpg,.jpeg,.png,.webp"
              <?=($order['status']!=='PENDING_PAYMENT' || $payRemain<=0)?'disabled':''?>>
            <div>
              <div style="font-weight:800;margin-bottom:6px">แนบสลิป </div>
              <label for="slipFile" class="choose">เลือกไฟล์</label>
              <div id="fileName" class="helper">ยังไม่เลือกไฟล์</div>
            </div>
          </div>

          <div class="buttons">
            <button class="btn btn-primary"
              <?=($order['status']!=='PENDING_PAYMENT' || $payRemain<=0)?'disabled':''?>>อัปโหลดสลิป</button>
            <span class="badge"><span class="dot"></span> ชำระเงินภายใน 10 นาทีหลังสั่งซื้อ</span>
          </div>
        </form>
      </div>
    </div>

    <!-- ยกเลิกออเดอร์ -->
<div class="card">
  <div class="card-body">
    <form method="post" action="account/cancel_order.php"
          onsubmit="return confirm('ยืนยันยกเลิกคำสั่งซื้อ #<?=$order_id?> ?');">
      <input type="hidden" name="csrf" value="<?=$csrf?>">
      <input type="hidden" name="order_id" value="<?=$order_id?>">
      <div style="font-weight:800;margin-bottom:6px">ยกเลิกคำสั่งซื้อ (ภายใน 5 นาที)</div>
      <div class="helper" style="margin-bottom:10px">เหลือเวลายกเลิก: <b id="cdCancel2" class="time"></b></div>
      <button class="btn btn-danger" style="width:100%"
        <?=($order['status']!=='PENDING_PAYMENT' || $cancelRemain<=0)?'disabled':''?>>ยกเลิกออเดอร์</button>
      <p class="helper">เมื่อลูกค้ายกเลิก ระบบจะคืนสต๊อกสินค้าให้อัตโนมัติ</p>
    </form>
  </div>
</div>


  <p class="note">
    ออกจากหน้านี้ได้ที่ <b>คำสั่งซื้อของฉัน</b> แล้วกด <b>ชำระเงิน/อัปโหลดสลิป</b> ของออเดอร์ #<?=$order_id?> อีกครั้งได้ทุกเมื่อ
  </p>
</main>

<?php if ($order['status']==='PENDING_PAYMENT'): ?>
<script>
(function(){
  function fmt(sec){ if(sec<0) sec=0; var m=Math.floor(sec/60), s=('0'+(sec%60)).slice(-2); return (m<10?'0':'')+m+':'+s; }
  var pay = <?=$payRemain?>;
  var can = <?=$cancelRemain?>;

  function tick(){
    var p=document.getElementById('cdPay');     if(p) p.textContent = fmt(pay);
    var c=document.getElementById('cdCancel');  if(c) c.textContent = fmt(can);
    var c2=document.getElementById('cdCancel2');if(c2) c2.textContent = fmt(can);

    if (pay<=0){ location.reload(); return; }
    pay--; if (can>0) can--;
    setTimeout(tick, 1000);
  }
  tick();

  // ===== Drag & Drop uploader (ไม่กระทบ backend เดิม) =====
  var dz = document.getElementById('dropzone');
  var fi = document.getElementById('slipFile');
  var fn = document.getElementById('fileName');

  if (dz && !dz.dataset.disabled){
    ['dragover','dragenter'].forEach(ev => dz.addEventListener(ev, e=>{e.preventDefault();dz.style.background='#f0f8ff';}));
    ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e=>{e.preventDefault();dz.style.background='';}));
    dz.addEventListener('drop', e => {
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        fi.files = e.dataTransfer.files;
        fn.textContent = e.dataTransfer.files[0].name;
      }
    });
    fi.addEventListener('change', e=>{
      if (fi.files && fi.files[0]) fn.textContent = fi.files[0].name;
    });
  }
})();
</script>
<?php endif; ?>
</body>
</html>
