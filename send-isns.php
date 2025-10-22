<?php
/**
 * send-isns.php — KEEP DESIGN / MINIMAL FIELDS
 * Požadovaná pole: sluzba, jmeno, telefon, email, zprava.
 * Server-side validace + odpověď JSON (pro AJAX). Při ne-AJAX submitu zachová redirect ?sent=1/0.
 * Odesílá přes nativní SMTP (SSL 465) dle /private/isns-config.php, fallback na mail().
 */

date_default_timezone_set('Europe/Prague');
error_reporting(E_ALL & ~E_NOTICE);

$root = __DIR__;
$configFile = $root . '/private/isns-config.php';
if (!file_exists($configFile)) {
    $resp = ['ok' => false, 'error' => 'noconfig'];
    respond($resp, false);
}
$config = include $configFile;

function val($key) { return trim((string)($_POST[$key] ?? '')); }
function sanitize_header($v) { return trim(preg_replace('/[\r\n]+/', ' ', (string)$v)); }
function is_ajax(){ return (strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest") || (!empty($_GET["ajax"])); }
function respond(array $data, $success) {
    if (is_ajax()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($success ? 200 : 400);
        header("Content-Type: application/json; charset=utf-8");
echo json_encode($data);
        exit;
    } else {
        header('Location: /isns/?sent=' . ($success ? '1' : '0') . (!empty($data['error']) ? '&err='.$data['error'] : ''));
        exit;
    }
}

// --- INPUT ---
$sluzba  = val('sluzba');
$jmeno   = val('jmeno');
$telefon = val('telefon');
$email   = val('email');
$zprava  = val('zprava');

// Server-side validace
$missing = [];
foreach (['sluzba' => $sluzba, 'jmeno' => $jmeno, 'telefon' => $telefon, 'email' => $email] as $k => $v) {
    if ($v === '') $missing[] = $k;
}
if (!empty($missing)) {
    respond(['ok' => false, 'error' => 'required', 'fields' => $missing], false);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['ok' => false, 'error' => 'email'], false);
}

// --- EMAIL ---
$subject = 'ISNS – nový dopyt (' . ($sluzba !== '' ? $sluzba : 'bez specifikace') . ')';
$lines = [
    'Služba:  ' . $sluzba,
    'Jméno:   ' . $jmeno,
    'Telefon: ' . $telefon,
    'E-mail:  ' . $email,
    'Zpráva:',
    $zprava,
];
$body = implode("\n", $lines) . "\n\nTime: " . date('Y-m-d H:i:s');

$toAddr  = sanitize_header($config['TO_ADDRESS']);
$toName  = sanitize_header($config['TO_NAME'] ?: 'ISNS');
$from    = sanitize_header($config['SMTP_FROM']);
$fromNm  = sanitize_header($config['SMTP_FROMNAME'] ?: 'ISNS Form');

function send_via_native_smtp_ssl($host, $port, $user, $pass, $from, $fromName, $to, $toName, $subject, $body) {
    $timeout = 20;
    $remote = 'ssl://' . $host . ':' . intval($port);
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$fp) return [false, "CONNECT FAIL: $errstr ($errno)"];
    stream_set_timeout($fp, $timeout);

    $read = function() use ($fp) {
        $resp = '';
        while (!feof($fp)) {
            $line = fgets($fp, 512);
            if ($line === false) break;
            $resp .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $resp;
    };
    $send = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };
    $expect = function($resp, $codes) { foreach ((array)$codes as $c) if (strpos($resp, (string)$c) === 0) return true; return false; };

    $resp = $read(); if (!$expect($resp, 220)) { fclose($fp); return [false, '220 not received: '.$resp]; }
    $hostname = gethostname() ?: 'localhost';
    $send("EHLO $hostname"); $resp = $read(); if (!$expect($resp, 250)) { fclose($fp); return [false, '250 after EHLO failed: '.$resp]; }

    $send("AUTH LOGIN"); $resp = $read(); if (!$expect($resp, 334)) { fclose($fp); return [false, '334 after AUTH LOGIN failed: '.$resp]; }
    $send(base64_encode($user)); $resp = $read(); if (!$expect($resp, 334)) { fclose($fp); return [false, '334 username prompt failed: '.$resp]; }
    $send(base64_encode($pass)); $resp = $read(); if (!$expect($resp, 235)) { fclose($fp); return [false, '235 auth failed: '.$resp]; }

    $send('MAIL FROM: <' . $from . '>');   $resp = $read(); if (!$expect($resp, 250)) { fclose($fp); return [false, '250 MAIL FROM failed: '.$resp]; }
    $send('RCPT TO: <' . $to . '>');       $resp = $read(); if (!$expect($resp, [250,251])) { fclose($fp); return [false, '250 RCPT TO failed: '.$resp]; }
    $send('DATA');                         $resp = $read(); if (!$expect($resp, 354)) { fclose($fp); return [false, '354 DATA failed: '.$resp]; }

    $fromNameEnc = mb_encode_mimeheader($fromName, 'UTF-8');
    $toNameEnc   = mb_encode_mimeheader($toName, 'UTF-8');
    $subjectEnc  = mb_encode_mimeheader($subject, 'UTF-8');
    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromNameEnc . ' <' . $from . '>',
        'To: ' . $toNameEnc . ' <' . $to . '>',
        'Subject: ' . $subjectEnc,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    $send($data); $resp = $read(); if (!$expect($resp, 250)) { fclose($fp); return [false, '250 after . failed: '.$resp]; }

    $send('QUIT'); $read(); fclose($fp);
    return [true, 'OK'];
}

// Primary: SMTP
$sent = false; $detail = '';
if (!empty($config['SMTP_ENABLED'])) {
    list($ok, $info) = send_via_native_smtp_ssl(
        $config['SMTP_HOST'] ?: 'smtp.gmail.com',
        $config['SMTP_PORT'] ?: 465,
        $config['SMTP_USERNAME'],
        $config['SMTP_PASSWORD'],
        $from, $fromNm, $toAddr, $toName, $subject, $body
    );
    $sent = $ok; $detail = 'SMTP_SSL ' . ($ok ? 'OK' : ('ERR: ' . $info));
}
if (!$sent) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromNm . ' <' . $from . '>',
        'Reply-To: ' . $from,
    ];
    $params = '-f ' . escapeshellarg($from);
    $sent = @mail($toAddr, $subject, $body, implode("\r\n", $headers), $params);
    $detail = $sent ? 'MAIL() OK' : 'MAIL() ERR';
}

// Log
$logDir = $root . '/private/logs'; if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
@file_put_contents($logDir . '/isns.log', '[' . date('Y-m-d H:i:s') . '] ' . $detail . ' to=' . $toAddr . "\n", FILE_APPEND);

respond(['ok' => $sent], $sent);


