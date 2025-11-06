<?php
// admin/products.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';



// ------------ รับพารามิเตอร์ ------------
$with = $_GET['with'] ?? 'active';            // active | all
$showAll = ($with === 'all');
$q   = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);

// ------------ โหลดหมวดหมู่สำหรับตัวกรอง ------------
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ------------ สร้าง WHERE เงื่อนไข ------------
$where = [];
$args  = [];

if (!$showAll) {
  $where[] = "p.is_active = 1";
}
if ($q !== '') {
  $where[] = "(p.name LIKE ? OR p.brand LIKE ?)";
  $args[]  = "%$q%";
  $args[]  = "%$q%";
}
if ($cat > 0) {
  $where[] = "p.category_id = ?";
  $args[]  = $cat;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ------------ ดึงสินค้าพร้อมข้อมูลเสริม ------------
$sql = "
  SELECT
    p.id, p.name, p.brand, p.price, p.stock, p.image_url, p.is_active,
    c.name AS category,
    (
      SELECT GROUP_CONCAT(g.code ORDER BY g.code SEPARATOR ', ')
      FROM product_genres pg
      JOIN genres g ON g.id = pg.genre_id
      WHERE pg.product_id = p.id
    ) AS genres_text
  FROM products p
  LEFT JOIN categories c ON c.id = p.category_id
  $whereSql
  ORDER BY p.id DESC
  LIMIT 500
";
$st = $pdo->prepare($sql);
$st->execute($args);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>จัดการสินค้า</title>
<link rel="stylesheet" href="../assets/styles.css">
<style>
  .admin-toolbar {display:flex;gap:8px;align-items:center;justify-content:space-between;margin:12px 0;}
  .table {width:100%;border-collapse:collapse}
  .table th,.table td {padding:10px;border-bottom:1px solid #e6eaef;vertical-align:middle}
  .table th {text-align:left;color:#555}
  .thumb-sm {width:64px;height:64px;object-fit:cover;border-radius:10px;background:#f2f3f5}
  .muted {color:#7a869a}
  .badge {padding:2px 8px;border-radius:999px;font-size:12px}
  .badge.muted {background:#eef2f6;color:#6b778c}
  .flash{padding:10px 12px;border-radius:10px;margin:10px 0}
  .flash.success{background:#e7f6ee;color:#146c43}
  .flash.error{background:#fdecea;color:#a61b1b}
  .filters{display:flex;gap:8px;align-items:center}
</style>
</head>
<body>

<header class="topbar">
  <a class="brand-pill" href="products.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="profile.php" style="margin-right:8px">โปรไฟล์</a>
  <a class="btn-outline" href="reports.php">ดูรายงาน</a>
  <a class="btn-outline" href="orders.php">รายการสั่งซื้อ</a>
  <a class="btn-outline" href="products.php?with=active">จัดการสินค้า</a>
  <a class="btn-outline" href="login.php" style="margin-left:8px">ออกจากระบบ</a>
</header>

<main class="container">
  <h2 class="section-title">จัดการสินค้า</h2>

  <div class="admin-toolbar">
    <form class="filters" method="get" action="products.php">
      <input type="hidden" name="with" value="<?= $showAll ? 'all':'active' ?>">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="ค้นหาชื่อ/แบรนด์" style="min-width:260px">
      <select name="cat">
        <option value="0">ทุกหมวด</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']?'selected':'' ?>>
            <?= h($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="btn-outline" type="submit">ค้นหา</button>
    </form>

    <div style="display:flex;gap:8px;align-items:center">
      <a class="btn-outline" href="products.php?with=active<?= $q!==''? '&q='.urlencode($q):'' ?><?= $cat? '&cat='.$cat:'' ?>">แสดงเฉพาะที่ขายอยู่</a>
      <a class="btn-outline" href="products.php?with=all<?= $q!==''? '&q='.urlencode($q):'' ?><?= $cat? '&cat='.$cat:'' ?>">แสดงทั้งหมด</a>
      <a class="primary-btn" href="product_form.php">+ เพิ่มสินค้า</a>
    </div>
  </div>

  <?php if (!empty($_GET['msg'])): ?>
    <div class="flash success"><?= h($_GET['msg']) ?></div>
  <?php endif; ?>
  <?php if (!empty($_GET['error'])): ?>
    <div class="flash error"><?= h($_GET['error']) ?></div>
  <?php endif; ?>

  <table class="table">
    <thead>
      <tr>
        <th style="width:60px">#</th>
        <th style="width:80px">รูป</th>
        <th>ชื่อ</th>
        <th>หมวด</th>
        <th style="width:120px">ราคา</th>
        <th style="width:90px">สต็อก</th>
        <th style="width:160px">แนวเกม</th>
        <th style="width:160px">จัดการ</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $img = $r['image_url'] ?: 'assets/no-image.png';
          $src = '../' . ltrim($img, '/');
        ?>
        <tr>
          <td class="muted"><?= (int)$r['id'] ?></td>
          <td><img class="thumb-sm" src="<?= h($src) ?>" onerror="this.src='../assets/no-image.png'"></td>
          <td>
            <div style="font-weight:600"><?= h($r['name']) ?></div>
            <div class="muted"><?= h($r['brand']) ?></div>
            <?php if ((int)$r['is_active'] === 0): ?>
              <div><span class="badge muted">ซ่อนแล้ว</span></div>
            <?php endif; ?>
          </td>
          <td><?= h($r['category'] ?? '-') ?></td>
          <td><?= number_format((float)$r['price']) ?> THB</td>
          <td><?= (int)$r['stock'] ?></td>
          <td><?= h($r['genres_text'] ?? '-') ?></td>
          <td>
            <a class="btn-outline" href="product_form.php?id=<?= (int)$r['id'] ?>">แก้ไข</a>

            <?php if ((int)$r['is_active'] === 1): ?>
              <form method="post" action="product_delete.php"
                    onsubmit="return confirm('ยืนยันลบ/ซ่อนสินค้านี้?');"
                    style="display:inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn-outline">ลบ</button>
              </form>
            <?php else: ?>
              <form method="post" action="product_restore.php" style="display:inline">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn-outline">กู้คืน</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="muted">ไม่พบสินค้า</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>

<footer class="footer"><p>© <?= date('Y') ?> Game Zone Decor</p></footer>
</body>
</html>
