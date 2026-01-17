<?php
// Set headers for M3U playlist
header('Content-Type: audio/x-mpegurl');
header('Content-Disposition: inline; filename="jpvali_playlist.m3u"');

// M3U file URL
$m3u_url = 'https://livetv-cb7.pages.dev/hotstar';

// Fetch the M3U content
$content = @file_get_contents($m3u_url);

if ($content === false) {
    die("#EXTM3U\n#EXTINF:-1,Error: Unable to fetch M3U file\n");
}

// Split content into lines
$lines = explode("\n", $content);
$modified_lines = [];

foreach ($lines as $line) {
    $line = trim($line);

    // REMOVE: #EXTHTTP lines completely
    if (strpos($line, '#EXTHTTP:') === 0) {
        continue; // Skip this line (don't add to modified_lines)
    }

    

    // Modify license key format
    if (strpos($line, '#KODIPROP:inputstream.adaptive.license_key=') === 0) {
        $key_part = substr($line, strlen('#KODIPROP:inputstream.adaptive.license_key='));
        $keys = explode(':', $key_part);
        if (count($keys) == 2) {
            $keyid = trim($keys[0]);
            $key = trim($keys[1]);
            $line = '#KODIPROP:inputstream.adaptive.license_key=https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid=' . $keyid . '&key=' . $key;
        }
    }

    // Modify URL: Add ||cookie= after index.mpd? AND remove &xxx=... part
    if (strpos($line, 'https://jiotvpllive.cdn.jio.com') === 0 && 
        strpos($line, 'index.mpd?') !== false) {

        // Replace index.mpd? with index.mpd?||cookie=
        $line = str_replace('index.mpd?', 'index.mpd?||cookie=', $line);

        // Remove &xxx=%7Ccookie=... and everything after it
        if (strpos($line, '&xxx=') !== false) {
            $line = preg_replace('/&xxx=.*$/', '', $line);
        }
    }

    $modified_lines[] = $line;
}

// Output the modified content
echo implode("\n", $modified_lines);
?>
