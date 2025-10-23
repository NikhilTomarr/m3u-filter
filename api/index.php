<?php
// ---- CONFIG ----
$inputUrl = 'https://raw.githubusercontent.com/USER/REPO/BRANCH/path/to/source.m3u'; // aapka current input URL
$clearKeyBaseUrl = 'https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php';

// ---- Helper: fetch source (no-cache) ----
function http_get_nocache($url, $timeout = 12) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_HTTPHEADER => [
      'Cache-Control: no-cache, no-store, max-age=0, must-revalidate',
      'Pragma: no-cache'
    ],
    CURLOPT_USERAGENT => 'curl/8.x'
  ]);
  $body = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($body === false || $code >= 400) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "#EXTM3U\n# Error fetching source ($code)\n";
    exit;
  }
  return $body;
}

// ---- DIRECT OUTPUT MODE ----
// 1) Fetch source fresh
$src = http_get_nocache($inputUrl);

// 2) Convert using your existing function
// NOTE: convertM3UString must be present below (as in your file)
$converted = convertM3UString($src, $clearKeyBaseUrl);

// 3) Output to client with M3U headers (no file writes)
header('Content-Type: application/x-mpegurl');
header('Content-Disposition: inline; filename="artl.m3u"');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo $converted;
exit;

// M3U conversion function
function convertM3UString($inputContent, $clearKeyBaseUrl) {
    $lines = explode("\n", $inputContent);
    $output = [];
    $currentChannel = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) {
            continue;
        }
        
        if (strpos($line, '#EXTM3U') === 0) {
            $output[] = $line;
        } elseif (strpos($line, '# Total Channels:') === 0 || strpos($line, '# Expires:') === 0) {
            $output[] = $line;
        } elseif (strpos($line, '#KODIPROP:inputstream.adaptive.license_key=') === 0) {
            $licenseKey = str_replace('#KODIPROP:inputstream.adaptive.license_key=', '', $line);
            $parts = explode(':', $licenseKey);
            
            if (count($parts) >= 2) {
                $keyid = $parts[0];
                $key = $parts[1];
                
                $output[] = '#KODIPROP:inputstream.adaptive.license_type=clearkey';
                $newLicenseKey = $clearKeyBaseUrl . '?keyid=' . $keyid . '&key=' . $key;
                $output[] = '#KODIPROP:inputstream.adaptive.license_key=' . $newLicenseKey;
            }
        } elseif (strpos($line, '#KODIPROP:inputstream.adaptive.license_type=') === 0) {
            continue;
        } elseif (strpos($line, '#EXTVLCOPT:') === 0) {
            $currentChannel['vlcopt'] = $line;
        } elseif (strpos($line, '#EXTINF:') === 0) {
            $currentChannel['extinf'] = $line;
        } elseif (strpos($line, '#EXTHTTP:') === 0) {
            $currentChannel['exthttp'] = $line;
        } elseif (strpos($line, 'http') === 0) {
            $streamUrl = $line;
            
            if (isset($currentChannel['exthttp'])) {
                $cookieMatch = [];
                if (preg_match('/"cookie":"([^"]*)"/', $currentChannel['exthttp'], $cookieMatch)) {
                    $cookieValue = $cookieMatch[1];
                    $streamUrl = preg_replace('/\?\%7C.*/', '', $streamUrl);
                    $streamUrl .= '||cookie=' . $cookieValue;
                }
            }
            
            if (isset($currentChannel['vlcopt'])) {
                $output[] = $currentChannel['vlcopt'];
            }
            
            if (isset($currentChannel['extinf'])) {
                $output[] = $currentChannel['extinf'];
            }
            
            $output[] = $streamUrl;
            $currentChannel = [];
        } else {
            $output[] = $line;
        }
    }
    
    return implode("\n", $output);
}

// Check if update is needed (only check every 30 minutes to avoid overloading)
$cacheData = [];
if (file_exists($cacheFile)) {
    $cacheData = json_decode(file_get_contents($cacheFile), true) ?: [];
}

$lastCheck = $cacheData['last_check'] ?? 0;
$now = time();

// Only check for updates every 30 minutes
if (($now - $lastCheck) > 1800) {
    $result = smartUpdateM3U($inputUrl, $outputFile, $cacheFile, $clearKeyBaseUrl);
    
    // Update last check time
    $cacheData['last_check'] = $now;
    file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
}

// Serve the M3U file
if (file_exists($outputFile)) {
    header('Content-Type: application/x-mpegurl');
    header('Content-Disposition: inline; filename="artl.m3u"');
    header('Cache-Control: no-cache, must-revalidate');
    readfile($outputFile);
} else {
    http_response_code(404);
    echo "M3U file not found";
}

?>
