<link rel="stylesheet" href="/assets/cookies/cc.css?v=7">
<?php
/**
 * isns-selftest.php — diagnostics for ISNS sending on hosting
 * Upload to webroot and open /isns-selftest.php in the browser.
 */
header('Content-Type: text/html; charset=UTF-8');
$ok = function($b){ return $b ? '✅' : '❌'; };
$items = [];

function envval($k){ return ini_get($k); }
function has($fn){ return function_exists($fn); }
function ext($e){ return extension_loaded($e); }
function file_exists_readable($p){ return is_file($p) && is_readable($p); }

$items[] = ['PHP version', PHP_VERSION, version_compare(PHP_VERSION, '7.4', '>=')];
$items[] = ['mail() available', has('mail') ? 'yes' : 'no', has('mail')];
$items[] = ['openssl extension', ext('openssl') ? 'yes' : 'no', ext('openssl')];
$items[] = ['allow_url_fopen', envval('allow_url_fopen'), envval('allow_url_fopen')==='1'];

$cfg_path = __DIR__.'/private/isns-config.php';
$cfg_ok = file_exists_readable($cfg_path);
$items[] = ['/private/isns-config.php present', $cfg_ok ? 'yes' : 'no', $cfg_ok];

$cfg = $cfg_ok ? require $cfg_path : [];
$smtp_host = $cfg['SMTP_HOST'] ?? '';
$smtp_user = $cfg['SMTP_USERNAME'] ?? '';
$to_addr   = $cfg['TO_ADDRESS'] ?? 'info@cogniterra.cz';
$from      = $cfg['SMTP_FROM'] ?? 'info@cogniterra.cz';

$items[] = ['PHPMailer available', class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'yes' : 'no', class_exists('PHPMailer\\PHPMailer\\PHPMailer')];
$items[] = ['SMTP host set', $smtp_host ?: '(empty)', $smtp_host !== ''];
$items[] = ['SMTP username set', $smtp_user ?: '(empty)', $smtp_user !== ''];

echo "<h1 style='font-family:system-ui'>ISNS Self-Test</h1>";
echo "<table style='border-collapse:collapse;font-family:system-ui;font-size:15px'>";
foreach ($items as $row) {
  [$label,$val,$pass] = $row;
  echo "<tr><td style='border:1px solid #ddd;padding:6px 10px'>".$label."</td><td style='border:1px solid #ddd;padding:6px 10px'>".$val."</td><td style='border:1px solid #ddd;padding:6px 10px'>".($pass?'✅':'❌')."</td></tr>";
}
echo "</table>";

echo "<h2 style='font-family:system-ui'>Outgoing tests</h2>";
echo "<ol style='font-family:system-ui'>";
echo "<li>Attempt <strong>mail()</strong>… ";
$subject = 'ISNS SELFTEST mail() '.date('Y-m-d H:i:s');
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/plain; charset=UTF-8\r\n";
$headers .= "From: Cogniterra Group <".$from.">\r\n";
$headers .= "Reply-To: Cogniterra Group <".$from.">\r\n";
$params = "-f ".$from;
$sent1 = @mail($to_addr, '=?UTF-8?B?'.base64_encode($subject).'?=', "Test body.\n", $headers, $params);
echo $sent1 ? "<strong style='color:green'>OK</strong>" : "<strong style='color:red'>FAILED</strong>";
echo "</li>";

if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') && $smtp_host && $smtp_user) {
  echo "<li>Attempt <strong>SMTP (PHPMailer)</strong>… ";
  try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->Port       = (int)($cfg['SMTP_PORT'] ?? 587);
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Username   = $smtp_user;
    $mail->Password   = $cfg['SMTP_PASSWORD'] ?? '';
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom($from, 'Cogniterra Group');
    $mail->addAddress($to_addr, 'ISNS Selftest');
    $mail->Subject = 'ISNS SELFTEST SMTP '.date('Y-m-d H:i:s');
    $mail->Body    = 'Test body via SMTP.';
    $mail->AltBody = 'Test body via SMTP.';
    $ok2 = $mail->send();
    echo $ok2 ? "<strong style='color:green'>OK</strong>" : "<strong style='color:red'>FAILED</strong>";
  } catch (Throwable $e) {
    echo "<strong style='color:red'>FAILED</strong> — ".htmlspecialchars($e->getMessage());
  }
  echo "</li>";
} else {
  echo "<li>SMTP test skipped (PHPMailer not available nebo SMTP není nastaveno).</li>";
}
echo "</ol>";

echo "<p style='font-family:system-ui;color:#666'>Pokud vše selže, zkontrolujte error_log na hostingu a hodnoty v /private/isns-config.php (SMTP_HOST/USERNAME/PASSWORD/FROM/TO_ADDRESS).</p>";

<div id="cookie-consent-root"></div>
<script src="/assets/cookies/cc.js?v=7" defer></script>