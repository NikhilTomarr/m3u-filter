<?php
// Config
$githubRawUrl = 'https://raw.githubusercontent.com/USER/REPO/BRANCH/path/to/source.json'; // ya .m3u ya aapki API [web:11]
$defaultUA = 'tv.accedo.airtel.wynk/1.97.1 (Linux;Android 11) ExoPlayerLib/2.19.1'; // EXTVLCOPT UA [web:8]
header('Content-Type: audio/x-mpegurl'); // M3U MIME [web:16]

// Helper: no-cache GET
function http_get_nocache($url, $timeout=12) {
  $ch = curl_init();
  // Cache-bypass via headers; query param based cache-bust ab reliable nahi hai [web:7][web:13]
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_HTTPHEADER => [
      'Cache-Control: no-cache, no-store, max-age=0, must-revalidate',
      'Pragma: no-cache'
    ],
    CURLOPT_USERAGENT => 'curl/8.x',
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($body === false || $code >= 400) {
    http_response_code(502);
    exit("#EXTM3U\n# Error fetching source ($code)\n");
  }
  curl_close($ch);
  return $body;
}

// Source format assumptions:
// Option A) JSON array of items with keys:
//   name, logo, group, mpd, cookie, keyid, key, user_agent(optional) [web:1][web:10]
// Option B) Already M3U lines: pass-through (no auto-15-min, just immediate fetch) [web:16]

// Detect by first non-space char
$raw = http_get_nocache($githubRawUrl); // each request fresh fetch [web:11][web:7]
$trim = ltrim($raw);

// If JSON, map to desired M3U template
function print_header() {
  echo "#EXTM3U\n";
}

if (strlen($trim) && $trim[0] === '[') {
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    print_header();
    echo "# Parse error: invalid JSON\n";
    exit;
  }
  print_header();
  foreach ($data as $ch) {
    $name   = $ch['name']   ?? 'Channel';
    $logo   = $ch['logo']   ?? '';
    $group  = $ch['group']  ?? 'Entertainment';
    $mpd    = $ch['mpd']    ?? '';
    $cookie = $ch['cookie'] ?? '';
    $keyid  = $ch['keyid']  ?? '';
    $key    = $ch['key']    ?? '';
    $ua     = $ch['user_agent'] ?? $defaultUA;

    // Mandatory fields check
    if (!$mpd || !$keyid || !$key) continue;

    echo "#KODIPROP:inputstream.adaptive.license_type=clearkey\n"; // clearkey header [web:10]
    // Aapka vercel PHP endpoint format allowed (query ke saath) [web:10]
    $licenseUrl = "https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid={$keyid}&key={$key}";
    echo "#KODIPROP:inputstream.adaptive.license_key={$licenseUrl}\n"; // clearkey URL [web:10]
    echo "#EXTVLCOPT:http-user-agent={$ua}\n"; // UA per entry [web:8]
    $logoAttr = $logo ? " tvg-logo=\"{$logo}\"" : "";
    echo "#EXTINF:-1{$logoAttr} group-title=\"{$group}\", {$name}\n"; // EXTM3U entry [web:10]

    // URL + ||cookie=
    if ($cookie) {
      echo "{$mpd}||cookie={$cookie}\n"; // cookie inline immediately after URL [web:10]
    } else {
      echo "{$mpd}\n";
    }
  }
  exit;
}

// Else assume already an M3U source; we’ll normalize minimal:
// 1) Ensure starts with #EXTM3U
// 2) No extra auto-refresh logic; direct passthrough
$lines = preg_split("/\r\n|\n|\r/", $raw);
if (!$lines) {
  echo "#EXTM3U\n# Empty source\n";
  exit;
}
if (stripos($lines[0], '#EXTM3U') !== 0) {
  array_unshift($lines, '#EXTM3U');
}
echo implode("\n", $lines);
