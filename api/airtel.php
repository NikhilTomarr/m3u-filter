<?php
// Upstream playlist
$src = 'https://m3u-fetcher.vercel.app/api/airtel';

// Fetch upstream
$ch = curl_init($src);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_TIMEOUT => 25,
  CURLOPT_ENCODING => '', // handle gzip/br
  CURLOPT_HTTPHEADER => [
    'User-Agent: VLC/3.0 (Linux; Android)'
  ],
]);
$txt = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($err || $code < 200 || $code >= 300 || $txt === false) {
  header('Content-Type: text/plain; charset=utf-8');
  http_response_code(502);
  echo "Upstream fetch failed.";
  exit;
}

// Step 1: exact token replacements (all occurrences)
$txt = str_replace(
  [
    'join@Billa_tv',
    'https://cdn.videas.fr/v-medias/s5/hlsv1/98/5f/985ff528-2486-41a4-a077-21c4228b2da0/1080p.m3u8'
  ],
  [
    'Streamstar',
    'https://streamstartv.blogspot.com'
  ],
  $txt
);

// Step 2: split into logical channel blocks using #EXTINF as anchor
$lines = preg_split("/\r\n|\r|\n/", $txt);
$out = [];
$block = [];

$flush = function() use (&$block, &$out) {
  if (!$block) return;

  // Collect fields within the block
  $license_type = [];
  $license_key  = null;
  $extinf       = null;
  $extvlcopt    = null;
  $exthttp_json = null;
  $url          = null;
  $others       = [];

  foreach ($block as $ln) {
    $l = trim($ln);
    if ($l === '') continue;

    if (stripos($l, '#KODIPROP:inputstream.adaptive.license_type=clearkey') === 0) {
      $license_type[] = $l;
      continue;
    }
    if (stripos($l, '#KODIPROP:inputstream.adaptive.license_key=') === 0) {
      $license_key = $l;
      continue;
    }
    if (stripos($l, '#EXTINF:') === 0) {
      $extinf = $l;
      continue;
    }
    if (stripos($l, '#EXTVLCOPT:http-user-agent=') === 0) {
      $extvlcopt = $l;
      continue;
    }
    if (stripos($l, '#EXTHTTP:') === 0) {
      $exthttp_json = $l;
      continue;
    }
    if ($l[0] !== '#') {
      // first non-tag line as URL
      if ($url === null) $url = $l;
      else $others[] = $l;
      continue;
    }
    $others[] = $l;
  }

  // Build cookie inline if present
  $cookie_inline = '';
  if ($exthttp_json) {
    // format: #EXTHTTP:{"cookie":"..."}
    if (preg_match('/#EXTHTTP:\s*(\{.*\})/i', $exthttp_json, $m)) {
      $json = $m[1];
      $obj = json_decode($json, true);
      if (is_array($obj) && isset($obj['cookie'])) {
        $cookie_inline = '||cookie=' . $obj['cookie'];
      }
    }
  }

  // Compress duplicate license_type to exactly one line
  $license_type_line = '#KODIPROP:inputstream.adaptive.license_type=clearkey';
  if (empty($license_type)) {
    // If block had none, keep none
    $license_type_line = null;
  }

  // Emit in requested order:
  // license_type (1) -> license_key (1 if exists) -> EXTVLCOPT -> EXTINF -> URL||cookie
  if ($license_type_line) $out[] = $license_type_line;
  if ($license_key)       $out[] = $license_key;
  if ($extvlcopt)         $out[] = $extvlcopt;
  if ($extinf)            $out[] = $extinf;
  if ($url)               $out[] = $url . $cookie_inline;

  // Preserve any other lines that didn't fit known tags (optional: keep them below)
  foreach ($others as $o) $out[] = $o;

  // Separator line to keep readability (optional)
  // $out[] = '';
  $block = [];
};

// Iterate and group
foreach ($lines as $ln) {
  if (strpos($ln, '#EXTINF:') === 0 && !empty($block)) {
    // new block starts, flush old
    $flush();
  }
  $block[] = $ln;
}
// Flush last
$flush();

// Final text
$result = implode("\n", $out);

// Output as M3U
header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Cache-Control: no-store');
echo $result;
