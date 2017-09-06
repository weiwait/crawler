<?php

use spider\SpiderClass;

set_time_limit(300);
require_once './vendor/autoload.php';

// $result = $client->request('GET', 'www.baidu.com');
// echo $result->getStatusCode();
// var_dump($result->getHeader('content-type'));
// echo $result->getBody();

// header('Content-Type: text/html; charset=gb18030');
// header('Content-Encoding: gzip');
// header('Accept-Encoding: gzip, deflate');
// echo $document;

//$content = preg_replace('/[\s\S]*<body\s*.*>?/i', '', $content);
//$content = preg_replace('/<\/body[\s\S]*/', '', $content);
//$content = preg_replace('/<script>[\s\S]*?<\/script>/', '', $content);
//
//file_put_contents(__DIR__ . '/content.html', $content);

//echo $document;
//$hrefs = $document->find('[href]');
//print_r($hrefs[0]->attributes());
//$url = 'http://m.tmetb.com/230/230889/46344595.html';
//$url = 'http://www.biqugex.com/book_26321/11278841.html';
$url = 'http://www.bxquge.com/17_17329/584275.html';
//$url = 'http://www.bxwx9.org/b/32/32355/5185925.html';
//$url = 'http://m.biqugedu.com/16_16517/8829953.html';
$next = '#pb_next';
$select = '#nr1';
/**
 * @param $uri
 * @param $client \GuzzleHttp\Client
 * @param array|string $next
 */
//function spider($uri, $client, $next = array())
//{
//    $result = $client->request('GET', $uri)->getBody();
//    $content = mb_convert_encoding($result, 'UTF-8', 'GBK');
//    $document = new \DiDom\Document($content);
//    $text = $document->find('#nr1')[0]->text();
//    file_put_contents(__DIR__ . '/page.text', $text, FILE_APPEND);
//    if (!$next) {
//        return;
//    } else {
//        if (key($next) == 'id') {
//            $uri = $document->find('#' . $next['id'])[0]->find('[href]')[0]->attributes()['href'];
//        } elseif (key($next) == 'class') {
//            $uri = $document->find('.' . $next['id'])[0]->find('[href]')[0]->attributes()['href'];
//        } else {
//            $rui = '';
//        }
//    }
//    if ($uri) {
//        $uri = 'http://m.tmetb.com' . $uri;
//        spider($uri, $client, $next);
//    }
//}
$Spider = new SpiderClass();
//$Spider->spider($url, $select, $next);
$Spider->analyzeDocument($url);
