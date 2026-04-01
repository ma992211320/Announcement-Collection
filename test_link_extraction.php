#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';

// 测试链接提取
$url = "https://www.hzlscgfw.cn/jyxx/001001/001001003/001001003001/sec.html";

// 获取页面内容
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, CRAWLER_USER_AGENT);
$html = curl_exec($ch);
curl_close($ch);

// 解析HTML
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

// 提取所有链接
$links = $xpath->query('//a[contains(@href, "/2026")]');
echo "找到 " . $links->length . " 个链接\n";

// 提取链接
foreach ($links as $link) {
    $title = trim($link->nodeValue);
    $href = $link->getAttribute('href');
    if (strpos($href, 'jyxx') !== false) {
        $url = CRAWLER_BASE_URL . $href;
        echo "项目：$title\n";
        echo "链接：$url\n";
        echo "\n";
    }
}

echo "测试完成！\n";
?>