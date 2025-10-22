<?php
header('Content-Type: audio/x-mpegurl');

// Helper: input M3U from ?src= URL, ?file= local file, ya raw POST
function get_input_m3u(): string {
    if (!empty($_GET['src'])) {
        $src = $_GET['src'];
        // Prefer cURL if available
        if (function_exists('curl_init')) {
            $ch = curl_init($src);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'PHP-Playlist-Converter',
            ]);
            $data = curl_exec($ch);
            curl_close($ch);
            if ($data !== false && strlen(trim($data))>0) return $data;
        } else {
            $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: PHP-Playlist-Converter\r\n"]]);
            $data = @file_get_contents($src, false, $ctx);
            if ($data !== false && strlen(trim($data))>0) return $data;
        }
    }
    if (!empty($_GET['file']) && file_exists($_GET['file'])) {
        $data = @file_get_contents($_GET['file']);
        if ($data !== false && strlen(trim($data))>0) return $data;
    }
    $raw = file_get_contents('php://input');
    if ($raw !== false && strlen(trim($raw))>0) return $raw;
    return '';
}

$m3u =
    
