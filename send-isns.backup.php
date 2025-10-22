<link rel="stylesheet" href="/assets/cookies/cc.css?v=7">
<?php
// send-isns.php
mb_internal_encoding('UTF-8');

// Honeypot
if (!empty($_POST['website'])) {
  header('Location: /?sent=1#isns-form');
  exit;
}

// Inputs
$service = isset($_POST['service']) ? trim($_POST['service']) : '';
$name    = isset($_POST['name'])    ? trim($_POST['name'])    : '';
$phone   = isset($_POST['phone'])   ? trim($_POST['phone'])   : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])   : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$consent = isset($_POST['consent']) ? $_POST['consent'] : '';

$validServices = array('LITE','PREMIUM','ULTRA');
if (!in_array($service, $validServices)) $service = '';

if ($service === '' || $name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($consent)) {
  header('Location: /?sent=0#isns-form');
  exit;
}

$to      = 'info@cogniterra.cz';
$subject = 'ISNS poptávka – ' . $service;
$body    = "Služba: $service\n".
           "Jméno: $name\n".
           "Telefon: $phone\n".
           "E-mail: $email\n\n".
           "Zpráva:\n$message\n\n".
           "— Odesláno z webu cogniterra.cz";

$fromAddress = 'noreply@cogniterra.cz';
$headers  = array();
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: Cogniterra <' . $fromAddress . '>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$ok = @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, implode("\r\n", $headers));

header('Location: /?sent=' . ($ok ? '1' : '0') . '#isns-form');
exit;

<div id="cookie-consent-root"></div>
<script src="/assets/cookies/cc.js?v=7" defer></script>