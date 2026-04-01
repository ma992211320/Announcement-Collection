<?php
class DataProcessor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getProjects($keyword = '', $limit = 100) {
        $sql = "SELECT 
            p.id, 
            p.project_name, 
            p.project_number, 
            p.region, 
            p.total_investment, 
            p.bid_deadline, 
            MAX(pa.publish_time) as latest_publish_time
        FROM projects p
        LEFT JOIN project_announcements pa ON p.id = pa.project_id
        WHERE 1=1";
        
        $params = [];
        
        if (!empty($keyword)) {
            $sql .= " AND (p.project_name LIKE ? OR p.project_number LIKE ? OR p.region LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        
        $sql .= " GROUP BY p.id
        ORDER BY latest_publish_time DESC
        LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function getProjectAnnouncements($projectId) {
        $sql = "SELECT 
            pa.id, 
            pa.announcement_title, 
            pa.publish_time, 
            pa.url, 
            at.type_name as announcement_type
        FROM project_announcements pa
        JOIN announcement_types at ON pa.announcement_type_id = at.id
        WHERE pa.project_id = ?
        ORDER BY pa.publish_time DESC";
        
        $stmt = $this->db->query($sql, [$projectId]);
        return $stmt->fetchAll();
    }
    
    public function cleanDuplicateProjects() {
        // 查找重复项目
        $sql = "SELECT 
            MIN(id) as keep_id, 
            project_name, 
            project_number
        FROM projects
        WHERE project_number != ''
        GROUP BY project_name, project_number
        HAVING COUNT(*) > 1";
        
        $stmt = $this->db->query($sql);
        $duplicates = $stmt->fetchAll();
        
        foreach ($duplicates as $duplicate) {
            $keepId = $duplicate['keep_id'];
            $projectName = $duplicate['project_name'];
            $projectNumber = $duplicate['project_number'];
            
            // 查找需要删除的项目ID
            $deleteSql = "SELECT id FROM projects WHERE project_name = ? AND project_number = ? AND id != ?";
            $deleteStmt = $this->db->query($deleteSql, [$projectName, $projectNumber, $keepId]);
            $deleteIds = $deleteStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($deleteIds)) {
                // 更新关联的公告记录
                foreach ($deleteIds as $deleteId) {
                    $updateSql = "UPDATE project_announcements SET project_id = ? WHERE project_id = ?";
                    $this->db->query($updateSql, [$keepId, $deleteId]);
                    
                    // 删除重复项目
                    $deleteProjectSql = "DELETE FROM projects WHERE id = ?";
                    $this->db->query($deleteProjectSql, [$deleteId]);
                }
            }
        }
        
        return count($duplicates);
    }
    
    public function exportToCsv($filename, $keyword = '') {
        $projects = $this->getProjects($keyword, 1000);
        
        $fp = fopen($filename, 'w');
        
        // 写入表头
        fputcsv($fp, [
            '项目名称', '项目编号', '区域', '总投资额', '竞包截止时间', '最新公告时间'
        ]);
        
        // 写入数据
        foreach ($projects as $project) {
            fputcsv($fp, [
                $project['project_name'],
                $project['project_number'],
                $project['region'],
                $project['total_investment'],
                $project['bid_deadline'] ? date('Y-m-d H:i:s', strtotime($project['bid_deadline'])) : '',
                $project['latest_publish_time']
            ]);
        }
        
        fclose($fp);
        return true;
    }
}
?>