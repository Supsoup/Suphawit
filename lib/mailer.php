<?php
// lib/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * ส่งอีเมล
 * @return array [bool success, string message]
 */
function send_email(string $to, string $subject, string $html): array
{
    // เปิด/ปิด SMTP (ถ้า false จะใช้ mail() ของระบบ)
    $SMTP_ENABLED = true;

    // === ตั้งค่า Gmail SMTP ===
    $SMTP_HOST = 'smtp.gmail.com';
    $SMTP_PORT = 587;                           // TLS
    $SMTP_USER = 'yourgmail@gmail.com';         // อีเมล Gmail
    $SMTP_PASS = 'xxxxxxxxxxxxxxxx';            // App Password 16 ตัว
    $FROM_EMAIL = 'yourgmail@gmail.com';        // ควรตรงกับ $SMTP_USER
    $FROM_NAME  = 'Game Zone Decor';

    // โหลด Composer autoload
    $autoload1 = __DIR__ . '/../vendor/autoload.php';
    $autoload2 = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($autoload1)) {
        require_once $autoload1;
    } elseif (file_exists($autoload2)) {
        require_once $autoload2;
    } else {
        return [false, 'Composer autoload not found (vendor/autoload.php)'];
    }

    $mail = new PHPMailer(true);

    try {
        if ($SMTP_ENABLED) {
            $mail->isSMTP();
            $mail->Host       = $SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = $SMTP_USER;
            $mail->Password   = $SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
        } else {
            $mail->isMail();
            $mail->CharSet = 'UTF-8';
        }

        $mail->setFrom($FROM_EMAIL, $FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = strip_tags($html);

        $mail->send();
        return [true, 'OK'];
    } catch (Exception $e) {
        return [false, 'Mailer error: '.$e->getMessage()];
    }
}
