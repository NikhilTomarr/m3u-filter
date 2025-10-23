<?php
/*
 air.php — Direct live M3U converter
 - Har request par source M3U ko fetch karke turant convert karke echo karta hai
 - artl.m3u file write/cron/manual update ki zarurat nahi
 - Output format:
   #EXTM3U
   #KODIPROP:inputstream.adaptive.license_type=clearkey
   #KODIPROP:inputstream.adaptive.license_key=https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid=...&key=...
   #EXTVLCOPT:http-user-agent=...
   #EXTINF:-1 tvg-logo="..." group-title="...", Name
   https://...mpd||cookie=Edge-Cache-Cookie=...
*/

// --------------- Config ---------------
$inputUrl = 'https://raw.githubusercontent.com/alex8875/m3u/refs/heads/main/artl.m3u'; // working source
$clearKeyBaseUrl = 'https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php';
$defaultUA = 'tv.accedo.airtel.wynk/1.97.1 (Linux;Android 11) ExoPlayerLib/2.19.1';

// --------------- HTTP helpers ---------------
function http_get_nocache($url, $timeout = 20) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_HTTPHEADER => [
      'Cache-Control: no-cache, no-store, max-age=0, must-revalidate',
      'Pragma: no-cache',
      'Accept: */*'
    ],
    CURLOPT_USERAGENT => 'curl/8.x',
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  return [$code, $body, $err];
}

// --------------- Direct stream out ---------------
[$code, $src, $err] = http_get_nocache($inputUrl);

header('Content-Type: application/x-mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="artl.m3u"');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($code >= 400 || $src === false || !trim($src)) {
  echo "#EXTM3U\n# Error fetching source ($code) ".($err ? "[$err]" : "")."\n";
  exit;
}

echo convertM3UString($src, $clearKeyBaseUrl, $defaultUA);
exit;


