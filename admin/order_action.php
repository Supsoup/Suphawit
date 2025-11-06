<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }

$csrf = $_POST['csrf'] ?? '';
if ($csrf !== ($_SESSION['csrf'] ?? '')) { header('Location: orders.php?err=CSRF'); exit; }

$orderId = (int)($_POST['order_id'] ?? 0);
$action  = $_POST['action'] ?? '';
if ($orderId <= 0) { header('Location: orders.php?err=bad_id'); exit; }

try {
  $pdo->beginTransaction();

  // ล็อกออเดอร์
  $st = $pdo->prepare("SELECT * FROM orders WHERE id=? FOR UPDATE");
  $st->execute([$orderId]);
  $o = $st->fetch(PDO::FETCH_ASSOC);
  if (!$o) throw new RuntimeException('ไม่พบออเดอร์');

  /* ---------- โหมดใหม่: อัปเดตสถานะรวม ---------- */
  if ($action === 'update_status') {
    $new = $_POST['new_status'] ?? '';
    $allow = ['PENDING_PAYMENT','PAID_CHECKING','PAID_CONFIRMED','SHIPPING','COMPLETED','EXPIRED','CANCELLED'];
    if (!in_array($new,$allow,true)) throw new RuntimeException('สถานะไม่ถูกต้อง');

    $courier = trim($_POST['courier'] ?? '');
    $track   = trim($_POST['tracking_no'] ?? '');
    $reason  = trim($_POST['cancel_reason'] ?? '');
    $note    = trim($_POST['cancel_note'] ?? '');

    // คืนสต็อกถ้ายกเลิกและยังไม่ถึงขั้นจัดส่ง
    if (in_array($new,['EXPIRED','CANCELLED'],true) &&
        in_array($o['status'],['PENDING_PAYMENT','PAID_CHECKING','PAID_CONFIRMED'],true)) {
      $it = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id=?");
      $it->execute([$orderId]);
      $rows = $it->fetchAll(PDO::FETCH_ASSOC);
      $up = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?");
      foreach ($rows as $r) { $up->execute([(int)$r['qty'], (int)$r['product_id']]); }
    }

    if ($new === 'SHIPPING' && $track === '') throw new RuntimeException('กรุณากรอกเลขพัสดุ');

    // สรุปเหตุผลยกเลิก
    $finalReason = null;
    if (in_array($new,['EXPIRED','CANCELLED'],true)) {
      $finalReason = $reason ?: null;
      if ($reason === 'OTHER' && $note !== '') $finalReason = 'OTHER: '.$note;
    }

    // ใช้ named placeholders ล้วน ๆ
    $up = $pdo->prepare("
      UPDATE orders
      SET status        = :status,
          cancel_reason = :reason,
          courier       = :courier,
          tracking_no   = :track
      WHERE id          = :id
    ");
    $up->execute([
      ':status'  => $new,
      ':reason'  => $finalReason,
      ':courier' => ($courier !== '' ? $courier : null),
      ':track'   => ($track   !== '' ? $track   : null),
      ':id'      => $orderId,
    ]);

    $pdo->commit();
    header('Location: orders.php?ok='.urlencode('อัปเดตสถานะเรียบร้อย')); exit;
  }

  /* ---------- ปุ่ม legacy เดิม (ยังรองรับ) ---------- */
  switch ($action) {
    case 'approve_payment':
      if ($o['status']!=='PAID_CHECKING') throw new RuntimeException('สถานะไม่ถูกต้อง');
      $pdo->prepare("UPDATE orders SET status='PAID_CONFIRMED', cancel_reason=NULL WHERE id=?")->execute([$orderId]);
      break;
    case 'reject_payment':
      if ($o['status']!=='PAID_CHECKING') throw new RuntimeException('สถานะไม่ถูกต้อง');
      $pdo->prepare("UPDATE orders SET status='PENDING_PAYMENT', cancel_reason='INVALID_PAYMENT' WHERE id=?")->execute([$orderId]);
      break;
    case 'mark_shipping':
      if ($o['status']!=='PAID_CONFIRMED') throw new RuntimeException('สถานะไม่ถูกต้อง');
      $pdo->prepare("UPDATE orders SET status='SHIPPING' WHERE id=?")->execute([$orderId]);
      break;
    case 'mark_completed':
      if ($o['status']!=='SHIPPING') throw new RuntimeException('สถานะไม่ถูกต้อง');
      $pdo->prepare("UPDATE orders SET status='COMPLETED' WHERE id=?")->execute([$orderId]);
      break;
    case 'cancel_order':
      if ($o['status']!=='PENDING_PAYMENT') throw new RuntimeException('ยกเลิกได้เฉพาะรอชำระ');
      $it = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id=?");
      $it->execute([$orderId]);
      $up = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?");
      foreach ($it->fetchAll(PDO::FETCH_ASSOC) as $r) { $up->execute([(int)$r['qty'], (int)$r['product_id']]); }
      $pdo->prepare("UPDATE orders SET status='CANCELLED', cancel_reason='CANCELLED_BY_ADMIN' WHERE id=?")->execute([$orderId]);
      break;
    default:
      // no-op
  }

  if ($pdo->inTransaction()) $pdo->commit();
  header('Location: orders.php?ok=อัปเดตสำเร็จ'); exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: orders.php?err='.urlencode($e->getMessage())); exit;
}
