<?php
require_once __DIR__ . '/vendor/autoload.php';

class Crawler {
    private $db;
    private $client;
    private $scrapedData = [];
    
    public function __construct() {
        // 尝试连接数据库，但失败时不阻止爬虫执行
        try {
            require_once __DIR__ . '/Database.php';
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            $this->db = null;
            $this->log("数据库连接失败，将只执行爬虫采集：" . $e->getMessage());
        }
        
        $this->client = new GuzzleHttp\Client([
            'base_uri' => CRAWLER_BASE_URL,
            'timeout' => CRAWLER_TIMEOUT,
            'headers' => [
                'User-Agent' => CRAWLER_USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'zh-CN,zh;q=0.9',
            ],
        ]);
    }
    
    public function crawl($announcementType, $keyword = '') {
        $typeCode = ANNOUNCEMENT_TYPES[$announcementType] ?? '';
        if (empty($typeCode)) {
            throw new Exception("无效的公告类型");
        }
        
        // 清空之前的采集数据
        $this->scrapedData = [];
        
        $page = 1;
        $hasMore = true;
        
        while ($hasMore) {
            $url = sprintf(ANNOUNCEMENT_LIST_PATH, $typeCode);
            $url .= "?pageNum={$page}";
            if (!empty($keyword)) {
                $url .= "&title={$keyword}";
            }
            
            try {
                $this->log("开始抓取页面: {$url}");
                $response = $this->client->request('GET', $url);
                $html = (string) $response->getBody();
                
                $this->log("抓取页面成功，开始解析");
                $hasMore = $this->parseListPage($html, $announcementType, $keyword);
                $page++;
                
                $this->log("当前采集到的数据量: " . count($this->scrapedData));
                
                // 避免请求过快，添加延迟
                sleep(2);
            } catch (Exception $e) {
                $this->log("抓取页面失败: {$url}, 错误: {$e->getMessage()}");
                break;
            }
        }
        
        $this->log("采集完成，总共采集到 " . count($this->scrapedData) . " 条数据");
    }
    
    private function parseListPage($html, $announcementType, $keyword = '') {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // 提取公告链接
        $links = $xpath->query('//a[contains(@href, "/2026")]');
        $this->log("找到 " . $links->length . " 个链接");
        
        if ($links->length === 0) {
            return false;
        }
        
        foreach ($links as $linkNode) {
            try {
                $href = $linkNode->getAttribute('href');
                if (strpos($href, 'jyxx') === false) {
                    continue;
                }
                
                $title = trim($linkNode->nodeValue);
                
                // 关键词过滤
                if (!empty($keyword)) {
                    if (stripos($title, $keyword) === false) {
                        $this->log("跳过不匹配关键词的项目: {$title}");
                        continue;
                    }
                    $this->log("找到匹配关键词的项目: {$title}");
                }
                
                $url = CRAWLER_BASE_URL . $href;
                
                // 提取发布时间
                $publishTime = '';
                $parent = $linkNode->parentNode;
                if ($parent) {
                    $timeNode = $xpath->query('.//span', $parent)->item(0);
                    if ($timeNode) {
                        $publishTime = trim($timeNode->nodeValue);
                        $publishTime = date('Y-m-d H:i:s', strtotime($publishTime));
                    } else {
                        // 从URL中提取时间
                        preg_match('/\/2026(\d{2})(\d{2})\//', $href, $timeMatches);
                        if (isset($timeMatches[1]) && isset($timeMatches[2])) {
                            $publishTime = "2026-{$timeMatches[1]}-{$timeMatches[2]} 00:00:00";
                        }
                    }
                }
                if (empty($publishTime)) {
                    $publishTime = date('Y-m-d H:i:s');
                }
                
                // 提取区域信息
                preg_match('/\[(.*?)\]/', $title, $regionMatches);
                $region = isset($regionMatches[1]) ? $regionMatches[1] : '';
                
                // 提取项目名称（去除区域和公告类型）
                $projectName = $title;
                $projectName = preg_replace('/\[.*?\]/', '', $projectName);
                $projectName = str_replace($announcementType, '', $projectName);
                $projectName = trim($projectName);
                
                // 抓取详情页获取更多信息
                $detailInfo = $this->parseDetailPage($url);
                
                // 保存到scrapedData数组
                $projectData = [
                    'id' => count($this->scrapedData) + 1,
                    'project_name' => $projectName,
                    'project_number' => $detailInfo['project_number'],
                    'region' => $region,
                    'total_investment' => $detailInfo['total_investment'],
                    'bid_deadline' => $detailInfo['bid_deadline'],
                    'latest_publish_time' => $publishTime,
                    'announcements' => [
                        [
                            'id' => count($this->scrapedData) + 1,
                            'announcement_title' => $title,
                            'publish_time' => $publishTime,
                            'url' => $url,
                            'announcement_type' => $announcementType
                        ]
                    ]
                ];
                $this->scrapedData[] = $projectData;
                $this->log("添加项目: {$projectName}");
                
                // 保存到数据库（如果数据库连接成功）
                if ($this->db) {
                    $this->saveProject($projectName, $region, $publishTime, $url, $announcementType, $detailInfo);
                }
            } catch (Exception $e) {
                $this->log("解析公告项失败: {$e->getMessage()}");
                continue;
            }
        }
        
        // 检查是否有下一页
        $nextPage = $xpath->query('//a[contains(text(), "下一页")]')->item(0);
        $hasNextPage = $nextPage !== null;
        $this->log("是否有下一页: " . ($hasNextPage ? "是" : "否"));
        return $hasNextPage;
    }
    
