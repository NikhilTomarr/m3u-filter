<?php
// Remote playlist URL
$remoteUrl = 'https://m3u-fetcher.vercel.app/api/airtel';

// Patterns to hide
$blockPatterns = [
  '/join@Billa_tv/i',
  '#https?://cdn\.videas\.fr/.*#i',
];

// Fetch with curl
function fetch_m3u($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_CONNECTTIMEOUT=>10,
    CURLOPT_TIMEOUT=>20,
    CURLOPT_USERAGENT=>'Mozilla/5.0 (Vercel-PHP)',
  ]);
  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if($body===false || $code<200 || $code>=300){
    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(502);
    echo "#EXTM3U\n# Error fetching origin: CODE=$code ERR=$err\n";
    exit;
  }
  return $body;
}
function should_drop_block($block,$blockPatterns){
  $text = implode("\n",$block);
  foreach($blockPatterns as $p){ if(preg_match($p,$text)) return true; }
  return false;
}
function filter_playlist($raw,$blockPatterns){
  $lines = preg_split("/\r\n|\r|\n/",$raw);
  $out = ['#EXTM3U']; $cur=[];
  $seen=false;
  foreach($lines as $line){
    $t = trim($line); if($t==='') continue;
    if(!$seen && stripos($t,'#EXTM3U')===0){ $seen=true; continue; }
    $cur[]=$t;
    if($t[0]!=='#'){
      if(!should_drop_block($cur,$blockPatterns)){
        foreach($cur as $cl){ $out[]=$cl; }
      }
      $cur=[];
    }
  }
  return implode("\n",$out)."\n";
}
$raw = fetch_m3u($remoteUrl);
header('Content-Type: application/vnd.apple.mpegurl; charset=UTF-8');
header('Cache-Control: no-store, must-revalidate');
echo filter_playlist($raw,$blockPatterns);
