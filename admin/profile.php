<?php
// admin/profile.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// ✅ ถ้ามีไฟล์ helper ตรวจแอดมินอยู่แล้ว ใช้ของเดิมได้
// ที่นี่เช็คแบบเบา ๆ ว่ามี session admin ไหม
if (empty($_SESSION['admin'])) {
  header('Location: ../auth/login.php'); exit;
}

$adminId = (int)$_SESSION['admin']['id'];
$msg = $err = '';

// โหลดข้อมูล
$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, gender, name FROM admins WHERE id=? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) { $err = 'ไม่พบข้อมูลผู้ดูแลระบบ'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$err) {
  $first  = trim($_POST['first_name'] ?? '');
  $last   = trim($_POST['last_name']  ?? '');
  $gender = $_POST['gender'] ?? null;

  if ($first === '' || $last === '') {
    $err = 'กรุณากรอกชื่อและนามสกุลให้ครบถ้วน';
  } elseif (!in_array($gender, ['MALE','FEMALE','OTHER',''], true)) {
    $err = 'ค่าเพศไม่ถูกต้อง';
  } else {
    $displayName = trim($first . ' ' . $last);
    $up = $pdo->prepare("UPDATE admins SET first_name=?, last_name=?, gender=?, name=? WHERE id=?");
    $up->execute([$first, $last, ($gender ?: null), $displayName, $adminId]);

    // อัปเดต session เพื่อให้ topbar แสดงชื่อใหม่ทันที
    $_SESSION['admin']['name'] = $displayName;

    // โหลดใหม่
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $msg = 'บันทึกข้อมูลเรียบร้อย';
  }
}

// กรณีเพิ่งเพิ่มคอลัมน์และยังไม่มีค่า first/last ให้แยกจาก name ชั่วคราว
if (!$admin['first_name'] && $admin['name']) {
  $parts = preg_split('/\s+/', $admin['name'], 2);
  $admin['first_name'] = $admin['first_name'] ?: ($parts[0] ?? '');
  $admin['last_name']  = $admin['last_name']  ?: ($parts[1] ?? '');
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>โปรไฟล์ผู้ดูแลระบบ</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<header class="topbar">
  <a class="brand-pill" href="products.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="products.php" style="margin-right:8px">จัดการสินค้า</a>
  <a class="btn-outline" href="orders.php" style="margin-right:8px">รายการสั่งซื้อ</a>
  <a class="btn-outline" href="reports.php" style="margin-right:8px">รายงาน</a>
  <a class="btn-outline" href="profile.php" style="margin-right:8px">โปรไฟล์</a>
  <a class="btn-outline" href="../auth/logout.php">ออกจากระบบ</a>
</header>

<main class="container" style="display:flex;justify-content:center;align-items:center;min-height:calc(100vh - 80px)">
  <div style="width:100%;max-width:560px;background:#fff;padding:24px 28px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08)">
    <h2 class="section-title" style="text-align:center;margin-top:0">โปรไฟล์ผู้ดูแลระบบ</h2>

    <?php if ($msg): ?><p style="color:#2e7d32;text-align:center"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p style="color:#d32f2f;text-align:center"><?= htmlspecialchars($err) ?></p><?php endif; ?>

    <form method="post" class="checkout-form" style="max-width:none;margin:0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <label>ชื่อ
          <input type="text" name="first_name" value="<?= htmlspecialchars($admin['first_name'] ?? '') ?>" required>
        </label>
        <label>นามสกุล
          <input type="text" name="last_name" value="<?= htmlspecialchars($admin['last_name'] ?? '') ?>" required>
        </label>
      </div>

      <label>เพศ
        <select name="gender">
          <?php
            $g = $admin['gender'] ?? '';
            $opts = ['' => 'ไม่ระบุ', 'MALE'=>'ชาย', 'FEMALE'=>'หญิง', 'OTHER'=>'อื่น ๆ'];
            foreach ($opts as $val=>$txt):
          ?>
          <option value="<?= $val ?>" <?= $g===$val ? 'selected' : '' ?>><?= $txt ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>อีเมล
        <input type="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" readonly>
      </label>

      <button class="primary-btn" type="submit" style="width:100%">บันทึก</button>
    </form>
  </div>
</main>
</body>
</html>
