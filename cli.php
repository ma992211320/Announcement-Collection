#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Crawler.php';
require_once __DIR__ . '/DataProcessor.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

$options = getopt('t:k:e:c:h', ['type:', 'keyword:', 'export:', 'clean:', 'help']);

if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}

if (isset($options['c']) || isset($options['clean'])) {
    cleanDuplicates();
    exit(0);
}

if (isset($options['e']) || isset($options['export'])) {
    $filename = isset($options['e']) ? $options['e'] : $options['export'];
    $keyword = isset($options['k']) ? $options['k'] : (isset($options['keyword']) ? $options['keyword'] : '');
    exportCsv($filename, $keyword);
    exit(0);
}

if (isset($options['t']) || isset($options['type'])) {
    $type = isset($options['t']) ? $options['t'] : $options['type'];
    $keyword = isset($options['k']) ? $options['k'] : (isset($options['keyword']) ? $options['keyword'] : '');
    crawl($type, $keyword);
    exit(0);
}

showHelp();

exit(1);

function crawl($type, $keyword) {
    $validTypes = array_keys(ANNOUNCEMENT_TYPES);
    if (!in_array($type, $validTypes)) {
        echo "无效的公告类型。有效类型：" . implode(', ', $validTypes) . "\n";
        exit(1);
    }
    
    echo "开始抓取 {$type}，关键词：{$keyword}\n";
    
    try {
        $crawler = new Crawler();
        $crawler->crawl($type, $keyword);
        echo "抓取完成！\n";
    } catch (Exception $e) {
        echo "抓取失败：" . $e->getMessage() . "\n";
        exit(1);
    }
}

function exportCsv($filename, $keyword) {
    echo "导出数据到 {$filename}，关键词：{$keyword}\n";
    
    try {
        $processor = new DataProcessor();
        $processor->exportToCsv($filename, $keyword);
        echo "导出完成！\n";
    } catch (Exception $e) {
        echo "导出失败：" . $e->getMessage() . "\n";
        exit(1);
    }
}

function cleanDuplicates() {
    echo "开始清理重复项目\n";
    
    try {
        $processor = new DataProcessor();
        $count = $processor->cleanDuplicateProjects();
        echo "清理完成，共处理 {$count} 组重复项目\n";
    } catch (Exception $e) {
        echo "清理失败：" . $e->getMessage() . "\n";
        exit(1);
    }
}

function showHelp() {
    echo "使用方法：php cli.php [选项]\n";
    echo "\n";
    echo "选项：\n";
    echo "  -t, --type      公告类型（必填，用于抓取）\n";
    echo "  -k, --keyword   搜索关键词（可选）\n";
    echo "  -e, --export    导出CSV文件名（用于导出数据）\n";
    echo "  -c, --clean     清理重复项目\n";
    echo "  -h, --help      显示帮助信息\n";
    echo "\n";
    echo "示例：\n";
    echo "  抓取招标公告：php cli.php -t 招标公告\n";
    echo "  按关键词抓取：php cli.php -t 招标公告 -k 服务\n";
    echo "  导出数据：php cli.php -e output.csv\n";
    echo "  清理重复项目：php cli.php -c\n";
}
?>