<?php
header('Access-Control-Allow-Origin: *');

// 模式1：用 batchexecute 解碼 Google News 真實連結

if (isset($_GET['resolve'])) {
    header('Content-Type: application/json');
    
    $url = $_GET['url'] ?? '';
    
    // Step 1: 確認 URL 有收到
    if (!$url) {
        die(json_encode(['step' => 1, 'error' => 'no url']));
    }
    
    // Step 2: 確認 regex 能跑
    $matched = preg_match('/\/articles\/([^?\/]+)/', $url, $m);
    if (!$matched) {
        die(json_encode(['step' => 2, 'error' => 'regex failed', 'url' => $url]));
    }
    
    $id = $m[1];
    die(json_encode(['step' => 3, 'id' => $id]));
}

// 模式2：抓 Google News RSS（原有程式碼繼續往下）

$q = $_GET['q'] ?? 'FIFA World Cup 2026';
$url = 'https://news.google.com/rss/search?q=' . urlencode($q) . '&hl=en-US&gl=US&ceid=US:en';

$ctx = stream_context_create([
  'http' => [
    'header' => implode("\r\n", [
      'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
      'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language: en-US,en;q=0.5',
      'Referer: https://news.google.com/',
      'Cache-Control: no-cache',
    ]),
    'timeout' => 15,
  ]
]);

$rss = @file_get_contents($url, false, $ctx);

if ($rss === false || strpos($rss, '<rss') === false) {
  http_response_code(502);
  echo 'Error: failed to fetch RSS';
  exit;
}

header('Content-Type: application/rss+xml; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');
echo $rss;
