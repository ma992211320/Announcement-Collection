#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Crawler.php';

// 测试爬虫和JSON输出
try {
    $crawler = new Crawler();
    $crawler->crawl('招标公告', '服务');
    
    $scrapedData = $crawler->getScrapedData();
    echo "采集到 " . count($scrapedData) . " 个项目\n";
    
    // 测试JSON编码
    $response = [
        'success' => true,
        'projects' => $scrapedData,
        'chartData' => [
            'labels' => ['招标公告', '变更公告', '开标结果公示'],
            'data' => [1, 0, 0]
        ]
    ];
    
    $json = json_encode($response);
    echo "JSON长度: " . strlen($json) . "\n";
    echo "JSON输出: " . $json . "\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>