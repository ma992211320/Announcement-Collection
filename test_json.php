#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';

// 测试JSON输出
$response = [
    'success' => true,
    'message' => '测试JSON输出'
];

echo json_encode($response);
echo "\n";
?>