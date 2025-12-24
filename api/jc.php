<?php
header('Content-Type: audio/x-mpegurl');
header('Content-Disposition: attachment; filename="playlist.m3u"');

// M3U playlist URL
$playlistUrl = 'https://raw.githubusercontent.com/alex8875/m3u/refs/heads/main/jcinema.m3u';

// Fetch the playlist content
function fetchPlaylist($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}

// Parse and convert M3U format
function convertM3UFormat($content) {
    $lines = explode("\n", $content);
    $output = "#EXTM3U\n";
    
    $currentEntry = [];
    $cookieValue = '';
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        
        // Skip empty lines
        if (empty($line)) continue;
        
        // Handle #EXTM3U header
        if (strpos($line, '#EXTM3U') === 0) {
            continue;
        }
        
        // Handle #EXTINF line
        if (strpos($line, '#EXTINF:') === 0) {
            $currentEntry['extinf'] = $line;
        }
        
        // Handle #EXTVLCOPT lines
        if (strpos($line, '#EXTVLCOPT:') === 0) {
            // Change user-agent
            if (strpos($line, 'http-user-agent') !== false) {
                $currentEntry['user_agent'] = '#EXTVLCOPT:http-user-agent=Hotstar;in.startv.hotstar/25.01.27.5.3788 (Android/13)';
            } elseif (strpos($line, 'http-origin') !== false) {
                $currentEntry['origin'] = $line;
            } elseif (strpos($line, 'http-referrer') !== false) {
                $currentEntry['referrer'] = $line;
            }
        }
        
        // Handle #EXTHTTP cookie
        if (strpos($line, '#EXTHTTP:') === 0) {
            // Extract cookie value from JSON
            preg_match('/"cookie":"([^"]+)"/', $line, $matches);
            if (isset($matches[1])) {
                $cookieValue = $matches[1];
            }
        }
        
        // Handle stream URL
        if (strpos($line, 'http://') === 0 || strpos($line, 'https://') === 0) {
            // Output the converted entry
            if (!empty($currentEntry['extinf'])) {
                $output .= $currentEntry['extinf'] . "\n";
            }
            if (!empty($currentEntry['user_agent'])) {
                $output .= $currentEntry['user_agent'] . "\n";
            }
            if (!empty($currentEntry['origin'])) {
                $output .= $currentEntry['origin'] . "\n";
            }
            if (!empty($currentEntry['referrer'])) {
                $output .= $currentEntry['referrer'] . "\n";
            }
            
            // Add URL with cookie appended
            if (!empty($cookieValue)) {
                $output .= $line . '||cookie=' . $cookieValue . "\n";
            } else {
                $output .= $line . "\n";
            }
            
            // Reset for next entry
            $currentEntry = [];
            $cookieValue = '';
        }
    }
    
    return $output;
}

try {
    // Fetch fresh playlist data
    $playlistContent = fetchPlaylist($playlistUrl);
    
    if ($playlistContent === false || empty($playlistContent)) {
        die("Error: Unable to fetch playlist from URL");
    }
    
    // Convert and output the playlist
    echo convertM3UFormat($playlistContent);
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
    
