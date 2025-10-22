<?php
// Source M3U URL
$src = 'https://m3u-fetcher.vercel.app/api/airtel';

// Fetch raw playlist (no transformations)
$ch = curl_init($src);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_HTTPHEADER => [
    'User-Agent: VLC/3.0 (Linux; Android)',
    // Agar source ko specific UA/cookie chahiye to yahan add karo
  ],
]);
$body = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($err || $code < 200 || $code >= 300 || !$body) {
  header('Content-Type: text/plain; charset=utf-8');
  http_response_code(502);
  echo "Upstream fetch failed."; 
  exit;
}

// Do exact replacements only
$body = str_replace(
  ['join@Billa_tv',
   'https://cdn.videas.fr/v-medias/s5/hlsv1/98/5f/985ff528-2486-41a4-a077-21c4228b2da0/1080p.m3u8'],
  ['Streamstar',
   'https://streamstartv.blogspot.com'],
  $body
);

// Output as M3U with same content otherwise
header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Cache-Control: no-store');
echo $body;
