<?php
// 数据库配置（宝塔面板正确版）
const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_USER = '127_0_0_4';
const DB_PASSWORD = 'ttreaTSGC3';
const DB_NAME = '127_0_0_4';

// 爬虫配置
const CRAWLER_BASE_URL = 'https://www.hzlscgfw.cn';
const CRAWLER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
const CRAWLER_TIMEOUT = 30;
const CRAWLER_MAX_RETRIES = 3;

// 公告类型配置（服务类）
const ANNOUNCEMENT_TYPES = [
    '招标公告' => '001001003001',
    '变更公告' => '001001003002',
    '中标候选人公示' => '001001003003',
    '中标公告' => '001001003004',
    '异常公告' => '001001003005',
    '开标结果公示' => '001001003006',
    '答疑澄清' => '001001003007'
];

// 公告列表页面路径
const ANNOUNCEMENT_LIST_PATH = '/jyxx/001001/001001003/%s/sec.html';

// 日志配置
const LOG_FILE = __DIR__ . '/crawler.log';
?>