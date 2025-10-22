<?php
// Source M3U URL
$source = "https://raw.githubusercontent.com/alex8875/m3u/refs/heads/main/artl.m3u";

// Fetch the remote M3U content
$m3u = file_get_contents($source);
if (!$m3u) {
    die("Failed to load M3U file.");
}

// Split lines
$lines = explode("\n", $m3u);
$output = "#EXTM3U\n";

// Temporary store variables
$keyid = "";
$key = "";
$cookie = "";
$ua = "";
$info = "";
$stream = "";

foreach ($lines as $line) {
    $line = trim($line);

    // Match clearkey line
    if (strpos($line, "#KODIPROP:inputstream.adaptive.license_key=") !== false) {
        $data = explode("=", $line, 2)[1];
        list($keyid, $key) = explode(":", $data);
        continue;
    }

    // Match cookie line
    if (strpos($line, "#EXTHTTP:") !== false) {
        preg_match('/"cookie":"([^"]+)"/', $line, $match);
        $cookie = isset($match[1]) ? $match[1] : "";
        continue;
    }

    // Match user-agent line
    if (strpos($line, "#EXTVLCOPT:http-user-agent=") !== false) {
        $ua = str_replace("#EXTVLCOPT:http-user-agent=", "", $line);
        continue;
    }

    // Match EXTINF line
    if (strpos($line, "#EXTINF:") === 0) {
        $info = $line;
        continue;
    }

    // Match stream URL (ending with .mpd)
    if (preg_match('/^https?:\/\/.*\.mpd$/', $line)) {
        $stream = $line;

        // Append formatted output
        $output .= "#KODIPROP:inputstream.adaptive.license_type=clearkey\n";
        $output .= "#KODIPROP:inputstream.adaptive.license_key=https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid=$keyid&key=$key\n";
        $output .= "#EXTVLCOPT:http-user-agent={$ua}\n";
        $output .= "{$info}\n";
        $output .= "{$stream}||cookie={$cookie}\n\n";

        // Reset for next channel
        $keyid = $key = $cookie = $ua = $info = $stream = "";
    }
}

// Output processed M3U playlist
header("Content-Type: audio/x-mpegurl");
echo $output;
?>
