<?php
header('Content-Type: text/plain; charset=utf-8');

// API URL
$apiUrl = 'https://cloudplay-app.cloudplay-help.workers.dev/hotstar?password=all';

// cURL se data fetch karo
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$jsonData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debug: raw response dekho
if ($jsonData === false || $httpCode != 200) {
    echo "#EXTM3U\n#ERROR: API se data nahi aaya (HTTP $httpCode)\n";
    exit;
}

echo "#DEBUG: Raw data length: " . strlen($jsonData) . " bytes\n";

// JSON decode karo
$channels = json_decode($jsonData, true);

// Debug: JSON check karo
if ($channels === null) {
    echo "#ERROR: JSON invalid - " . json_last_error_msg() . "\n";
    echo "#RAW: " . substr($jsonData, 0, 500) . "\n";
    exit;
}

if (!is_array($channels) || empty($channels)) {
    echo "#ERROR: No channels found\n";
    echo "#RAW: " . substr($jsonData, 0, 500) . "\n";
    exit;
}

echo "#EXTM3U\n";
echo "#Total channels: " . count($channels) . "\n\n";

// Har channel ko convert karo
foreach ($channels as $channel) {
    $id = $channel['id'] ?? '';
    $name = $channel['name'] ?? 'Unknown';
    $group = $channel['group'] ?? 'General';
    $logo = $channel['logo'] ?? '';
    $userAgent = $channel['user_agent'] ?? '';
    $m3u8Url = $channel['m3u8_url'] ?? '';
    
    if (empty($m3u8Url)) continue;
    
    $cookie = $channel['headers']['Cookie'] ?? '';
    $origin = $channel['headers']['Origin'] ?? '';
    $referer = $channel['headers']['Referer'] ?? '';

    // M3U format
    echo "#EXTINF:-1";
    if ($id) echo " tvg-id=\"$id\"";
    if ($group) echo " group-title=\"$group\"";
    if ($logo) echo " tvg-logo=\"$logo\"";
    echo ",$name\n";
    
    if ($userAgent) echo "#EXTVLCOPT:http-user-agent=$userAgent\n";
    if ($origin) echo "#EXTVLCOPT:http-origin=$origin\n";
    if ($referer) echo "#EXTVLCOPT:http-referrer=$referer\n";
    
    if ($cookie) {
        echo "$m3u8Url||cookie=$cookie\n\n";
    } else {
        echo "$m3u8Url\n\n";
    }
}
?>
