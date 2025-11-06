<?php
// admin/_admin_nav.php (minimal)
// ใช้ตัวแปร $ADMIN_ACTIVE และ (ถ้ามี) $ADMIN_TITLE จากหน้าที่ include
if (!isset($ADMIN_ACTIVE)) $ADMIN_ACTIVE = basename($_SERVER['PHP_SELF']);

$labels = [
  'products.php'      => 'จัดการสินค้า',
  'product_form.php'  => 'เพิ่ม/แก้ไขสินค้า',
  'orders.php'        => 'คำสั่งซื้อ',
  'reports.php'       => 'รายงานยอดขาย',
];
$here = $labels[$ADMIN_ACTIVE] ?? ($ADMIN_TITLE ?? 'แอดมิน');
?>
<style>
  .admin-nav a { text-decoration:none }
  .admin-nav .menu a { padding:8px 10px;border-radius:10px }
  .admin-nav .menu a.active { background:#e8f0fe;color:#1a73e8;font-weight:600 }
  .breadcrumb { color:#6b778c }
  .breadcrumb a { color:#1a73e8 }
</style>

<!-- แถบบนสุด (คงปุ่มเมนูหลักไว้ตามเดิม) -->
<header class="topbar admin-nav">
  <a class="brand-pill" href="../index.php">Game Zone Decor</a>
  <nav class="menu" style="display:flex;gap:8px;margin-left:16px">
    <a href="products.php" class="<?= $ADMIN_ACTIVE==='products.php'?'active':'' ?>">สินค้า</a>
    <a href="orders.php"   class="<?= $ADMIN_ACTIVE==='orders.php'  ?'active':'' ?>">คำสั่งซื้อ</a>
    <a href="reports.php"  class="<?= $ADMIN_ACTIVE==='reports.php' ?'active':'' ?>">รายงาน</a>
  </nav>
  <div style="flex:1"></div>
  <a class="btn-outline" href="../auth/logout.php">ออก</a>
</header>

<!-- แถบ path + ปุ่มเดียว "รายงานยอดขาย" -->
<div class="subbar" style="display:flex;justify-content:space-between;align-items:center">
  <div class="breadcrumb">
    <a href="products.php">Admin</a> › <span><?= htmlspecialchars($here, ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <?php if ($ADMIN_ACTIVE !== 'reports.php'): ?>
    <a class="btn-outline" href="reports.php">รายงานยอดขาย</a>
  <?php endif; ?>
</div>
