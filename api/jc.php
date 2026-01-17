<?php
header('Content-Type: text/plain; charset=utf-8');

// API URL with password
$apiUrl = 'https://cloudplay-app.cloudplay-help.workers.dev/hotstar?password=all';

// Fetch JSON data from the API
$jsonData = file_get_contents($apiUrl);

if ($jsonData === false) {
    die("Error: Unable to fetch data from API");
}

// Decode JSON to PHP array
$channels = json_decode($jsonData, true);

if ($channels === null) {
    die("Error: Invalid JSON data");
}

// Start M3U playlist
echo "#EXTM3U\n";

// Loop through each channel
foreach ($channels as $channel) {
    // Extract data
    $id = $channel['id'] ?? '';
    $name = $channel['name'] ?? '';
    $group = $channel['group'] ?? '';
    $logo = $channel['logo'] ?? '';
    $userAgent = $channel['user_agent'] ?? '';
    $m3u8Url = $channel['m3u8_url'] ?? '';

    // Extract headers
    $cookie = $channel['headers']['Cookie'] ?? '';
    $origin = $channel['headers']['Origin'] ?? '';
    $referer = $channel['headers']['Referer'] ?? '';

    // Build EXTINF line
    echo "#EXTINF:-1 tvg-id="$id" group-title="$group" tvg-logo="$logo",$name\n";

    // Add user-agent if present
    if (!empty($userAgent)) {
        echo "#EXTVLCOPT:http-user-agent=$userAgent\n";
    }

    // Add origin if present
    if (!empty($origin)) {
        echo "#EXTVLCOPT:http-origin=$origin\n";
    }

    // Add referrer if present
    if (!empty($referer)) {
        echo "#EXTVLCOPT:http-referrer=$referer\n";
    }

    // Build URL with cookie
    if (!empty($cookie)) {
        echo "$m3u8Url||cookie=$cookie\n";
    } else {
        echo "$m3u8Url\n";
    }

    // Add blank line between channels for readability
    echo "\n";
}
?>
