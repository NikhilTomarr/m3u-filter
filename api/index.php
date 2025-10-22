<?php
// 1) Origin M3U URL
$origin = 'https://m3u-fetcher.vercel.app/api/airtel';

// 2) Fetch with cURL
$ch = curl_init($origin);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT => 25,
  CURLOPT_USERAGENT => 'Mozilla/5.0 (Vercel-PHP Passthrough)',
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_SSL_VERIFYHOST => false,
]);
$body = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 3) Headers for M3U8 text
header('Content-Type: application/vnd.apple.mpegurl; charset=UTF-8');
header('Cache-Control: no-store, must-revalidate'); // avoid stale edge cache

// 4) Error handling
if ($body === false || $code < 200 || $code >= 300) {
  http_response_code(502);
  echo "#EXTM3U\n# Passthrough error: CODE=$code ERR=$err\n";
  exit;
}

// 5) Output as-is
// Ensure starts with #EXTM3U at top for players' sanity; if origin already has it, fine.
echo $body;
