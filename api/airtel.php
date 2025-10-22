<?php
// Upstream source (as-is output chahiye)
$src = 'https://m3u-fetcher.vercel.app/api/airtel';

// Fetch upstream
$ch = curl_init($src);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_TIMEOUT => 20,
  CURLOPT_ENCODING => '', // allow gzip/br decode so body plain text mile [optional]
  CURLOPT_HTTPHEADER => [
    'User-Agent: VLC/3.0 (Linux; Android)'
  ],
]);
$body = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($err || $code < 200 || $code >= 300 || $body === false) {
  header('Content-Type: text/plain; charset=utf-8');
  http_response_code(502);
  echo "Upstream fetch failed.";
  exit;
}

// EXACT replacements (all occurrences)
$body = str_replace(
  [
    'join@Billa_tv',
    'https://cdn.videas.fr/v-medias/s5/hlsv1/98/5f/985ff528-2486-41a4-a077-21c4228b2da0/1080p.m3u8'
  ],
  [
    'Streamstar',
    'https://streamstartv.blogspot.com'
  ],
  $body
);

// NOTE: Agar sirf pehli baar replace karna ho to uncomment:
// $body = preg_replace('/\bjoin@Billa_tv\b/', 'Streamstar', $body, 1);
// $body = preg_replace('#https?://cdn\.videas\.fr/v-medias/s5/hlsv1/98/5f/985ff528-2486-41a4-a077-21c4228b2da0/1080p\.m3u8#', 'https://streamstartv.blogspot.com', $body, 1);

// Output as M3U (no other change)
header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Cache-Control: no-store');
echo $body;
