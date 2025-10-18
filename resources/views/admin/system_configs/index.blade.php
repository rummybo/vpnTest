<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统配置管理</title>
    <link rel="stylesheet" href="/assets/admin/components.chunk.css?v={{config('app.version')}}">
    <link rel="stylesheet" href="/assets/admin/umi.css?v={{config('app.version')}}">
    <link rel="stylesheet" href="/assets/admin/custom.css?v={{config('app.version')}}">
    <style>
        .config-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 20px;
        }
        .config-header {
            border-bottom: 1px solid #e8e8e8;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .config-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .config-description {
            color: #666;
            margin-top: 8px;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .error {
            color: #ff4d4f;
            background: #fff2f0;
            border: 1px solid #ffccc7;
            border-radius: 4px;
            padding: 12px;
            margin: 20px;
        }
        .config-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .config-table th,
        .config-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e8e8e8;
        }
        .config-table th {
            background: #fafafa;
            font-weight: 600;
        }
        .config-group {
            background: #f0f9ff;
            font-weight: 600;
            color: #1890ff;
        }
        .config-actions {
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
        }
        .btn-primary {
            background: #1890ff;
            color: white;
        }
        .btn-success {
            background: #52c41a;
            color: white;
        }
    </style>
</head>
<body>
    <div class="config-container">
        <div class="config-header">
            <h1 class="config-title">系统配置管理</h1>
            <p class="config-description">管理系统的各项配置参数</p>
        </div>
        
        <div class="config-actions">
            <button class="btn btn-primary" id="addConfigBtn">添加配置</button>
            <button class="btn btn-success" id="refreshCacheBtn">刷新缓存</button>
        </div>
        
        <div id="configContent">
            <div class="loading">正在加载配置数据...</div>
        </div>
    </div>

    <script>
        // 获取安全路径
        const securePath = '{{config("v2board.secure_path", config("v2board.frontend_admin_path", ""))}}';
        const apiBase = `/api/v1/${securePath}`;

        // 加载配置数据
        async function loadConfigs() {
            try {
                const response = await fetch(`${apiBase}/system_configs/fetch`);
                const result = await response.json();
                
                if (response.ok) {
                    renderConfigs(result.data || []);
                } else {
                    showError('加载配置失败: ' + (result.message || '未知错误'));
                }
            } catch (error) {
                showError('网络请求失败: ' + error.message);
            }
        }

        // 渲染配置列表
        function renderConfigs(configs) {
            const content = document.getElementById('configContent');
            
            if (configs.length === 0) {
                content.innerHTML = '<div class="loading">暂无配置数据</div>';
                return;
            }

            // 按分组整理配置
            const groupedConfigs = {};
            configs.forEach(config => {
                if (!groupedConfigs[config.group]) {
                    groupedConfigs[config.group] = [];
                }
                groupedConfigs[config.group].push(config);
            });

            let html = '<table class="config-table">';
            html += '<thead><tr><th>配置键</th><th>配置名称</th><th>当前值</th><th>类型</th><th>状态</th><th>描述</th></tr></thead>';
            html += '<tbody>';

            Object.keys(groupedConfigs).forEach(group => {
                html += `<tr class="config-group"><td colspan="6">${group}</td></tr>`;
                groupedConfigs[group].forEach(config => {
                    const statusText = config.status ? '启用' : '禁用';
                    const statusClass = config.status ? 'text-success' : 'text-muted';
                    
                    html += `<tr>
                        <td><code>${config.key}</code></td>
                        <td>${config.name}</td>
                        <td>${config.display_value || config.value || '-'}</td>
                        <td>${config.type}</td>
                        <td class="${statusClass}">${statusText}</td>
                        <td>${config.description || '-'}</td>
                    </tr>`;
                });
            });

            html += '</tbody></table>';
            content.innerHTML = html;
        }

        // 显示错误信息
        function showError(message) {
            document.getElementById('configContent').innerHTML = 
                `<div class="error">错误: ${message}</div>`;
        }

        // 刷新缓存
        async function refreshCache() {
            try {
                const response = await fetch(`${apiBase}/system_configs/refresh-cache`, {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (response.ok) {
                    alert('缓存刷新成功');
                } else {
                    alert('缓存刷新失败: ' + (result.message || '未知错误'));
                }
            } catch (error) {
                alert('网络请求失败: ' + error.message);
            }
        }

        // 事件监听
        document.getElementById('refreshCacheBtn').addEventListener('click', refreshCache);
        document.getElementById('addConfigBtn').addEventListener('click', function() {
            alert('添加配置功能开发中...');
        });

        // 页面加载完成后获取数据
        document.addEventListener('DOMContentLoaded', loadConfigs);
    </script>
</body>
</html>