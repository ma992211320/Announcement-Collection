#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';

try {
    // 连接到MySQL服务器
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    
    // 读取数据库SQL文件
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // 执行SQL语句
    $pdo->exec($sql);
    
    echo "数据库初始化成功！\n";
} catch (Exception $e) {
    echo "数据库初始化失败：" . $e->getMessage() . "\n";
    exit(1);
}
?>