<link rel="stylesheet" href="/assets/cookies/cc.css?v=7">
<?php
header('Content-Type: text/plain; charset=UTF-8');
$send = __DIR__ . '/send-isns.php';
echo "cwd: " . __DIR__ . "\n";
echo "send-isns.php exists: " . (file_exists($send) ? "YES" : "NO") . "\n";
if (file_exists($send)) {
  echo "send-isns.php mtime: " . date('Y-m-d H:i:s', filemtime($send)) . "\n";
  $txt = file_get_contents($send);
  echo "has DEBUG probe: " . (strpos($txt, '/* PROBE DEFAULTS */') !== false ? "YES" : "NO") . "\n";
}
?>

<div id="cookie-consent-root"></div>
<script src="/assets/cookies/cc.js?v=7" defer></script>