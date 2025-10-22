<?php
$u='https://m3u-fetcher.vercel.app/api/airtel';
$ch=curl_init($u);
curl_setopt_array($ch,[
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_FOLLOWLOCATION=>true,
  CURLOPT_CONNECTTIMEOUT=>10,
  CURLOPT_TIMEOUT=>25,
  CURLOPT_USERAGENT=>'Mozilla/5.0 (Vercel-PHP-Debug)',
  CURLOPT_SSL_VERIFYPEER=>false,
  CURLOPT_SSL_VERIFYHOST=>false,
]);
$body=curl_exec($ch);
$err=curl_error($ch);
$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);
header('Content-Type: text/plain; charset=UTF-8');
echo "CODE=$code\nERR=$err\nLEN=".strlen($body??'0')."\n---\n";
echo substr($body??'',0,500);
