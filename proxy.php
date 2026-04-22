<?php
header('Access-Control-Allow-Origin: *');

// 模式1：用 batchexecute 解碼 Google News 真實連結

if (isset($_GET['resolve'])) {
    header('Content-Type: application/json');
    
    $url = $_GET['url'] ?? '';
    preg_match('/\/articles\/([^?\/]+)/', $url, $m);
    $id = $m[1] ?? '';
    
    $template = <<<'PAYLOAD'
[[["Fbv4je","[\"garturlreq\",[[\"en-US\",\"US\",[\"FINANCE_TOP_INDICES\",\"WEB_TEST_1_0_0\"],null,null,1,1,\"US:en\",null,180,null,null,null,null,null,0,null,null,[1608992183,723341000]],\"en-US\",\"US\",1,[2,3,4,8],1,0,\"655000234\",0,0,null,0],\"ID_PLACEHOLDER\"]",null,"generic"]]]
PAYLOAD;

    $payload = str_replace('ID_PLACEHOLDER', $id, $template);
    $postData = 'f.req=' . urlencode($payload);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://news.google.com/_/DotsSplashUi/data/batchexecute?rpcids=Fbv4je',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
            'Referer: https://news.google.com/',
        ],
    ]);
    $text = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    die(json_encode([
        'http_code' => $code,
        'curl_error' => $err,
        'text_length' => strlen($text ?: ''),
        'text_preview' => substr($text ?: '', 0, 500)
    ]));
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
