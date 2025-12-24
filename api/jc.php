<?php
header('Content-Type: application/x-mpegurl');
header('Content-Disposition: inline; filename="playlist.m3u8"');

// Fetch M3U data from GitHub
$url = 'https://raw.githubusercontent.com/alex8875/m3u/refs/heads/main/jcinema.m3u';
$data = file_get_contents($url);

if ($data === false) {
    die("Error fetching data from URL");
}

// Split data into lines
$lines = explode("\n", $data);
$output = [];
$cookieValue = '';

foreach ($lines as $line) {
    $line = trim($line);

    // Check if line contains EXTHTTP with cookie
    if (strpos($line, '#EXTHTTP:') === 0) {
        // Extract cookie value from JSON
        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $jsonStr = substr($line, $jsonStart);
            $jsonData = json_decode($jsonStr, true);

            if (isset($jsonData['cookie'])) {
                $cookieValue = $jsonData['cookie'];
            }
        }
        // Skip this line (don't add to output)
        continue;
    }

    // Check if line is a stream URL (starts with http/https and not a comment)
    if (preg_match('/^https?:\/\//', $line) && strpos($line, '#') !== 0) {
        // If we have a cookie value, append it to the URL
        if (!empty($cookieValue)) {
            $line = $line . '||cookie=' . $cookieValue;
            $cookieValue = ''; // Reset cookie for next entry
        }
    }

    $output[] = $line;
}

// Output the modified M3U data
echo implode("\n", $output);
?>