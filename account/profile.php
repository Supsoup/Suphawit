<?php
// /account/profile.php (UI สวยขึ้น)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../lib/helpers.php';
require_once __DIR__.'/../lib/user.php';

require_user('account/profile.php');

$csrf = ensure_csrf();
$u = current_user();
$st = $pdo->prepare("SELECT id,email,name,gender,phone,address FROM users WHERE id=?");
$st->execute([$u['id']]);
$user = $st->fetch(PDO::FETCH_ASSOC);

$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    $err = 'Invalid CSRF token';
  } else {
    $name   = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $phone  = trim($_POST['phone'] ?? '');
    $addr   = trim($_POST['address'] ?? '');

    if ($name === '') $name = $user['email'];
    if (!in_array($gender, ['MALE','FEMALE','OTHER','',null], true)) $gender = null;

    $up = $pdo->prepare("UPDATE users SET name=?, gender=?, phone=?, address=? WHERE id=?");
    $up->execute([$name, $gender ?: null, $phone ?: null, $addr ?: null, $user['id']]);

    $_SESSION['user']['name'] = $name;
    $msg = 'บันทึกโปรไฟล์เรียบร้อย';
    $st->execute([$u['id']]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
  }
}

// ตัวอักษรย่อใน avatar
$displayName = $user['name'] ?: $user['email'];
$initial = strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'));
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>โปรไฟล์ของฉัน</title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
/* ====== สไตล์เฉพาะหน้านี้ ====== */
.profile-wrap {max-width: 920px; margin: 0 auto;}
.profile-card {background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.06);padding:24px;}
.head {display:flex;gap:16px;align-items:center;margin-bottom:18px;}
.avatar {width:64px;height:64px;border-radius:999px;background:#dbeafe;color:#1e3a8a;
  display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;box-shadow:inset 0 0 0 2px #bfdbfe;}
.email-pill {font-size:.95rem;color:#374151;}
.email-pill b{font-weight:600;}
.form-grid {display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media (max-width: 760px){ .form-grid{grid-template-columns:1fr;} }
.field label{display:block;font-weight:600;margin-bottom:6px;color:#111827;}
.input, .select, .textarea {
  width:100%; background:#fff; border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;
  font-size:1rem; outline:none; transition:all .15s;
}
.input:focus, .select:focus, .textarea:focus {border-color:#60a5fa; box-shadow:0 0 0 3px rgba(59,130,246,.15);}
.textarea {min-height:110px; resize:vertical;}
.actions {display:flex;gap:12px;justify-content:flex-start;margin-top:14px;}
.badge-success{background:#dcfce7;color:#065f46;border-radius:999px;padding:.35rem .6rem;font-weight:600;display:inline-block;margin-bottom:10px;}
.badge-error{background:#fee2e2;color:#991b1b;border-radius:999px;padding:.35rem .6rem;font-weight:600;display:inline-block;margin-bottom:10px;}
.btn-primary{background:#1d4ed8;color:#fff;border:none;border-radius:12px;padding:10px 16px;font-weight:700;cursor:pointer}
.btn-primary:hover{background:#1e40af}
.btn-outline{border:1px solid #cbd5e1;border-radius:12px;padding:10px 16px;color:#111827;text-decoration:none}
.btn-outline:hover{background:#f1f5f9}
.muted{color:#6b7280}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="../index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="orders.php">คำสั่งซื้อของฉัน</a>
  <a class="btn-outline" href="../logout.php">ออกจากระบบ</a>
</header>

<main class="container profile-wrap">
  <h2 class="section-title">ข้อมูลส่วนตัว</h2>

  <div class="profile-card">
    <div class="head">
      <div class="avatar"><?=$initial?></div>
      <div class="email-pill">อีเมล: <b><?=htmlspecialchars($user['email'])?></b></div>
    </div>

    <?php if ($msg): ?><div class="badge-success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
    <?php if ($err): ?><div class="badge-error"><?=htmlspecialchars($err)?></div><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=$csrf?>">
      <div class="form-grid">
        <div class="field">
          <label>ชื่อแสดงผล</label>
          <input class="input" type="text" name="name" value="<?=htmlspecialchars($user['name'] ?: $user['email'])?>" required>
        </div>

        <div class="field">
          <label>เพศ</label>
          <select class="select" name="gender">
            <option value="" <?=empty($user['gender'])?'selected':''?>>— ไม่ระบุ —</option>
            <option value="MALE"   <?=$user['gender']==='MALE'?'selected':''?>>ชาย</option>
            <option value="FEMALE" <?=$user['gender']==='FEMALE'?'selected':''?>>หญิง</option>
            <option value="OTHER"  <?=$user['gender']==='OTHER'?'selected':''?>>อื่น ๆ</option>
          </select>
        </div>

        <div class="field">
          <label>เบอร์โทร</label>
          <input class="input" type="text" name="phone" value="<?=htmlspecialchars($user['phone'] ?? '')?>" maxlength="30" placeholder="เช่น 08x-xxx-xxxx">
        </div>

        <div class="field" style="grid-column:1/-1">
          <label>ที่อยู่ (สำหรับจัดส่ง)</label>
          <textarea class="textarea" name="address" rows="4" placeholder="บ้านเลขที่ / ถนน / ตำบล / อำเภอ / จังหวัด / รหัสไปรษณีย์"><?=htmlspecialchars($user['address'] ?? '')?></textarea>
        </div>
      </div>

      <div class="actions">
        <button class="btn-primary">บันทึก</button>
        <a class="btn-outline" href="../index.php">กลับหน้าแรก</a>
      </div>
    </form>
  </div>

  <p class="muted" style="margin-top:12px">* ชื่อแสดงผลจะใช้ในการโชว์บนแถบด้านบน</p>
</main>
</body>
</html>
