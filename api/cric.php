<?php

$SOURCE_URL = 'https://raw.githubusercontent.com/abusaeeidx/CricHd-playlists-Auto-Update-permanent/refs/heads/main/ALL.m3u';
$TIMEOUT = 10;

// If likely a browser (prefers HTML), redirect to Telegram
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (stripos($accept, 'text/html') !== false || stripos($userAgent, 'Mozilla') !== false) {
    header('Location: https://t.me/streamstartv', true, 302);
    exit;
}

// Custom first entry (exactly as given)
$prepend = "#EXTINF:-1 tvg-id=\"1998 | Streamstar\" group-title=\"Streamstar Special\" tvg-logo=\"https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiamoffBdXQP0r6SHT9kM1ravyBjCVbUrncneORa9h4STgb_d8iEmMyKWn5hbzNnShrdNQYmCMDmbr3xFittRirO_zNiW4ic1FpEwxoVKwxSleDLlTgx9tHmKmKWRwqIyHYWgaUohCyIYKF6TMAutBebcryI8jVyoU4YmeKLPj4dU1gvxmenQ9Lg7MpyOfK/s1280/20250321_130159.png\",@streamstartv\nhttps://fansspot.fun/promo.mp4\n";

// Fetch upstream exactly
$ch = curl_init($SOURCE_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $TIMEOUT,
    CURLOPT_TIMEOUT => $TIMEOUT,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);
$remote = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($remote === false || $code !== 200) {
    header('HTTP/1.1 502 Bad Gateway');
    header('Content-Type: text/plain; charset=utf-8');
    echo "Upstream fetch failed (HTTP $code)";
    exit;
}

// Serve playlist
header('Content-Type: application/x-mpegURL; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
echo $prepend;
echo $remote;
