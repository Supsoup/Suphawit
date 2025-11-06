<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  $st = $pdo->prepare("SELECT id,name,email,password_hash FROM users WHERE email=?");
  $st->execute([$email]);
  $u = $st->fetch(PDO::FETCH_ASSOC);

  $ok=false;
  if ($u) {
    $info = password_get_info($u['password_hash'] ?? '');
    if (!empty($info['algo'])) $ok = password_verify($pass, $u['password_hash']);
    else $ok = hash_equals($u['password_hash'], $pass); // กันพังก่อน
  }

  if ($ok) {
    $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email']];
    $to = $_SESSION['redirect_to'] ?? '../index.php';
    unset($_SESSION['redirect_to']);
    header("Location: $to"); exit;
  } else {
    $err = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
  }
}
?>
<!doctype html><html lang="th"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>เข้าสู่ระบบ</title>
<link rel="stylesheet" href="../assets/styles.css">
</head><body>
<header class="topbar">
  <a class="brand-pill" href="../index.php">Game Zone Decor</a>
  <div style="flex:1"></div>
</header>

<!-- ทำให้กรอบขาวอยู่กึ่งกลางหน้า -->
<main class="container" style="
  display:flex;
  justify-content:center;
  align-items:center;
  min-height: calc(100vh - 80px); /* หักความสูงเฮดเดอร์คร่าว ๆ */
">
  <div style="
    width:100%;
    max-width: 480px;
    background:#fff;
    padding:24px 28px;
    border-radius:12px;
    box-shadow:0 8px 24px rgba(0,0,0,.08);
  ">
    <h2 class="section-title" style="text-align:center;margin-top:0">เข้าสู่ระบบ</h2>

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

      <button class="primary-btn" type="submit" style="width:100%">เข้าสู่ระบบ</button>

      <p class="muted"><a href="forgot.php">ลืมรหัสผ่าน?</a></p>
      <p class="muted" style="text-align:center;margin:10px 0 0">
        ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
      </p>
    </form>
  </div>
</main>
</body></html>
