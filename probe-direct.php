<?php
// probe-direct.php — direct SMTP test without relying on query params
header('Content-Type: text/plain; charset=UTF-8');

mb_internal_encoding('UTF-8');

$RECIPIENT_TO   = 'info@cogniterra.cz';
$RECIPIENT_NAME = 'Cogniterra';

$SMTP_HOST      = 'smtp.gmail.com';
$SMTP_PORT      = 587; // STARTTLS
$SMTP_USERNAME  = 'info@cogniterra.cz';
$SMTP_PASSWORD  = 'omqvnioijfqrsnzn'; // app password
$SMTP_FROM      = 'info@cogniterra.cz';
$SMTP_FROMNAME  = 'Cogniterra';

$root = __DIR__;
$base1 = $root . '/lib/PHPMailer/src';
$base2 = $root . '/lib/PHPMailer';
$base  = is_dir($base1) ? $base1 : $base2;

if (!file_exists($base . '/PHPMailer.php')) { echo "PHPMailer not found at $base\n"; exit(1); }
require_once $base . '/PHPMailer.php';
require_once $base . '/SMTP.php';
require_once $base . '/Exception.php';

try {
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->SMTPDebug = 2;
  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USERNAME;
  $mail->Password   = $SMTP_PASSWORD;
  $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = $SMTP_PORT;
  $mail->CharSet    = 'UTF-8';

  $mail->setFrom($SMTP_FROM, $SMTP_FROMNAME);
  $mail->addAddress($RECIPIENT_TO, $RECIPIENT_NAME);
  $mail->addReplyTo($RECIPIENT_TO, $RECIPIENT_NAME);
  $mail->Subject = 'SMTP PROBE (direct)';
  $mail->Body    = 'Pokud toto čteš, SMTP funguje.';
  $mail->AltBody = 'Pokud toto čteš, SMTP funguje.';

  $ok = $mail->send();
  echo "send(): " . ($ok ? "OK" : "FAIL") . "\n";
} catch (Throwable $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}
?>

