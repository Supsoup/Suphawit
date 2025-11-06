<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password2'] ?? '';

  // ตรวจเบื้องต้น
  if ($email === '' || $pass === '' || $pass2 === '') {
    $err = 'กรอกข้อมูลให้ครบถ้วน';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'รูปแบบอีเมลไม่ถูกต้อง';
  } elseif ($pass !== $pass2) {
    $err = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
  } else {
    // อีเมลซ้ำ?
    $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute([$email]);
    if ($st->fetch()) {
      $err = 'อีเมลนี้มีบัญชีอยู่แล้ว';
    } else {
      // ตั้งชื่อเริ่มต้นจากหน้าอีเมล
      $name = explode('@', $email, 2)[0];
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      // เพิ่มผู้ใช้ใหม่ (คอลัมน์อื่นๆ เป็น NULL ได้)
      $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?,?,?)");
      if ($ins->execute([$name, $email, $hash])) {
        // ล็อกอินอัตโนมัติ
        $_SESSION['user'] = [
          'id'    => (int)$pdo->lastInsertId(),
          'name'  => $name,
          'email' => $email
        ];
        header('Location: ../index.php'); exit;
      } else {
        $err = 'สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
      }
    }
  }
}
?>
<!doctype html><html lang="th"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>สมัครสมาชิก</title>
<link rel="stylesheet" href="../assets/styles.css">
</head><body>
<header class="topbar">
  <a class="brand-pill" href="../index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
</header>

<!-- จัดให้อยู่กลางจอ เหมือนหน้า Login -->
<main class="container" style="
  display:flex;
  justify-content:center;
  align-items:center;
  min-height: calc(100vh - 80px);
">
  <div style="
    width:100%;
    max-width: 520px;
    background:#fff;
    padding:24px 28px;
    border-radius:12px;
    box-shadow:0 8px 24px rgba(0,0,0,.08);
  ">
    <h2 class="section-title" style="text-align:center;margin-top:0">สมัครสมาชิก</h2>

    <?php if ($err): ?>
      <p style="color:#d33;text-align:center;margin-top:0"><?= htmlspecialchars($err) ?></p>
    <?php endif; ?>

    <form method="post" class="checkout-form" style="max-width:none;margin:0">
      <label>อีเมล
        <input type="email" name="email" required>
      </label>

      <label>รหัสผ่าน
        <input type="password" name="password" required>
      </label>

      <label>ยืนยันรหัสผ่าน
        <input type="password" name="password2" required>
      </label>

      <button class="primary-btn" type="submit" style="width:100%">สมัครสมาชิก</button>

      <p class="muted" style="text-align:center;margin:10px 0 0">
        มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
      </p>
    </form>
  </div>
</main>
</body></html>
