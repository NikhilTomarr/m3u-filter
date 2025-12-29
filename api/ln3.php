<?php
header('Content-Type: application/x-mpegURL');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');


function fetchExternalM3U($url) {
    $options = [
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ];
    
    $context = stream_context_create($options);
    $data = @file_get_contents($url, false, $context);
    
    return $data !== false ? $data : '';
}


$liveEvents = [
    [
        'title' => '@streamstartv',
        'tvg_id' => '1998',
        'logo' => 'https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiamoffBdXQP0r6SHT9kM1ravyBjCVbUrncneORa9h4STgb_d8iEmMyKWn5hbzNnShrdNQYmCMDmbr3xFittRirO_zNiW4ic1FpEwxoVKwxSleDLlTgx9tHmKmKWRwqIyHYWgaUohCyIYKF6TMAutBebcryI8jVyoU4YmeKLPj4dU1gvxmenQ9Lg7MpyOfK/s1280/20250321_130159.png',
        'url' => 'https://fansspot.fun/promo.mp4',
        'props' => []
    ],
    [
        'title' => 'HINDI',
        'logo' => 'g',
        'url' => 'https://livetv-push.hotstar.com/dash/live/2002466/sshindiwv/master.mpd??||cookie=hdntl=exp=1766237995~acl=*sshindi*~id=a272c620b60b4d5379a1ec54e5cac97c~data=hdntl~hmac=64223c8bf63067364f35cad5a50c7b038f40bc4c9a97c8aa05a9c2cb99a575b9||http-user-agent=Hotstar;in.startv.hotstar/25.02.24.8.11169%20(Android/15)|||http-referer=https://www.hotstar.com/||||http-origin=https://www.hotstar.com',
        'props' => [
            '#KODIPROP:inputstream.adaptive.license_type=clearkey',
            '#KODIPROP:inputstream.adaptive.license_key=https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid=fe7718fbb3fb4ba78c07cc0f578744e6&key=624e24b1843b459fab0a949609416f0d'
        ]
    ],
    [
        'title' => 'English',
        'logo' => 'g',
        'url' => 'https://livetv-push.hotstar.com/dash/live/2002464/sshd1livetvwv/master.mpd?||cookie=hdntl=exp=1766238033~acl=*sshd1livetv*~id=41107ce774edae87c8865a2bdeb434d3~data=hdntl~hmac=3e2995be8ea1adab93dea440aef792f55103edd26862c544c72b754f784926ff|||http-origin=https://www.hotstar.com|||http-referer=https://www.hotstar.com/||http-user-agent=Hotstar;in.startv.hotstar/25.02.24.8.11169%20(Android/15)',
        'props' => [
            '#KODIPROP:inputstream.adaptive.license_type=clearkey',
            '#KODIPROP:inputstream.adaptive.license_key=https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid=fe7718fbb3fb4ba78c07cc0f578744e6&key=624e24b1843b459fab0a949609416f0d'
        ]
    ]
];


echo "#EXTM3U\n\n";


foreach ($liveEvents as $event) {
    // Output KODIPROP lines if present
    if (!empty($event['props'])) {
        foreach ($event['props'] as $prop) {
            echo $prop . "\n";
        }
    }
    
    
    $extinf = "#EXTINF:-1";
    if (!empty($event['tvg_id'])) $extinf .= ' tvg-id="' . $event['tvg_id'] . '"';
    if (!empty($event['logo'])) $extinf .= ' tvg-logo="' . $event['logo'] . '"';
    if (!empty($event['group'])) $extinf .= ' group-title="' . $event['group'] . '"';
    $extinf .= ', ' . $event['title'];
    
    echo $extinf . "\n";
    echo $event['url'] . "\n\n";
}


$externalData = fetchExternalM3U('https://modsdone.com/Billatv/Crichd.php');

if (!empty($externalData)) {
    // Remove duplicate #EXTM3U header from external data
    $externalData = preg_replace('/^#EXTM3U\s*/i', '', trim($externalData));
    echo $externalData;
}
?>
    
