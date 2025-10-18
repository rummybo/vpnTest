@extends('admin.layouts.default')

@section('title', '系统配置管理')

@section('content')
<div class="content">
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
    loadConfigs();
    
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
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editConfig(${config.id})">
                            <i class="fa fa-edit"></i>
                        </button>
                        ${config.is_system ? '' : `
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteConfig(${config.id}, '${config.key}')">
                            <i class="fa fa-trash"></i>
                        </button>
                        `}
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleConfig(${config.id})">
                            <i class="fa fa-toggle-${config.status ? 'on' : 'off'}"></i>
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
@endsection