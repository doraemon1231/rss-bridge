<?php
header('Access-Control-Allow-Origin: *');

// 模式1：解析 Google News 真實連結

if (isset($_GET['resolve'])) {
    $url = $_GET['url'] ?? '';
    if (!$url) {
        echo json_encode(['error' => 'missing url']);
        exit;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_NOBODY => true,  // 只抓 headers，不抓 body（省流量）
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode([
        'url' => $finalUrl ?: $url,
        'status' => $httpCode
    ]);
    exit;
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
