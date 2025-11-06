<?php
// ... ส่วน validate email, หา user, gen OTP & save token ...

require_once __DIR__ . '/../lib/mailer.php';

$otp = $plainOtp; // สมมติคุณมี OTP 6 หลักใน $plainOtp
$subject = 'รหัส OTP สำหรับเปลี่ยนรหัสผ่าน • Game Zone Decor';
$bodyHtml = '
  <div style="font-family:Tahoma,Arial,sans-serif;max-width:520px;margin:auto">
    <h2>ยืนยันการเปลี่ยนรหัสผ่าน</h2>
    <p>รหัส OTP ของคุณคือ</p>
    <div style="font-size:28px;font-weight:700;letter-spacing:3px;background:#f1f5f9;padding:10px 14px;border-radius:8px;display:inline-block">'
      . htmlspecialchars($otp) .
    '</div>
    <p style="margin-top:12px">รหัสนี้จะหมดอายุภายใน 10 นาที</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0">
    <p style="color:#64748b">หากคุณไม่ได้ร้องขอ สามารถละทิ้งอีเมลนี้ได้</p>
  </div>
';

list($ok, $msg) = send_email($email, $subject, $bodyHtml);

if ($ok) {
    // แสดง toast/flash สำเร็จ
    $_SESSION['flash_ok'] = 'ส่งรหัส OTP ไปที่อีเมลเรียบร้อยแล้ว';
    header('Location: reset.php?email='.urlencode($email));
    exit;
} else {
    $_SESSION['flash_error'] = 'ส่งอีเมลไม่สำเร็จ: ' . $msg;
    // ไม่ควรบอกสาเหตุละเอียดกับ user ใน Prod
}
