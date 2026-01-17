<?php
header('Content-Type: text/plain; charset=utf-8');

// API URL with password
$apiUrl = 'https://cloudplay-app.cloudplay-help.workers.dev/hotstar?password=all';

// Function to fetch data using cURL (more reliable)
function fetchDataCurl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return false;
    }

    return $response;
}

// Try to fetch data
$jsonData = fetchDataCurl($apiUrl);

if ($jsonData === false) {
    die("Error: Unable to fetch data from API. Please check your connection.");
}

// Decode JSON to PHP array
$channels = json_decode($jsonData, true);

if ($channels === null || !is_array($channels)) {
    die("Error: Invalid JSON data received from API");
}

if (count($channels) == 0) {
    die("Error: No channels found in API response");
}

// Start M3U playlist
echo "#EXTM3U
";

// Loop through each channel
foreach ($channels as $channel) {
    // Extract data with fallback values
    $id = isset($channel['id']) ? $channel['id'] : '';
    $name = isset($channel['name']) ? $channel['name'] : 'Unknown Channel';
    $group = isset($channel['group']) ? $channel['group'] : 'General';
    $logo = isset($channel['logo']) ? $channel['logo'] : '';
    $userAgent = isset($channel['user_agent']) ? $channel['user_agent'] : '';
    $m3u8Url = isset($channel['m3u8_url']) ? $channel['m3u8_url'] : '';

    // Skip if no stream URL
    if (empty($m3u8Url)) {
        continue;
    }

    // Extract headers
    $cookie = '';
    $origin = '';
    $referer = '';

    if (isset($channel['headers']) && is_array($channel['headers'])) {
        $cookie = isset($channel['headers']['Cookie']) ? $channel['headers']['Cookie'] : '';
        $origin = isset($channel['headers']['Origin']) ? $channel['headers']['Origin'] : '';
        $referer = isset($channel['headers']['Referer']) ? $channel['headers']['Referer'] : '';
    }

    // Build EXTINF line
    echo "#EXTINF:-1";
    if (!empty($id)) echo " tvg-id="$id"";
    if (!empty($group)) echo " group-title="$group"";
    if (!empty($logo)) echo " tvg-logo="$logo"";
    echo ",$name
";

    // Add user-agent if present
    if (!empty($userAgent)) {
        echo "#EXTVLCOPT:http-user-agent=$userAgent
";
    }

    // Add origin if present
    if (!empty($origin)) {
        echo "#EXTVLCOPT:http-origin=$origin
";
    }

    // Add referrer if present
    if (!empty($referer)) {
        echo "#EXTVLCOPT:http-referrer=$referer
";
    }

    // Build URL with cookie
    if (!empty($cookie)) {
        echo "$m3u8Url||cookie=$cookie
";
    } else {
        echo "$m3u8Url
";
    }

    // Add blank line between channels
    echo "
";
}
?>
