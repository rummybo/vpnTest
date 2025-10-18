<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <title>系统配置管理 - {{config('v2board.app_name', 'V2Board')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            margin: 0; 
            padding: 25px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333; 
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        .header { 
            background: white; 
            padding: 25px 30px; 
            border-radius: 15px; 
            margin-bottom: 25px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.1); 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            backdrop-filter: blur(10px);
        }
        .header h1 { 
            margin: 0; 
            color: #2c3e50; 
            font-size: 1.8rem;
            font-weight: 700;
        }
        .header p {
            margin: 5px 0 0 0; 
            color: #6c757d;
            font-size: 0.95rem;
        }
        .btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            margin-left: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        .btn-success { 
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%); 
            color: white; 
        }
        .btn-danger { 
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); 
            color: white; 
        }
        .btn-sm { 
            padding: 8px 16px; 
            font-size: 0.8rem;
            border-radius: 8px;
        }
        .card { 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
            margin-bottom: 25px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .card-header { 
            padding: 20px 25px; 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .card-header h3 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }
        .card-header label {
            font-weight: 600;
            color: #495057;
            margin-right: 10px;
        }
        .card-body { padding: 25px; }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .table th, .table td { 
            padding: 15px 12px; 
            text-align: center; 
            border-bottom: 1px solid #f1f3f4; 
        }
        .table th { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        .table tr:hover { 
            background: #f8f9fa; 
            transition: background-color 0.2s ease;
        }
        .table tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }
        .badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 0.75rem; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-success { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white; 
        }
        .badge-secondary { 
            background: linear-gradient(135deg, #6c757d, #adb5bd); 
            color: white; 
        }
        .badge-info { 
            background: linear-gradient(135deg, #17a2b8, #6f42c1); 
            color: white; 
        }
        .badge-warning { 
            background: linear-gradient(135deg, #ffc107, #fd7e14); 
            color: white; 
        }
        .loading { 
            text-align: center; 
            padding: 50px; 
            color: #6c757d;
        }
        .error { 
            background: linear-gradient(135deg, #ff6b6b, #ee5a52); 
            color: white; 
            padding: 20px; 
            border-radius: 12px; 
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        .empty-state { 
            text-align: center; 
            padding: 80px 20px; 
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            display: block;
        }
        .form-control { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #e9ecef; 
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }
        select.form-control { 
            margin-left: 15px; 
            max-width: 220px;
            background: white;
            cursor: pointer;
        }
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.6); 
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        .modal-dialog { 
            max-width: 700px; 
            margin: 30px auto; 
            background: white; 
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .modal-header { 
            padding: 20px 25px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .modal-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .modal-body { 
            padding: 25px; 
        }
        .modal-footer { 
            padding: 20px 25px; 
            background: #f8f9fa;
            border-top: 1px solid #dee2e6; 
            text-align: right; 
        }
        .close { 
            background: none; 
            border: none; 
            font-size: 28px; 
            cursor: pointer;
            color: white;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        .close:hover {
            opacity: 1;
        }
        .row { 
            display: flex; 
            margin: -10px; 
        }
        .col-md-6 { 
            flex: 1; 
            padding: 0 10px; 
        }
        code {
            background: #f1f3f4;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.85rem;
            color: #e83e8c;
        }
        .operation-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .operation-buttons .btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
            min-width: 80px;
        }
        .operation-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>系统配置管理</h1>
            <p style="margin: 5px 0 0 0; color: #6c757d;">管理系统的各种配置参数</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="createConfig()">
                ➕ 新增配置
            </button>
            <button type="button" class="btn btn-success" onclick="refreshCache()">
                🔄 刷新缓存
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 style="margin: 0;">配置列表</h3>
            <div>
                <label>分组筛选:</label>
                <select id="groupFilter" class="form-control" onchange="filterByGroup()">
                    <option value="">所有分组</option>
                    <option value="general">通用配置</option>
                    <option value="frontend" selected>前端配置</option>
                    <option value="system">系统配置</option>
                    <option value="payment">支付配置</option>
                    <option value="email">邮件配置</option>
                    <option value="sms">短信配置</option>
                </select>
            </div>
        </div>
        <div class="card-body">
    <div class="content-header">
        <div class="content-header-left">
            <h2 class="content-title">系统配置管理</h2>
            <p class="content-description">管理系统的各种配置参数</p>
        </div>
        <div class="content-header-right">
            <button type="button" class="btn btn-primary" onclick="createConfig()">
                <i class="si si-plus"></i> 新增配置
            </button>
            <button type="button" class="btn btn-success" onclick="refreshCache()">
                <i class="si si-refresh"></i> 刷新缓存
            </button>
        </div>
    </div>

    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">配置列表</h3>
            <div class="block-options">
                <select id="groupFilter" class="form-control" onchange="filterByGroup()">
                    <option value="">所有分组</option>
                    <option value="general">通用配置</option>
                    <option value="frontend">前端配置</option>
                    <option value="system">系统配置</option>
                    <option value="payment">支付配置</option>
                    <option value="email">邮件配置</option>
                    <option value="sms">短信配置</option>
                </select>
            </div>
        </div>
        <div class="block-content">
            <div id="loading" class="text-center py-4">
                <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                <p class="text-muted mt-2">加载中...</p>
            </div>
            
            <div id="error-message" class="alert alert-danger" style="display: none;">
                <h4><i class="fa fa-exclamation-triangle"></i> 错误</h4>
                <p id="error-text"></p>
            </div>

            <div id="config-table" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>配置键</th>
                                <th>配置名称</th>
                                <th>当前值</th>
                                <th>类型</th>
                                <th>分组</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>更新时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="config-tbody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="empty-state" class="text-center py-5" style="display: none;">
                <i class="si si-settings fa-3x text-muted"></i>
                <h3 class="text-muted mt-3">暂无配置数据</h3>
                <p class="text-muted">点击上方"新增配置"按钮添加第一个配置</p>
            </div>
        </div>
    </div>
</div>

<!-- 配置编辑模态框 -->
<div class="modal fade" id="configModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configModalTitle">新增配置</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="configForm">
                <div class="modal-body">
                    <input type="hidden" id="configId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="configKey">配置键 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="configKey" name="key" required>
                                <small class="form-text text-muted">唯一标识符，如：site_name</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="configName">配置名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="configName" name="name" required>
                                <small class="form-text text-muted">显示名称，如：网站名称</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="configType">配置类型 <span class="text-danger">*</span></label>
                                <select class="form-control" id="configType" name="type" required onchange="toggleValueInput()">
                                    <option value="text">文本</option>
                                    <option value="number">数字</option>
                                    <option value="switch">开关</option>
                                    <option value="select">下拉选择</option>
                                    <option value="textarea">多行文本</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="configGroup">配置分组 <span class="text-danger">*</span></label>
                                <select class="form-control" id="configGroup" name="group" required>
                                    <option value="general">通用配置</option>
                                    <option value="frontend">前端配置</option>
                                    <option value="system">系统配置</option>
                                    <option value="payment">支付配置</option>
                                    <option value="email">邮件配置</option>
                                    <option value="sms">短信配置</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="valueGroup">
                        <label for="configValue">配置值</label>
                        <input type="text" class="form-control" id="configValue" name="value">
                        <textarea class="form-control" id="configValueTextarea" name="value" rows="4" style="display: none;"></textarea>
                        <select class="form-control" id="configValueSelect" name="value" style="display: none;">
                            <option value="1">是</option>
                            <option value="0">否</option>
                        </select>
                    </div>

                    <div class="form-group" id="optionsGroup" style="display: none;">
                        <label for="configOptions">选项配置</label>
                        <textarea class="form-control" id="configOptions" name="options" rows="3" placeholder='{"option1":"选项1","option2":"选项2"}'></textarea>
                        <small class="form-text text-muted">JSON格式，用于下拉选择类型</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="configSort">排序</label>
                                <input type="number" class="form-control" id="configSort" name="sort" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="configStatus" name="status" value="1" checked> 启用状态
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="configDescription">配置描述</label>
                        <textarea class="form-control" id="configDescription" name="description" rows="2" placeholder="配置项的详细说明"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 默认加载前端配置
    filterByGroup();
    
    // 表单提交
    $('#configForm').on('submit', function(e) {
        e.preventDefault();
        saveConfig();
    });
});

// 加载配置数据
function loadConfigs() {
    $('#loading').show();
    $('#error-message').hide();
    $('#config-table').hide();
    $('#empty-state').hide();
    
    $.ajax({
        url: '/admin/system_configs/fetch',
        type: 'GET',
        success: function(response) {
            $('#loading').hide();
            
            if (response.data && response.data.length > 0) {
                renderConfigTable(response.data);
                $('#config-table').show();
            } else {
                $('#empty-state').show();
            }
        },
        error: function(xhr) {
            $('#loading').hide();
            let errorMsg = '加载配置失败';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            $('#error-text').text(errorMsg);
            $('#error-message').show();
        }
    });
}

// 渲染配置表格
function renderConfigTable(configs) {
    let tbody = $('#config-tbody');
    tbody.empty();
    
    configs.forEach(function(config) {
        let statusBadge = config.status ? 
            '<span class="badge badge-success">启用</span>' : 
            '<span class="badge badge-secondary">禁用</span>';
        
        let isSystemBadge = config.is_system ? 
            '<span class="badge badge-warning">系统</span>' : '';
        
        let displayValue = config.display_value || config.value || '-';
        if (displayValue.length > 50) {
            displayValue = displayValue.substring(0, 50) + '...';
        }
        
        let row = `
            <tr>
                <td>${config.id}</td>
                <td>
                    <code>${config.key}</code>
                    ${isSystemBadge}
                </td>
                <td>${config.name || '-'}</td>
                <td title="${config.value || ''}">${displayValue}</td>
                <td>
                    <span class="badge badge-info">${getTypeLabel(config.type)}</span>
                </td>
                <td>${getGroupLabel(config.group)}</td>
                <td>${config.sort || 0}</td>
                <td>${statusBadge}</td>
                <td>${config.updated_at || '-'}</td>
                <td>
                    <div class="operation-buttons">
                        <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;" onclick="editConfig(${config.id})" title="编辑配置">
                            ✏️ 编辑
                        </button>
                        ${config.is_system ? '' : `
                        <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;" onclick="deleteConfig(${config.id}, '${config.key}')" title="删除配置">
                            🗑️ 删除
                        </button>
                        `}
                        <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, ${config.status ? '#28a745, #1e7e34' : '#6c757d, #545b62'}); color: white;" onclick="toggleConfig(${config.id})" title="${config.status ? '禁用' : '启用'}配置">
                            ${config.status ? '✅ 启用' : '❌ 禁用'}
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// 获取类型标签
function getTypeLabel(type) {
    const labels = {
        'text': '文本',
        'number': '数字',
        'switch': '开关',
        'select': '选择',
        'textarea': '多行文本'
    };
    return labels[type] || type;
}

// 获取分组标签
function getGroupLabel(group) {
    const labels = {
        'general': '通用配置',
        'frontend': '前端配置',
        'system': '系统配置',
        'payment': '支付配置',
        'email': '邮件配置',
        'sms': '短信配置'
    };
    return labels[group] || group;
}

// 新建配置
function createConfig() {
    $('#configModalTitle').text('新增配置');
    $('#configForm')[0].reset();
    $('#configId').val('');
    $('#configStatus').prop('checked', true);
    toggleValueInput();
    $('#configModal').modal('show');
}

// 编辑配置
function editConfig(id) {
    // 从表格中获取配置数据
    $.ajax({
        url: '/admin/system_configs/fetch',
        type: 'GET',
        success: function(response) {
            let config = response.data.find(c => c.id === id);
            if (config) {
                $('#configModalTitle').text('编辑配置');
                $('#configId').val(config.id);
                $('#configKey').val(config.key);
                $('#configName').val(config.name);
                $('#configType').val(config.type);
                $('#configGroup').val(config.group);
                $('#configValue').val(config.value);
                $('#configValueTextarea').val(config.value);
                $('#configValueSelect').val(config.value);
                $('#configSort').val(config.sort);
                $('#configStatus').prop('checked', config.status);
                $('#configDescription').val(config.description);
                $('#configOptions').val(config.options || '');
                
                toggleValueInput();
                $('#configModal').modal('show');
            }
        }
    });
}

// 切换值输入框类型
function toggleValueInput() {
    let type = $('#configType').val();
    
    $('#configValue, #configValueTextarea, #configValueSelect').hide();
    $('#optionsGroup').hide();
    
    switch (type) {
        case 'textarea':
            $('#configValueTextarea').show();
            break;
        case 'switch':
            $('#configValueSelect').show();
            break;
        case 'select':
            $('#configValue').show();
            $('#optionsGroup').show();
            break;
        default:
            $('#configValue').show();
            break;
    }
}

// 保存配置
function saveConfig() {
    let formData = new FormData($('#configForm')[0]);
    let data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // 处理复选框
    data.status = $('#configStatus').is(':checked');
    
    // 获取正确的值
    let type = data.type;
    if (type === 'textarea') {
        data.value = $('#configValueTextarea').val();
    } else if (type === 'switch') {
        data.value = $('#configValueSelect').val();
    } else {
        data.value = $('#configValue').val();
    }
    
    $.ajax({
        url: '/admin/system_configs/save',
        type: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#configModal').modal('hide');
            loadConfigs();
            showMessage('配置保存成功', 'success');
        },
        error: function(xhr) {
            let errorMsg = '保存失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showMessage(errorMsg, 'error');
        }
    });
}

// 删除配置
function deleteConfig(id, key) {
    if (confirm(`确定要删除配置 "${key}" 吗？此操作不可恢复！`)) {
        $.ajax({
            url: '/admin/system_configs/drop',
            type: 'POST',
            data: { id: id },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                loadConfigs();
                showMessage('配置删除成功', 'success');
            },
            error: function(xhr) {
                let errorMsg = '删除失败';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showMessage(errorMsg, 'error');
            }
        });
    }
}

// 切换配置状态
function toggleConfig(id) {
    $.ajax({
        url: '/admin/system_configs/toggle',
        type: 'POST',
        data: { id: id },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            loadConfigs();
            showMessage('状态切换成功', 'success');
        },
        error: function(xhr) {
            let errorMsg = '操作失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showMessage(errorMsg, 'error');
        }
    });
}

// 刷新缓存
function refreshCache() {
    $.ajax({
        url: '/admin/system_configs/refreshCache',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            showMessage('缓存刷新成功', 'success');
        },
        error: function(xhr) {
            let errorMsg = '缓存刷新失败';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            showMessage(errorMsg, 'error');
        }
    });
}

// 按分组筛选
function filterByGroup() {
    let group = $('#groupFilter').val();
    let url = '/admin/system_configs/fetch';
    
    if (group) {
        url += '?group=' + group;
    }
    
    $('#loading').show();
    $('#error-message').hide();
    $('#config-table').hide();
    $('#empty-state').hide();
    
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#loading').hide();
            
            if (response.data && response.data.length > 0) {
                renderConfigTable(response.data);
                $('#config-table').show();
            } else {
                $('#empty-state').show();
            }
        },
        error: function(xhr) {
            $('#loading').hide();
            let errorMsg = '加载配置失败';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            $('#error-text').text(errorMsg);
            $('#error-message').show();
        }
    });
}

// 显示消息
function showMessage(message, type) {
    // 这里可以使用你的通知组件
    if (type === 'success') {
        alert('成功: ' + message);
    } else {
        alert('错误: ' + message);
    }
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>