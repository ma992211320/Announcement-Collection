<?php
header('Content-Type: application/json');

// 环境检测
if (isset($_GET['action']) && $_GET['action'] === 'check_env') {
    $checks = [];
    
    // 检查PHP版本
    $phpVersion = phpversion();
    $checks[] = [
        'name' => 'PHP版本',
        'passed' => version_compare($phpVersion, '7.0.0', '>='),
        'message' => $phpVersion . (version_compare($phpVersion, '7.0.0', '>=') ? ' (满足要求)' : ' (需要PHP 7.0.0或更高版本)')
    ];
    
    // 检查PDO扩展
    $pdoEnabled = extension_loaded('pdo');
    $checks[] = [
        'name' => 'PDO扩展',
        'passed' => $pdoEnabled,
        'message' => $pdoEnabled ? '已启用' : '未启用'
    ];
    
    // 检查MySQL PDO扩展
    $pdoMysqlEnabled = extension_loaded('pdo_mysql');
    $checks[] = [
        'name' => 'PDO MySQL扩展',
        'passed' => $pdoMysqlEnabled,
        'message' => $pdoMysqlEnabled ? '已启用' : '未启用'
    ];
    
    // 检查cURL扩展
    $curlEnabled = extension_loaded('curl');
    $checks[] = [
        'name' => 'cURL扩展',
        'passed' => $curlEnabled,
        'message' => $curlEnabled ? '已启用' : '未启用'
    ];
    
    // 检查DOM扩展
    $domEnabled = extension_loaded('dom');
    $checks[] = [
        'name' => 'DOM扩展',
        'passed' => $domEnabled,
        'message' => $domEnabled ? '已启用' : '未启用'
    ];
    
    // 检查文件权限
    $configWritable = is_writable(__DIR__ . '/config.php');
    $checks[] = [
        'name' => 'config.php文件权限',
        'passed' => $configWritable,
        'message' => $configWritable ? '可写' : '不可写'
    ];
    
    // 检查日志文件权限
    $logDirWritable = is_writable(__DIR__);
    $checks[] = [
        'name' => '日志文件权限',
        'passed' => $logDirWritable,
        'message' => $logDirWritable ? '可写' : '不可写'
    ];
    
    echo json_encode(['checks' => $checks]);
    exit;
}

// 数据库安装
if (isset($_GET['action']) && $_GET['action'] === 'install_db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? '';
    $dbPort = $_POST['db_port'] ?? '';
    $dbName = $_POST['db_name'] ?? '';
    $dbUser = $_POST['db_user'] ?? '';
    $dbPassword = $_POST['db_password'] ?? '';
    
    if (empty($dbHost) || empty($dbPort) || empty($dbName) || empty($dbUser)) {
        echo json_encode([
            'success' => false,
            'message' => '请填写完整的数据库配置'
        ]);
        exit;
    }
    
    try {
        // 连接到MySQL服务器
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $dbUser, $dbPassword, $options);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");
        
        // 读取数据库SQL文件
        $sql = file_get_contents(__DIR__ . '/database.sql');
        
        // 执行SQL语句
        $pdo->exec($sql);
        
        // 更新config.php文件
        $configContent = file_get_contents(__DIR__ . '/config.php');
        $configContent = preg_replace('/const DB_HOST = .*?;/', "const DB_HOST = '{$dbHost}';", $configContent);
        $configContent = preg_replace('/const DB_PORT = .*?;/', "const DB_PORT = {$dbPort};", $configContent);
        $configContent = preg_replace('/const DB_USER = .*?;/', "const DB_USER = '{$dbUser}';", $configContent);
        $configContent = preg_replace('/const DB_PASSWORD = .*?;/', "const DB_PASSWORD = '{$dbPassword}';", $configContent);
        $configContent = preg_replace('/const DB_NAME = .*?;/', "const DB_NAME = '{$dbName}';", $configContent);
        
        file_put_contents(__DIR__ . '/config.php', $configContent);
        
        // 创建安装完成标志文件
        file_put_contents(__DIR__ . '/installed.lock', date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true,
            'message' => '数据库安装成功！'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '数据库安装失败: ' . $e->getMessage()
        ]);
    }
    exit;
}

// 默认响应
echo json_encode([
    'success' => false,
    'message' => '无效的操作'
]);
?>