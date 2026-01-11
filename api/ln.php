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
        'url' => 'https://jcevents.hotstar.com/bpk-tv/f0e3e64ae415771d8e460317ce97aa5e/Fallback/index2.m3u8||cookie=hdntl=exp=1768201558~acl=/*~id=190ac98179344a21461fd22ea60e6d5e~data=hdntl~hmac=2c6d75d058b3f4e6ffcb946a80fac03d593aad710f0439c33c5038b9eaa1294f|||http-origin=https://www.hotstar.com|||http-referer=https://www.hotstar.com/||http-user-agent=Hotstar;in.startv.hotstar/SportsLover(Android/15)',
        'props' => [
            '#KODIPROP:inputstream.adaptive.license_type=clearkey'
        ]
    ],
    [
        'title' => 'English',
        'logo' => 'g',
        'url' => 'https://otte.live.cf.ww.aiv-cdn.net/iad-nitro/live/clients/dash/enc/njsiktakqy/out/v1/b25ec77dd6d547d68df6d07f165896ff/cenc.mpd',
        'props' => [
            '#KODIPROP:inputstream.adaptive.license_type=clearkey',
            '#KODIPROP:inputstream.adaptive.license_key=https://vercel-php-clearkey-hex-base64-json.vercel.app/api/results.php?keyid=dd94299841023377b7302276118a971b&key=b0e803fe7d4c136d0ba8eac091a032b7'
        ]
    ],
    [
        'title' => 'English',
        'logo' => 'g',
        'url' => 'https://livetv-push.hotstar.com/dash/live/2002465/sshd2livetvwv/master.mpd?||cookie=hdntl=exp=1768200358~acl=*sshd2livetv*~id=62e0ace2c24927037bb2ff08f26644a0~data=hdntl~hmac=6a0ba1905630f9d104afb588124d1b6b99318723bbca9198d0ec58d0191cdfc2|||http-origin=https://www.hotstar.com|||http-referer=https://www.hotstar.com/||http-user-agent=Hotstar;in.startv.hotstar/SportsLover(Android/15)',
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
    