// --------------- Converter ---------------
/*
 Input expectations (as in your source):
 - #EXTM3U
 - #KODIPROP:inputstream.adaptive.license_key=KEYID:KEY
 - #EXTINF:-1 tvg-logo="..." group-title="...", Name
 - #EXTHTTP:{"cookie":"Edge-Cache-Cookie=..."}
 - #EXTVLCOPT:http-user-agent=...
 - STREAM_URL (e.g., https://...mpd)

 Output goals:
 - Ensure clearkey type line
 - Transform license_key to vercel endpoint with ?keyid=&key=
 - Preserve/ensure EXTVLCOPT UA (fallback to default if missing)
 - Move cookie JSON into URL suffix:  STREAM_URL||cookie=Edge-Cache-Cookie=...
 - Preserve tvg-logo, group-title, name
*/
function convertM3UString($inputContent, $clearKeyBaseUrl, $defaultUA) {
  $lines = preg_split("/\r\n|\n|\r/", $inputContent);
  $out = [];
  $out[] = "#EXTM3U";

  $current = [
    'has_clearkey_type' => false,
    'keyid' => '',
    'key'   => '',
    'ua'    => '',
    'extinf'=> '',
    'logo'  => '',
    'group' => '',
    'name'  => '',
    'cookie'=> '',
    'url'   => ''
  ];

  // helper to flush a built channel to output
  $flush = function() use (&$out, &$current, $clearKeyBaseUrl, $defaultUA) {
    if (!$current['url']) {
      // nothing to emit
      $current = [
        'has_clearkey_type' => false,
        'keyid' => '',
        'key'   => '',
        'ua'    => '',
        'extinf'=> '',
        'logo'  => '',
        'group' => '',
        'name'  => '',
        'cookie'=> '',
        'url'   => ''
      ];
      return;
    }

    // Ensure clearkey type
    $out[] = "#KODIPROP:inputstream.adaptive.license_type=clearkey";

    // Build vercel license URL if keyid/key present
    if ($current['keyid'] !== '' && $current['key'] !== '') {
      $licenseUrl = $clearKeyBaseUrl.'?keyid='.$current['keyid'].'&key='.$current['key'];
      $out[] = "#KODIPROP:inputstream.adaptive.license_key=".$licenseUrl;
    }

    // UA
    $ua = $current['ua'] !== '' ? $current['ua'] : $defaultUA;
    $out[] = "#EXTVLCOPT:http-user-agent=".$ua;

    // EXTINF line: if parsed already, reuse; else synthesize
    if ($current['extinf'] !== '') {
      $out[] = $current['extinf'];
    } else {
      $label = $current['name'] !== '' ? $current['name'] : 'Channel';
      $logoAttr = $current['logo'] !== '' ? ' tvg-logo="'.$current['logo'].'"' : '';
      $groupAttr = $current['group'] !== '' ? ' group-title="'.$current['group'].'"' : '';
      $out[] = "#EXTINF:-1".$logoAttr.$groupAttr.", ".$label;
    }

    // URL + cookie
    if ($current['cookie'] !== '') {
      $out[] = $current['url'].'||cookie='.$current['cookie'];
    } else {
      $out[] = $current['url'];
    }

    // reset for next channel
    $current = [
      'has_clearkey_type' => false,
      'keyid' => '',
      'key'   => '',
      'ua'    => '',
      'extinf'=> '',
      'logo'  => '',
      'group' => '',
      'name'  => '',
      'cookie'=> '',
      'url'   => ''
    ];
  };

  // parse helpers
  $parse_key_line = function($line) {
    // expects "#KODIPROP:inputstream.adaptive.license_key=KEYID:KEY"
    $pos = strpos($line, '=');
    if ($pos === false) return [null, null];
    $payload = trim(substr($line, $pos+1));
    // if payload already has ?keyid=&key= form, try to extract
    if (stripos($payload, 'keyid=') !== false && stripos($payload, 'key=') !== false) {
      // fallback: keep as-is by not extracting keys
      return [null, null];
    }
    $parts = explode(':', $payload, 2);
    if (count($parts) !== 2) return [null, null];
    return [$parts[0], $parts[1]];
  };

  $parse_extinf = function($line) {
    // extract logo, group, name if present
    $logo = '';
    $group = '';
    $name = '';
    if (preg_match('/tvg-logo="([^"]*)"/', $line, $m)) $logo = $m[1];
    if (preg_match('/group-title="([^"]*)"/', $line, $m)) $group = $m[1];
    $comma = strrpos($line, ',');
    if ($comma !== false) $name = trim(substr($line, $comma+1));
    return [$logo, $group, $name];
  };

  foreach ($lines as $raw) {
    $line = trim($raw);
    if ($line === '') continue;

    if (strpos($line, '#EXTM3U') === 0) {
      // already added header at start; ignore duplicates
      continue;
    }

    if (strpos($line, '#KODIPROP:inputstream.adaptive.license_type=') === 0) {
      // We will enforce clearkey on flush; skip storing type
      $current['has_clearkey_type'] = true;
      continue;
    }

    if (strpos($line, '#KODIPROP:inputstream.adaptive.license_key=') === 0) {
      list($kid, $k) = $parse_key_line($line);
      if ($kid !== null && $k !== null) {
        $current['keyid'] = $kid;
        $current['key']   = $k;
      }
      continue;
    }

    if (strpos($line, '#EXTVLCOPT:http-user-agent=') === 0) {
      $ua = trim(substr($line, strlen('#EXTVLCOPT:http-user-agent=')));
      if ($ua !== '') $current['ua'] = $ua;
      continue;
    }

    if (strpos($line, '#EXTINF:') === 0) {
      $current['extinf'] = $line;
      list($lg, $grp, $nm) = $parse_extinf($line);
      if ($lg !== '')  $current['logo']  = $lg;
      if ($grp !== '') $current['group'] = $grp;
      if ($nm !== '')  $current['name']  = $nm;
      continue;
    }

    if (strpos($line, '#EXTHTTP:') === 0) {
      // Expect JSON like {"cookie":"Edge-Cache-Cookie=..."}
      if (preg_match('/"cookie"\s*:\s*"([^"]+)"/', $line, $m)) {
        $current['cookie'] = $m[1];
      }
      continue;
    }

    if (strpos($line, '#') === 0) {
      // other tags: ignore
      continue;
    }

    // If reached here, it should be a URL line
    // Clean any previously appended cookie variants like |cookie= or %7Ccookie=
    $url = $line;
    // strip pipe cookie forms
    $url = preg_replace('/\|{1,2}cookie=.*$/', '', $url);
    // strip encoded %7Ccookie=
    $url = preg_replace('/%7Ccookie=.*$/i', '', $url);
    $current['url'] = $url;

    // End of a channel block – flush
    $flush();
  }

  // In case last block missed flushing (no trailing newline)
  $flush();

  return implode("\n", $out)."\n";
}
