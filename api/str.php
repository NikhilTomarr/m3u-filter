<?php

// Set headers for M3U playlist output
header('Content-Type: application/x-mpegurl');
header('Content-Disposition: inline; filename="converted_playlist.m3u"');

// Source M3U URL
$sourceUrl = 'https://livetv-cb7.pages.dev/star.m3u';

// Fetch fresh data from source
$m3uContent = @file_get_contents($sourceUrl);

if ($m3uContent === false) {
    die("#EXTM3U\n#EXTINF:-1,Error: Unable to fetch playlist data\nhttp://error");
}

// Split content into lines
$lines = explode("\n", $m3uContent);
$output = [];
$currentEntry = [];
$cookieValue = '';

foreach ($lines as $line) {
    $line = trim($line);

    // Check if line is #EXTM3U header
    if (strpos($line, '#EXTM3U') === 0) {
        $output[] = $line;
        continue;
    }

    // Check if line is #EXTINF
    if (strpos($line, '#EXTINF:') === 0) {
        $currentEntry = [$line];
        $cookieValue = ''; // Reset cookie for new entry
        continue;
    }

    // Check if line is #KODIPROP
    if (strpos($line, '#KODIPROP:') === 0) {
        $currentEntry[] = $line;
        continue;
    }

    // Check if line is #EXTVLCOPT
    if (strpos($line, '#EXTVLCOPT:') === 0) {
        $currentEntry[] = $line;
        continue;
    }

    // Check if line is #EXTHTTP with cookie
    if (strpos($line, '#EXTHTTP:') === 0) {
        // Extract cookie value from JSON
        preg_match('/\{"cookie":"([^"]+)"\}/', $line, $matches);
        if (isset($matches[1])) {
            $cookieValue = $matches[1];
        }
        // Don't add this line to output, we'll append cookie to URL instead
        continue;
    }

    // Check if line is a URL (MPD stream URL)
    if (preg_match('/^https?:\/\//', $line)) {
        // Add cookie to URL if we have one
        if (!empty($cookieValue)) {
            $line = $line . '?||cookie=' . $cookieValue;
        }
        $currentEntry[] = $line;

        // Output the complete entry
        foreach ($currentEntry as $entryLine) {
            $output[] = $entryLine;
        }
        $output[] = ''; // Add blank line between entries

        // Reset for next entry
        $currentEntry = [];
        $cookieValue = '';
        continue;
    }

    // Any other line
    if (!empty($line)) {
        $currentEntry[] = $line;
    }
}

// Output the converted playlist
echo implode("\n", $output);
?>