    private function parseDetailPage($url) {
        try {
            $response = $this->client->request('GET', $url);
            $html = (string) $response->getBody();
            
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $detailInfo = [
                'project_number' => '',
                'total_investment' => 0,
                'bid_deadline' => null,
            ];
            
            // 提取项目编号
            $nodes = $xpath->query('//div[@class="ewb-article"]//p[contains(text(), "项目编号")]');
            if ($nodes->length > 0) {
                $text = $nodes->item(0)->nodeValue;
                preg_match('/项目编号：(.*?)/', $text, $matches);
                if (isset($matches[1])) {
                    $detailInfo['project_number'] = trim($matches[1]);
                }
            }
            
            // 提取总投资额
            $nodes = $xpath->query('//div[@class="ewb-article"]//p[contains(text(), "总投资") or contains(text(), "投资额")]');
            if ($nodes->length > 0) {
                $text = $nodes->item(0)->nodeValue;
                preg_match('/[0-9,.]+/', $text, $matches);
                if (isset($matches[0])) {
                    $amount = str_replace(',', '', $matches[0]);
                    $detailInfo['total_investment'] = (float) $amount;
                }
            }
            
            // 提取竞包截止时间
            $nodes = $xpath->query('//div[@class="ewb-article"]//p[contains(text(), "截止时间") or contains(text(), "竞包截止")]');
            if ($nodes->length > 0) {
                $text = $nodes->item(0)->nodeValue;
                preg_match('/\d{4}-\d{2}-\d{2}(\s+\d{2}:\d{2})?/', $text, $matches);
                if (isset($matches[0])) {
                    $date = $matches[0];
                    if (strlen($date) == 10) {
                        $date .= ' 17:00:00';
                    } else {
                        $date .= ':00';
                    }
                    $detailInfo['bid_deadline'] = $date;
                }
            }
            
            return $detailInfo;
        } catch (Exception $e) {
            $this->log("抓取详情页失败: {$url}, 错误: {$e->getMessage()}");
            return [
                'project_number' => '',
                'total_investment' => 0,
                'bid_deadline' => null,
            ];
        }
    }
    
    private function saveProject($projectName, $region, $publishTime, $url, $announcementType, $detailInfo) {
        try {
            $db = $this->db;
            $db->beginTransaction();
            
            // 查找或创建项目
            $stmt = $db->query(
                "SELECT id FROM projects WHERE project_name = ? AND project_number = ?",
                [$projectName, $detailInfo['project_number']]
            );
            $project = $stmt->fetch();
            
            if (!$project) {
                $stmt = $db->query(
                    "INSERT INTO projects (project_name, project_number, region, total_investment, bid_deadline) VALUES (?, ?, ?, ?, ?)",
                    [$projectName, $detailInfo['project_number'], $region, $detailInfo['total_investment'], $detailInfo['bid_deadline']]
                );
                $projectId = $db->lastInsertId();
            } else {
                $projectId = $project['id'];
                // 更新项目信息
                $db->query(
                    "UPDATE projects SET region = ?, total_investment = ?, bid_deadline = ? WHERE id = ?",
                    [$region, $detailInfo['total_investment'], $detailInfo['bid_deadline'], $projectId]
                );
            }
            
            // 查找公告类型ID
            $stmt = $db->query(
                "SELECT id FROM announcement_types WHERE type_name = ?",
                [$announcementType]
            );
            $announcementType = $stmt->fetch();
            if (!$announcementType) {
                throw new Exception("公告类型不存在: {$announcementType}");
            }
            $announcementTypeId = $announcementType['id'];
            
            // 检查公告是否已存在
            $stmt = $db->query(
                "SELECT id FROM project_announcements WHERE project_id = ? AND announcement_type_id = ? AND publish_time = ?",
                [$projectId, $announcementTypeId, $publishTime]
            );
            $existing = $stmt->fetch();
            
            if (!$existing) {
                $db->query(
                    "INSERT INTO project_announcements (project_id, announcement_type_id, announcement_title, publish_time, url) VALUES (?, ?, ?, ?, ?)",
                    [$projectId, $announcementTypeId, $projectName . ' ' . $announcementType, $publishTime, $url]
                );
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $this->log("保存项目失败: {$e->getMessage()}");
        }
    }
    
    private function log($message) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message . "\n";
        file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
    }
    
    public function getScrapedData() {
        return $this->scrapedData;
    }
}
?>