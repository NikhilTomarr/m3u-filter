<?php

$inputUrl = 'https://raw.githubusercontent.com/alex8875/m3u/refs/heads/main/artl.m3u';
$outputFile = 'artl.m3u';
$cacheFile = 'artl_cache.json';
$clearKeyBaseUrl = 'https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php';

// Smart auto-update function
function smartUpdateM3U($inputUrl, $outputFile, $cacheFile, $clearKeyBaseUrl) {
    $updateNeeded = false;
    $cacheData = [];
    
    // Load cache data
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Fetch current content from GitHub
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $inputUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; M3U-Updater/1.0)');
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    if (curl_error($ch) || $httpCode !== 200) {
        curl_close($ch);
        return "Error fetching content: " . curl_error($ch);
    }
    
    $headers = substr($response, 0, $headerSize);
    $currentContent = substr($response, $headerSize);
    curl_close($ch);
    
    // Get ETag and Last-Modified for change detection
    $etag = '';
    $lastModified = '';
    
    if (preg_match('/etag:\s*"?([^"\r\n]+)"?/i', $headers, $match)) {
        $etag = trim($match[1], '"');
    }
    
    if (preg_match('/last-modified:\s*([^\r\n]+)/i', $headers, $match)) {
        $lastModified = trim($match[1]);
    }
    
    // Check if content has changed
    $currentHash = md5($currentContent);
    
    if (!isset($cacheData['hash']) || 
        $cacheData['hash'] !== $currentHash || 
        $cacheData['etag'] !== $etag ||
        (time() - $cacheData['last_update']) > 14400) { // Force update after 4 hours
        
        $updateNeeded = true;
        
        // Convert the M3U content
        $convertedContent = convertM3UString($currentContent, $clearKeyBaseUrl);
        
        // Save converted content
        file_put_contents($outputFile, $convertedContent);
        
        // Update cache
        $cacheData = [
            'hash' => $currentHash,
            'etag' => $etag,
            'last_modified' => $lastModified,
            'last_update' => time(),
            'file_size' => strlen($convertedContent)
        ];
        
        file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
        
        return "File updated successfully! New hash: $currentHash";
    }
    
    return "No update needed. File is current.";
}

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
