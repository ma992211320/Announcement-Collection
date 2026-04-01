-- 创建数据库
CREATE DATABASE IF NOT EXISTS `127_0_0_4` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `127_0_0_4`;

-- 公告类型表
CREATE TABLE IF NOT EXISTS announcement_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL COMMENT '公告类型名称',
    type_code VARCHAR(20) NOT NULL COMMENT '公告类型编码',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 项目表
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(191) NOT NULL COMMENT '项目名称',
    project_number VARCHAR(50) COMMENT '项目编号',
    region VARCHAR(100) COMMENT '区域',
    total_investment DECIMAL(18,2) COMMENT '总投资额',
    bid_deadline DATETIME COMMENT '竞包截止时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_project (project_name, project_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 项目公告表
CREATE TABLE IF NOT EXISTS project_announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL COMMENT '项目ID',
    announcement_type_id INT NOT NULL COMMENT '公告类型ID',
    announcement_title VARCHAR(255) NOT NULL COMMENT '公告标题',
    publish_time DATETIME NOT NULL COMMENT '发布时间',
    url VARCHAR(500) NOT NULL COMMENT '公告链接',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_announcement (project_id, announcement_type_id, publish_time),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (announcement_type_id) REFERENCES announcement_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入公告类型数据
INSERT INTO announcement_types (type_name, type_code) VALUES
('招标公告', '001'),
('变更公告', '002'),
('中标候选人公示', '003'),
('中标公告', '004'),
('异常公告', '005'),
('开标结果公示', '006'),
('答疑澄清', '007');