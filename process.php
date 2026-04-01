<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

if (isset($_POST['show_example'])) {
    // 返回示例数据
    $exampleData = getExampleData();
    echo json_encode([
        'success' => true,
        'projects' => $exampleData['projects'],
        'chartData' => $exampleData['chartData']
    ]);
    exit;
}

if (isset($_POST['crawl'])) {
    $announcementType = $_POST['announcement_type'] ?? '';
    $keyword = $_POST['keyword'] ?? '';
    
    if (empty($announcementType)) {
        echo json_encode([
            'success' => false,
            'message' => '请选择公告类型'
        ]);
        exit;
    }
    
    try {
        // 尝试加载爬虫类
        require_once __DIR__ . '/Crawler.php';
        
        // 执行采集（即使数据库连接失败也执行爬虫）
        $crawler = new Crawler();
        $crawler->crawl($announcementType, $keyword);
        
        // 尝试加载数据库相关类并获取结果
        try {
            require_once __DIR__ . '/Database.php';
            require_once __DIR__ . '/DataProcessor.php';
            
            // 清理重复项目
            $processor = new DataProcessor();
            $processor->cleanDuplicateProjects();
            
            // 获取采集结果
            $projects = $processor->getProjects($keyword, 50);
            
            // 获取每个项目的公告信息
            foreach ($projects as &$project) {
                $project['announcements'] = $processor->getProjectAnnouncements($project['id']);
            }
            
            // 生成图表数据
            $chartData = generateChartData($projects);
            
            echo json_encode([
                'success' => true,
                'projects' => $projects,
                'chartData' => $chartData
            ]);
        } catch (Exception $dbException) {
                // 数据库连接失败时，返回爬虫采集的实时数据
                $scrapedData = $crawler->getScrapedData();
                if (!empty($scrapedData)) {
                    $chartData = generateChartData($scrapedData);
                    echo json_encode([
                        'success' => true,
                        'projects' => $scrapedData,
                        'chartData' => $chartData,
                        'message' => '数据库连接失败，显示实时采集数据：' . $dbException->getMessage()
                    ]);
                } else {
                    // 如果没有采集到数据，返回空数据和提示信息
                    echo json_encode([
                        'success' => true,
                        'projects' => [],
                        'chartData' => generateChartData([]),
                        'message' => '没有找到匹配关键词的项目，请尝试其他关键词' . ($keyword ? '（搜索关键词：' . $keyword . '）' : '')
                    ]);
                }
            }
    } catch (Exception $e) {
        // 爬虫执行失败时，返回空数据和提示信息
        echo json_encode([
            'success' => false,
            'projects' => [],
            'chartData' => generateChartData([]),
            'message' => '采集失败：' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);

function getExampleData() {
    $projects = [
        [
            'id' => 1,
            'project_name' => '安吉县生态资源循环利用基地一期堆场拆房垃圾处置服务采购项目',
            'project_number' => 'hzgq202603307-1（HXCG-ZX-2026-021）',
            'region' => '安吉县',
            'total_investment' => 496.49,
            'bid_deadline' => '2026-04-20 10:00:00',
            'latest_publish_time' => '2026-03-31 00:00:00',
            'announcements' => [
                [
                    'id' => 1,
                    'announcement_title' => '安吉县生态资源循环利用基地一期堆场拆房垃圾处置服务采购项目招标公告',
                    'publish_time' => '2026-03-31 00:00:00',
                    'url' => 'https://www.hzlscgfw.cn/jyxx/001001/001001003/001001003001/20260331/2d2eb391-f1aa-499e-977f-db1abe7649f0.html',
                    'announcement_type' => '招标公告'
                ]
            ]
        ],
        [
            'id' => 2,
            'project_name' => '浙江南太湖城市开发控股集团有限公司2026-2027年度工程造价咨询定点服务单位预发包项目',
            'project_number' => 'hzgq202603139',
            'region' => '南太湖新区',
            'total_investment' => 0,
            'bid_deadline' => '2026-03-30 09:30:00',
            'latest_publish_time' => '2026-03-20 00:00:00',
            'announcements' => [
                [
                    'id' => 2,
                    'announcement_title' => '浙江南太湖城市开发控股集团有限公司2026-2027年度工程造价咨询定点服务单位预发包项目招标公告',
                    'publish_time' => '2026-03-20 00:00:00',
                    'url' => 'https://www.hzlscgfw.cn/jyxx/001001/001001003/001001003001/20260320/b29033d3-815c-4044-aebd-6d25d61ba02a.html',
                    'announcement_type' => '招标公告'
                ]
            ]
        ]
    ];
    
    $chartData = [
        'labels' => ['招标公告', '变更公告', '开标结果公示', '中标候选人公示', '中标公告', '异常公告', '答疑澄清'],
        'data' => [2, 1, 2, 0, 0, 0, 0]
    ];
    
    return [
        'projects' => $projects,
        'chartData' => $chartData
    ];
}

function generateChartData($projects) {
    $typeCount = [
        '招标公告' => 0,
        '变更公告' => 0,
        '中标候选人公示' => 0,
        '中标公告' => 0,
        '异常公告' => 0,
        '开标结果公示' => 0,
        '答疑澄清' => 0
    ];
    
    foreach ($projects as $project) {
        if (isset($project['announcements'])) {
            foreach ($project['announcements'] as $announcement) {
                if (isset($typeCount[$announcement['announcement_type']])) {
                    $typeCount[$announcement['announcement_type']]++;
                }
            }
        }
    }
    
    return [
        'labels' => array_keys($typeCount),
        'data' => array_values($typeCount)
    ];
}
?>