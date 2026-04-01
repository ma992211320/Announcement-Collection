#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Crawler.php';

// 测试爬虫
$crawler = new Crawler();

// 测试抓取服务类招标公告
$crawler->crawl('招标公告', '服务');
echo "测试完成！\n";
?>