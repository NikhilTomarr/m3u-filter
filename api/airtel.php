<?php
// Input: M3U content jisme #EXTHTTP:{"cookie":"..."} alag line me hota hai
// Output: Wahi channels, but URL line me "||cookie=..." appended.
// Order per channel: 
// #KODIPROP:inputstream.adaptive.license_type=clearkey
// #KODIPROP:inputstream.adaptive.license_key=...
// #EXTVLCOPT:http-user-agent=...
// #EXTINF:...
// URL||cookie=...

header('Content-Type: audio/x-mpegurl');

$m3u = file_get_contents('php://input'); // Ya apna source (URL/file) yahan la sakte ho
$lines = preg_split("/[\\r\\n]+/", $m3u);
$out = [];

$pendingProps = [];
$pendingExtinf = null;
$pendingUA = null;
$pendingCookie = null;

function flush_channel(&$out, &$pendingProps, &$pendingUA, &$pendingExtinf, &$pendingCookie, $mediaUrl){
    if (!$mediaUrl) return;

    if (!empty($pendingProps['license_type'])) {
        $out[] = '#KODIPROP:inputstream.adaptive.license_type=' . $pendingProps['license_type'];
    }
    if (!empty($pendingProps['license_key'])) {
        $out[] = '#KODIPROP:inputstream.adaptive.license_key=' . $pendingProps['license_key'];
    }
    if ($pendingUA) {
        $out[] = '#EXTVLCOPT:http-user-agent=' . $pendingUA;
    }
    if ($pendingExtinf) {
        $out[] = $pendingExtinf;
    }

    if ($pendingCookie) {
        $cookie = $pendingCookie;
        if (preg_match('/\\{\"cookie\"\\s*:\\s*\"([^\"]+)\"\\}/', $cookie, $m)) {
            $cookie = $m[1];
        }
        $out[] = $mediaUrl . '||cookie=' . $cookie;
    } else {
        $out[] = $mediaUrl;
    }

    // reset for next channel
    $pendingProps = [];
    $pendingUA = null;
    $pendingExtinf = null;
    $pendingCookie = null;
}

$out[] = '#EXTM3U';
$currentMediaUrl = null;

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;

    if (stripos($line, '#EXTM3U') === 0) {
        continue; // already added
    }

    if (stripos($line, '#KODIPROP:inputstream.adaptive.license_type=') === 0) {
        // dedupe: last seen wins
        $pendingProps['license_type'] = substr($line, strlen('#KODIPROP:inputstream.adaptive.license_type='));
        continue;
    }
    if (stripos($line, '#KODIPROP:inputstream.adaptive.license_key=') === 0) {
        $pendingProps['license_key'] = substr($line, strlen('#KODIPROP:inputstream.adaptive.license_key='));
        continue;
    }
    if (stripos($line, '#EXTVLCOPT:http-user-agent=') === 0) {
        $pendingUA = substr($line, strlen('#EXTVLCOPT:http-user-agent='));
        continue;
    }
    if (stripos($line, '#EXTINF:') === 0) {
        $pendingExtinf = $line;
        continue;
    }
    if (stripos($line, '#EXTHTTP:') === 0) {
        // store cookie JSON/raw
        $pendingCookie = substr($line, strlen('#EXTHTTP:'));
        continue;
    }

    if (preg_match('/^https?:\\/\\//i', $line)) {
        // URL milte hi channel flush
        flush_channel($out, $pendingProps, $pendingUA, $pendingExtinf, $pendingCookie, $line);
        continue;
    }

    // Unknown directive safe-keep (rare)
    if ($line[0] === '#') {
        $out[] = $line;
    }
}

echo implode(\"\\n\", $out), \"\\n\";
