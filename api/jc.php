<?php
// Debug mode ON - sab kuch dikhega
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Script start...\n";

// Direct API test
$apiUrl = 'https://cloudplay-app.cloudplay-help.workers.dev/hotstar?password=all';
echo "2. API URL: $apiUrl\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0\r\n",
        'timeout' => 30
    ]
]);

$jsonData = @file_get_contents($apiUrl, false, $context);
echo "3. Data length: " . (strlen($jsonData ?? '') ?: '0') . "\n";

if (!$jsonData) {
    echo "#EXTM3U\n#ERROR: API se data nahi mila\n";
    exit;
}

echo "4. Raw data preview: " . substr($jsonData, 0, 200) . "\n";

$channels = json_decode($jsonData, true);
echo "5. Channels count: " . (is_array($channels) ? count($channels) : '0') . "\n";

if (!is_array($channels) || empty($channels)) {
    echo "#ERROR: Invalid JSON\n";
    exit;
}

echo "#EXTM3U\n";
echo "#PLAYLIST READY - " . count($channels) . " channels\n\n";

foreach ($channels as $channel) {
    $name = $channel['name'] ?? 'Unknown';
    $id = $channel['id'] ?? '';
    $group = $channel['group'] ?? '';
    $logo = $channel['logo'] ?? '';
    $url = $channel['m3u8_url'] ?? '';
    
    if (!$url) continue;
    
    $cookie = $channel['headers']['Cookie'] ?? '';
    
    echo "#EXTINF:-1";
    if ($id) echo " tvg-id=\"$id\"";
    if ($group) echo " group-title=\"$group\"";
    if ($logo) echo " tvg-logo=\"$logo\"";
    echo ",$name\n";
    
    if ($cookie) {
        echo "$url||cookie=$cookie\n\n";
    } else {
        echo "$url\n\n";
    }
}

echo "// END OF PLAYLIST\n";
?>
