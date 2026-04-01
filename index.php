<?php
// 检查是否已安装
if (!file_exists(__DIR__ . '/installed.lock')) {
    header('Location: install.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服务类招标公告采集工具</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .result-section {
            margin-top: 2rem;
            display: none;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 2rem;
        }
        .card {
            margin-bottom: 1rem;
        }
        .card-header {
            background-color: #007bff;
            color: white;
        }
        .announcement-badge {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">服务类招标公告采集工具</h1>
        
        <div class="card">
            <div class="card-header">
                采集配置（服务类公告）
            </div>
            <div class="card-body">
                <form id="crawl-form" method="post" action="">
                    <div class="form-group">
                        <label for="announcement-type" class="form-label">公告类型</label>
                        <select class="form-select" id="announcement-type" name="announcement_type" required>
                            <option value="">请选择公告类型</option>
                            <option value="招标公告">招标公告</option>
                            <option value="变更公告">变更公告</option>
                            <option value="中标候选人公示">中标候选人公示</option>
                            <option value="中标公告">中标公告</option>
                            <option value="异常公告">异常公告</option>
                            <option value="开标结果公示">开标结果公示</option>
                            <option value="答疑澄清">答疑澄清</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="keyword" class="form-label">搜索关键词（可选）</label>
                        <input type="text" class="form-control" id="keyword" name="keyword" placeholder="请输入搜索关键词">
                    </div>
                    <button type="submit" class="btn btn-primary" name="crawl" value="1">开始采集</button>
                    <button type="submit" class="btn btn-secondary" name="show_example" value="1">查看示例数据</button>
                </form>
            </div>
        </div>
        
        <div class="result-section" id="result-section">
            <h2 class="mb-3">采集结果</h2>
            
            <div class="chart-container">
                <canvas id="announcementChart"></canvas>
            </div>
            
            <div id="projects-list">
                <!-- 项目列表将通过JavaScript动态生成 -->
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('crawl-form');
            const resultSection = document.getElementById('result-section');
            const projectsList = document.getElementById('projects-list');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const submitter = e.submitter;
                if (submitter) {
                    formData.append(submitter.name, submitter.value || '');
                }
                
                fetch('process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultSection.style.display = 'block';
                        renderProjects(data.projects);
                        renderChart(data.chartData);
                    } else {
                        alert('操作失败：' + data.message);
                    }
                })
                .catch(error => {
                    alert('请求失败：' + error.message);
                });
            });
            
            function renderProjects(projects) {
                projectsList.innerHTML = '';
                
                projects.forEach(project => {
                    const projectCard = document.createElement('div');
                    projectCard.className = 'card';
                    
                    const cardHeader = document.createElement('div');
                    cardHeader.className = 'card-header';
                    cardHeader.innerHTML = `
                        <h5 class="card-title">${project.project_name}</h5>
                        <div class="d-flex justify-content-between">
                            <span class="text-sm">区域：${project.region}</span>
                            <span class="text-sm">发布时间：${project.latest_publish_time}</span>
                        </div>
                    `;
                    
                    const cardBody = document.createElement('div');
                    cardBody.className = 'card-body';
                    
                    const projectInfo = document.createElement('div');
                    projectInfo.innerHTML = `
                        <p class="card-text"><strong>项目编号：</strong>${project.project_number}</p>
                        <p class="card-text"><strong>总投资额：</strong>${project.total_investment || '未提供'} 万元</p>
                        <p class="card-text"><strong>竞包截止时间：</strong>${project.bid_deadline || '未提供'}</p>
                    `;
                    
                    const announcementsList = document.createElement('div');
                    announcementsList.className = 'mt-3';
                    announcementsList.innerHTML = '<h6>公告列表：</h6>';
                    
                    if (project.announcements && project.announcements.length > 0) {
                        const ul = document.createElement('ul');
                        ul.className = 'list-group';
                        
                        project.announcements.forEach(announcement => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item';
                            li.innerHTML = `
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge bg-primary announcement-badge">${announcement.announcement_type}</span>
                                        <a href="${announcement.url}" target="_blank">${announcement.announcement_title}</a>
                                    </div>
                                    <span class="text-sm">${announcement.publish_time}</span>
                                </div>
                            `;
                            ul.appendChild(li);
                        });
                        
                        announcementsList.appendChild(ul);
                    } else {
                        announcementsList.innerHTML += '<p class="text-muted">暂无公告信息</p>';
                    }
                    
                    cardBody.appendChild(projectInfo);
                    cardBody.appendChild(announcementsList);
                    projectCard.appendChild(cardHeader);
                    projectCard.appendChild(cardBody);
                    projectsList.appendChild(projectCard);
                });
            }
            
            let announcementChart = null;
            
            function renderChart(chartData) {
                const ctx = document.getElementById('announcementChart').getContext('2d');
                
                // 如果图表已存在，先销毁
                if (announcementChart) {
                    try {
                        announcementChart.destroy();
                    } catch (e) {
                        // 忽略销毁错误
                    }
                }
                
                announcementChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: '公告数量',
                            data: chartData.data,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>