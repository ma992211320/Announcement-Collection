<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服务类招标公告采集工具 - 安装</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 2rem;
            max-width: 800px;
        }
        .card {
            margin-bottom: 2rem;
        }
        .card-header {
            background-color: #007bff;
            color: white;
        }
        .alert {
            margin-top: 1rem;
        }
        .progress {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">服务类招标公告采集工具 - 安装</h1>
        
        <div class="card">
            <div class="card-header">
                安装向导
            </div>
            <div class="card-body">
                <div id="install-steps">
                    <div class="step" id="step-1">
                        <h5>1. 环境检测</h5>
                        <div id="env-check-result"></div>
                    </div>
                    <div class="step" id="step-2" style="display: none;">
                        <h5>2. 数据库配置</h5>
                        <form id="db-config-form">
                            <div class="mb-3">
                                <label for="db-host" class="form-label">数据库主机</label>
                                <input type="text" class="form-control" id="db-host" name="db_host" value="localhost" required>
                            </div>
                            <div class="mb-3">
                                <label for="db-port" class="form-label">数据库端口</label>
                                <input type="text" class="form-control" id="db-port" name="db_port" value="3306" required>
                            </div>
                            <div class="mb-3">
                                <label for="db-name" class="form-label">数据库名称</label>
                                <input type="text" class="form-control" id="db-name" name="db_name" value="127_0_0_4" required>
                            </div>
                            <div class="mb-3">
                                <label for="db-user" class="form-label">数据库用户名</label>
                                <input type="text" class="form-control" id="db-user" name="db_user" value="127_0_0_4" required>
                            </div>
                            <div class="mb-3">
                                <label for="db-password" class="form-label">数据库密码</label>
                                <input type="password" class="form-control" id="db-password" name="db_password" value="ttreaTSGC3" required>
                            </div>
                        </form>
                    </div>
                    <div class="step" id="step-3" style="display: none;">
                        <h5>3. 数据库安装</h5>
                        <div id="db-install-result"></div>
                    </div>
                    <div class="step" id="step-4" style="display: none;">
                        <h5>4. 安装完成</h5>
                        <div id="install-complete"></div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button id="prev-btn" class="btn btn-secondary" style="display: none;">上一步</button>
                    <button id="next-btn" class="btn btn-primary">下一步</button>
                    <button id="install-btn" class="btn btn-success" style="display: none;">开始安装</button>
                    <a href="index.php" id="finish-btn" class="btn btn-success" style="display: none;">完成</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const steps = [1, 2, 3, 4];
            let currentStep = 1;
            
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const installBtn = document.getElementById('install-btn');
            const finishBtn = document.getElementById('finish-btn');
            
            // 环境检测
            function checkEnvironment() {
                const resultDiv = document.getElementById('env-check-result');
                resultDiv.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">检测中...</span></div>';
                
                fetch('install_process.php?action=check_env')
                    .then(response => response.json())
                    .then(data => {
                        let html = '<div class="list-group">';
                        data.checks.forEach(check => {
                            const status = check.passed ? 'success' : 'danger';
                            const icon = check.passed ? '✓' : '✗';
                            html += `<div class="list-group-item list-group-item-${status}">${icon} ${check.name}: ${check.message}</div>`;
                        });
                        html += '</div>';
                        resultDiv.innerHTML = html;
                    })
                    .catch(error => {
                        resultDiv.innerHTML = `<div class="alert alert-danger">环境检测失败: ${error.message}</div>`;
                    });
            }
            
            // 数据库安装
            function installDatabase() {
                const resultDiv = document.getElementById('db-install-result');
                resultDiv.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">安装中...</span></div>';
                
                const formData = new FormData(document.getElementById('db-config-form'));
                
                fetch('install_process.php?action=install_db', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => {
                            showStep(4);
                        }, 1000);
                    } else {
                        resultDiv.innerHTML = `<div class="alert alert-danger">安装失败: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `<div class="alert alert-danger">安装失败: ${error.message}</div>`;
                });
            }
            
            // 显示指定步骤
            function showStep(step) {
                steps.forEach(s => {
                    document.getElementById(`step-${s}`).style.display = s === step ? 'block' : 'none';
                });
                
                currentStep = step;
                
                // 更新按钮状态
                prevBtn.style.display = step > 1 ? 'inline-block' : 'none';
                nextBtn.style.display = step < 3 ? 'inline-block' : 'none';
                installBtn.style.display = step === 3 ? 'inline-block' : 'none';
                finishBtn.style.display = step === 4 ? 'inline-block' : 'none';
                
                // 执行当前步骤的操作
                if (step === 1) {
                    checkEnvironment();
                }
            }
            
            // 按钮事件
            prevBtn.addEventListener('click', function() {
                if (currentStep > 1) {
                    showStep(currentStep - 1);
                }
            });
            
            nextBtn.addEventListener('click', function() {
                if (currentStep < 3) {
                    showStep(currentStep + 1);
                }
            });
            
            installBtn.addEventListener('click', function() {
                installDatabase();
            });
            
            // 开始安装
            showStep(1);
        });
    </script>
</body>
</html>