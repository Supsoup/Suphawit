<?php
// auth/reset.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

$err = '';
$ok  = '';
$email = trim($_POST['email'] ?? ($_GET['email'] ?? ''));
$otp   = trim($_POST['otp']   ?? ($_GET['otp']   ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $newpass = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        // ตรวจความถูกต้องพื้นฐาน
        if ($email === '' || $otp === '') {
            throw new Exception('กรุณากรอกอีเมลและรหัส OTP');
        }
        if (strlen($newpass) < 8) {
            throw new Exception('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
        }
        if ($newpass !== $confirm) {
            throw new Exception('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
        }

        // ตรวจสอบผู้ใช้
        $u = $pdo->prepare("SELECT id, email FROM users WHERE email=?");
        $u->execute([$email]);
        $user = $u->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new Exception('ข้อมูลไม่ถูกต้อง หรือรหัส OTP ไม่ถูกต้อง');

        // หา OTP ที่ยังไม่หมดอายุ
        $q = $pdo->prepare("
          SELECT id, otp_code, expires_at
          FROM password_resets
          WHERE user_id = ?
            AND otp_code = ?
            AND expires_at >= NOW()
          ORDER BY id DESC
          LIMIT 1
        ");
        $q->execute([$user['id'], $otp]);
        $pr = $q->fetch(PDO::FETCH_ASSOC);
        if (!$pr) {
            throw new Exception('รหัส OTP ไม่ถูกต้อง หรือหมดอายุแล้ว');
        }

        // อัปเดตรหัสผ่าน
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $up->execute([$hash, $user['id']]);

        // ลบ OTP ทั้งหมดของ user เพื่อป้องกันการใช้ซ้ำ
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

        // แจ้งสำเร็จ
        $ok = 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว คุณสามารถเข้าสู่ระบบได้ทันที';
    } catch (Throwable $e) {
        // สำหรับดีบักชั่วคราว: $err = $e->getMessage();
        $err = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ตั้งรหัสผ่านใหม่</title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
  .auth-wrap{max-width:720px;margin:0 auto;display:grid;grid-template-columns:1fr;gap:16px}
  .auth-card{border:1px solid #e5e7eb;background:#fff;border-radius:14px;padding:18px}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="../index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="login.php">เข้าสู่ระบบ</a>
</header>

<main class="container">
  <h2 class="section-title">ตั้งรหัสผ่านใหม่</h2>

  <div class="auth-wrap">
    <div class="auth-card">
      <?php if ($err): ?>
        <p style="color:#dc2626;margin-top:0"><?= htmlspecialchars($err) ?></p>
      <?php elseif ($ok): ?>
        <p style="color:#16a34a;margin-top:0"><?= htmlspecialchars($ok) ?></p>
        <p><a class="btn-outline" href="login.php">ไปหน้าเข้าสู่ระบบ</a></p>
      <?php else: ?>
        <p class="muted" style="margin-top:0">กรอกรหัส OTP ที่ได้รับทางอีเมล พร้อมตั้งรหัสผ่านใหม่</p>
      <?php endif; ?>

      <?php if (!$ok): ?>
      <form method="post" class="checkout-form" style="max-width:420px;margin:0 auto">
        <label>อีเมล
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </label>
        <label>รหัส OTP
          <input type="text" name="otp" value="<?= htmlspecialchars($otp) ?>" maxlength="6" required>
        </label>
        <label>รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)
          <input type="password" name="password" required>
        </label>
        <label>ยืนยันรหัสผ่านใหม่
          <input type="password" name="password_confirm" required>
        </label>
        <button class="primary-btn" type="submit">ยืนยันการตั้งรหัสผ่านใหม่</button>
        <p class="muted">ยังไม่ได้ OTP? <a href="forgot.php">ขอรหัสใหม่</a></p>
      </form>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>
