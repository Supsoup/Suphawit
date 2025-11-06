<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// ออเดอร์ที่นับเป็นยอดขาย
$PAID_STATUSES = ["PAID_CONFIRMED", "SHIPPING", "DELIVERED"];

/* ---------------- รายวัน (30 วัน) ---------------- */
$sqlDaily = "
  SELECT DATE(o.placed_at) AS d, SUM(oi.qty * oi.unit_price) AS revenue
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  WHERE o.status IN (" . implode(',', array_fill(0, count($PAID_STATUSES), '?')) . ")
  GROUP BY DATE(o.placed_at)
  ORDER BY d ASC
  LIMIT 30
";
$st = $pdo->prepare($sqlDaily); $st->execute($PAID_STATUSES);
$daily = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- รายเดือน (12 เดือน) ---------------- */
$sqlMonthly = "
  SELECT DATE_FORMAT(o.placed_at, '%Y-%m') AS ym, SUM(oi.qty * oi.unit_price) AS revenue
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  WHERE o.status IN (" . implode(',', array_fill(0, count($PAID_STATUSES), '?')) . ")
  GROUP BY DATE_FORMAT(o.placed_at, '%Y-%m')
  ORDER BY ym ASC
  LIMIT 12
";
$st = $pdo->prepare($sqlMonthly); $st->execute($PAID_STATUSES);
$monthly = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- หมวดสินค้า (เดือนนี้) ---------------- */
$sqlByCatThisMonth = "
  SELECT COALESCE(c.name, 'ไม่ระบุหมวด') AS category, SUM(oi.qty * oi.unit_price) AS revenue
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  JOIN products p ON p.id = oi.product_id
  LEFT JOIN categories c ON c.id = p.category_id
  WHERE o.status IN (" . implode(',', array_fill(0, count($PAID_STATUSES), '?')) . ")
    AND DATE_FORMAT(o.placed_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
  GROUP BY category
  ORDER BY revenue DESC
";
$st = $pdo->prepare($sqlByCatThisMonth); $st->execute($PAID_STATUSES);
$byCat = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- สรุปยอด ---------------- */
$summary = ['this_month' => 0.0, 'today' => 0.0];

$st = $pdo->prepare("
  SELECT SUM(oi.qty * oi.unit_price)
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  WHERE o.status IN (" . implode(',', array_fill(0, count($PAID_STATUSES), '?')) . ")
    AND DATE_FORMAT(o.placed_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
");
$st->execute($PAID_STATUSES);
$summary['this_month'] = (float)$st->fetchColumn();

$st = $pdo->prepare("
  SELECT SUM(oi.qty * oi.unit_price)
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  WHERE o.status IN (" . implode(',', array_fill(0, count($PAID_STATUSES), '?')) . ")
    AND DATE(o.placed_at) = CURDATE()
");
$st->execute($PAID_STATUSES);
$summary['today'] = (float)$st->fetchColumn();
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>รายงานยอดขาย</title>
<link rel="stylesheet" href="../assets/styles.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<style>
  :root{
    --bg:#eef5f8; --card:#fff; --ink:#0b1b28; --muted:#6b7b86; --line:#e6edf2;
    --primary:#2a8ec9; --primary-ink:#fff; --accent:#4f46e5; --green:#16a34a;
    --shadow:0 12px 28px rgba(13,34,51,.08);
  }
  body{background:var(--bg); color:var(--ink)}
  .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:16px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;box-shadow:var(--shadow)}
  .kpi{display:flex;align-items:center;justify-content:space-between}
  .kpi .val{font-size:30px;font-weight:900}
  .pill{display:inline-block;border:1px solid var(--line);background:#f7fbff;padding:.25rem .6rem;border-radius:999px;font-weight:700;color:#0b5394}
  .chart-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:16px;margin-top:16px}
  @media (max-width:1100px){ .chart-grid{grid-template-columns:1fr} }
  h3{margin:18px 0 10px}
  .muted{color:var(--muted)}
  .empty{display:flex;align-items:center;justify-content:center;height:220px;color:var(--muted)}
  canvas{width:100% !important;height:auto !important}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand-pill" href="products.php">Game Zone Decor</a>
  <div style="flex:1"></div>
  <a class="btn-outline" href="reports.php">รายงาน</a>
  <a class="btn-outline" href="orders.php" style="margin-left:8px">รายการสั่งซื้อ</a>
  <a class="btn-outline" href="products.php" style="margin-left:8px">จัดการสินค้า</a>
  <a class="btn-outline" href="../auth/logout.php" style="margin-left:8px">ออกจากระบบ</a>
</header>

<main class="container">
  <h2 class="section-title">รายงานยอดขาย</h2>

  <!-- KPIs -->
  <div class="cards">
    <div class="card kpi">
      <div>
        <div class="muted">ยอดขายวันนี้</div>
        <div class="val"><?= number_format($summary['today'], 2) ?> THB</div>
      </div>
      <span class="pill">THB / day</span>
    </div>
    <div class="card kpi">
      <div>
        <div class="muted">ยอดขายเดือนนี้</div>
        <div class="val"><?= number_format($summary['this_month'], 2) ?> THB</div>
      </div>
      <span class="pill">THB / month</span>
    </div>
  </div>

  <div class="chart-grid">
    <!-- รายวัน -->
    <div class="card">
      <h3>กราฟยอดขายรายวัน (30 วันล่าสุด)</h3>
      <?php if (empty($daily)): ?>
        <div class="empty">ยังไม่มียอดขายในช่วงนี้</div>
      <?php else: ?>
        <canvas id="chartDaily"></canvas>
      <?php endif; ?>
    </div>

    <!-- แยกตามหมวด -->
    <div class="card">
      <h3>ยอดขายแยกตามหมวด (เดือนนี้)</h3>
      <?php if (empty($byCat)): ?>
        <div class="empty">ยังไม่มีรายการในเดือนนี้</div>
      <?php else: ?>
        <canvas id="chartCategory"></canvas>
      <?php endif; ?>
    </div>

    <!-- รายเดือน -->
    <div class="card" style="grid-column:1/-1">
      <h3>กราฟยอดขายรายเดือน (12 เดือนล่าสุด)</h3>
      <?php if (empty($monthly)): ?>
        <div class="empty">ยังไม่มียอดขายในช่วงนี้</div>
      <?php else: ?>
        <canvas id="chartMonthly"></canvas>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
  // Helpers
  const fmtTHB = v => new Intl.NumberFormat('th-TH',{style:'currency',currency:'THB',maximumFractionDigits:0}).format(v||0);

  // Data from PHP
  const daily = <?= json_encode($daily) ?>;
  const monthly = <?= json_encode($monthly) ?>;
  const byCat = <?= json_encode($byCat) ?>;

  // Theme colors
  const C_PRIMARY = '#2a8ec9';
  const C_ACCENT  = '#4f46e5';
  const C_FILL1   = 'rgba(42,142,201,.18)';
  const C_FILL2   = 'rgba(79,70,229,.2)';

  // Create gradient helper
  function makeGradient(ctx, c1, c2){
    const g = ctx.createLinearGradient(0,0,0,220);
    g.addColorStop(0, c1);
    g.addColorStop(1, 'rgba(255,255,255,0)');
    return g;
  }

  // Daily (Line)
  if (daily.length){
    const ctx = document.getElementById('chartDaily').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: daily.map(r => r.d),
        datasets: [{
          label: 'ยอดขาย (THB)',
          data: daily.map(r => +r.revenue),
          fill: true,
          borderColor: C_PRIMARY,
          pointBackgroundColor: C_PRIMARY,
          pointRadius: 3,
          borderWidth: 2,
          tension: .35,
          backgroundColor: makeGradient(ctx, C_FILL1, 'transparent')
        }]
      },
      options: {
        plugins: {
          legend: { display:false },
          tooltip: {
            callbacks: { label: (ctx)=> ' ' + fmtTHB(ctx.parsed.y) }
          }
        },
        interaction: { mode:'index', intersect:false },
        scales: {
          x: { grid: { display:false } },
          y: {
            beginAtZero: true,
            ticks: { callback: v => v.toLocaleString('th-TH') },
            grid: { color:'rgba(0,0,0,.06)' }
          }
        }
      }
    });
  }

  // Category (Pie)
  if (byCat.length){
    const ctx = document.getElementById('chartCategory').getContext('2d');
    new Chart(ctx, {
      type: 'pie',
      data: {
        labels: byCat.map(r => r.category),
        datasets: [{
          data: byCat.map(r => +r.revenue),
          backgroundColor: ['#2a8ec9','#4f46e5','#16a34a','#f59e0b','#ef4444','#9333ea','#0ea5e9','#fb7185']
        }]
      },
      options:{
        plugins:{
          legend:{ position:'bottom' },
          tooltip:{ callbacks:{ label:(c)=> ' ' + c.label + ': ' + fmtTHB(c.parsed) } }
        }
      }
    });
  }

  // Monthly (Bar)
  if (monthly.length){
    const ctx = document.getElementById('chartMonthly').getContext('2d');
    const grad = makeGradient(ctx, C_FILL2, 'transparent');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: monthly.map(r => r.ym),
        datasets: [{
          label: 'ยอดขาย (THB)',
          data: monthly.map(r => +r.revenue),
          backgroundColor: grad,
          borderColor: C_ACCENT,
          borderWidth: 2,
          borderRadius: 8,
          maxBarThickness: 40
        }]
      },
      options: {
        plugins: {
          legend: { display:false },
          tooltip: { callbacks: { label: (ctx)=> ' ' + fmtTHB(ctx.parsed.y) } }
        },
        scales: {
          x: { grid:{ display:false } },
          y: {
            beginAtZero:true,
            ticks:{ callback:v=> v.toLocaleString('th-TH') },
            grid:{ color:'rgba(0,0,0,.06)' }
          }
        }
      }
    });
  }
</script>
</body>
</html>
