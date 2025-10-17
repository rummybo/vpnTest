@extends('admin.layout')

@section('title', '系统配置管理')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            系统配置管理
            <small>管理系统各功能模块的开关配置</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> 首页</a></li>
            <li class="active">系统配置</li>
        </ol>
    </section>

    <section class="content">
        <!-- 配置分组标签页 -->
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#frontend" data-toggle="tab">前端配置</a></li>
                <li><a href="#general" data-toggle="tab">通用配置</a></li>
                <li><a href="#system" data-toggle="tab">系统配置</a></li>
            </ul>
            
            <div class="tab-content">
                <!-- 前端配置 -->
                <div class="active tab-pane" id="frontend">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">前端功能开关</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-success btn-sm" onclick="refreshCache()">
                                    <i class="fa fa-refresh"></i> 刷新缓存
                                </button>
                            </div>
                        </div>
                        
                        <div class="box-body">
                            <div class="row" id="frontend-configs">
                                <!-- 前端配置将在这里动态加载 -->
                                <div class="col-md-12 text-center">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p>加载中...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 通用配置 -->
                <div class="tab-pane" id="general">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">通用系统配置</h3>
                        </div>
                        
                        <div class="box-body">
                            <div class="row" id="general-configs">
                                <!-- 通用配置将在这里动态加载 -->
                                <div class="col-md-12 text-center">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p>加载中...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 系统配置 -->
                <div class="tab-pane" id="system">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">系统级配置</h3>
                        </div>
                        
                        <div class="box-body">
                            <div class="row" id="system-configs">
                                <!-- 系统配置将在这里动态加载 -->
                                <div class="col-md-12 text-center">
                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                    <p>加载中...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- 配置编辑模态框 -->
<div class="modal fade" id="configModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">编辑配置</h4>
            </div>
            
            <div class="modal-body">
                <form id="configForm">
                    <input type="hidden" id="configId" name="id">
                    
                    <div class="form-group">
                        <label for="configName">配置名称</label>
                        <input type="text" class="form-control" id="configName" name="name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="configDescription">配置描述</label>
                        <textarea class="form-control" id="configDescription" name="description" rows="2" readonly></textarea>
                    </div>
                    
                    <div class="form-group" id="configValueGroup">
                        <label for="configValue">配置值</label>
                        <div id="configValueContainer">
                            <!-- 根据配置类型动态生成输入控件 -->
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveConfig()">保存</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 页面加载完成后初始化
    loadConfigs('frontend');
    
    // 标签页切换事件
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href").replace('#', '');
        loadConfigs(target);
    });
});

// 获取安全路径
function getSecurePath() {
    return window.settings?.secure_path || '';
}

// 加载配置数据
function loadConfigs(group) {
    var container = '#' + group + '-configs';
    $(container).html('<div class="col-md-12 text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>加载中...</p></div>');
    
    $.ajax({
        url: '/api/v1/' + getSecurePath() + '/system_configs/fetch?group=' + group,
        type: 'GET',
        success: function(response) {
            if (response.data && response.data.length > 0) {
                renderConfigs(container, response.data);
            } else {
                $(container).html('<div class="col-md-12 text-center"><p>暂无配置数据</p></div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('加载配置失败:', error);
            $(container).html('<div class="col-md-12 text-center"><p class="text-danger">加载失败，请重试</p></div>');
        }
    });
}

// 渲染配置列表
function renderConfigs(container, configs) {
    var html = '';
    
    configs.forEach(function(config) {
        html += `
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-${config.type === 'switch' ? (config.value === '1' ? 'green' : 'red') : 'blue'}">
                        <i class="fa fa-${getConfigIcon(config.type)}"></i>
                    </span>
                    
                    <div class="info-box-content">
                        <span class="info-box-text">${config.name}</span>
                        <span class="info-box-number">
                            ${config.type === 'switch' ? 
                                `<label class="switch">
                                    <input type="checkbox" ${config.value === '1' ? 'checked' : ''} 
                                           onchange="toggleConfig(${config.id}, this.checked)">
                                    <span class="slider round"></span>
                                </label>` : 
                                `<span class="config-value">${config.value || '未设置'}</span>`
                            }
                        </span>
                        
                        <div class="progress">
                            <div class="progress-bar" style="width: ${config.status ? '100' : '0'}%"></div>
                        </div>
                        
                        <span class="progress-description">
                            ${config.description || ''}
                            ${config.is_system ? '<span class="label label-warning">系统配置</span>' : ''}
                            ${config.type !== 'switch' ? 
                                `<button class="btn btn-xs btn-info pull-right" onclick="editConfig(${config.id})">
                                    <i class="fa fa-edit"></i>
                                </button>` : ''
                            }
                        </span>
                    </div>
                </div>
            </div>
        `;
    });
    
    $(container).html(html);
}

// 获取配置类型图标
function getConfigIcon(type) {
    var icons = {
        'switch': 'toggle-on',
        'text': 'font',
        'number': 'calculator',
        'select': 'list',
        'textarea': 'file-text-o'
    };
    return icons[type] || 'cog';
}

// 切换开关配置
function toggleConfig(configId, enabled) {
    $.ajax({
        url: '/api/v1/' + getSecurePath() + '/system_configs/toggle',
        type: 'POST',
        data: {
            id: configId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.data) {
                showAlert('配置更新成功', 'success');
                // 重新加载当前标签页
                var activeTab = $('.nav-tabs .active a').attr('href').replace('#', '');
                loadConfigs(activeTab);
            } else {
                showAlert(response.message || '更新失败', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('切换配置失败:', error);
            showAlert('更新失败，请重试', 'error');
        }
    });
}

// 编辑配置
function editConfig(configId) {
    $.ajax({
        url: '/api/v1/' + getSecurePath() + '/system_configs/fetch',
        type: 'GET',
        success: function(response) {
            var config = response.data.find(c => c.id == configId);
            if (config) {
                showConfigModal(config);
            }
        },
        error: function(xhr, status, error) {
            showAlert('获取配置信息失败', 'error');
        }
    });
}

// 显示配置编辑模态框
function showConfigModal(config) {
    $('#configId').val(config.id);
    $('#configName').val(config.name);
    $('#configDescription').val(config.description);
    
    // 根据配置类型生成输入控件
    generateValueInput(config);
    
    $('#configModal').modal('show');
}

// 生成配置值输入控件
function generateValueInput(config) {
    var container = $('#configValueContainer');
    var html = '';
    
    switch (config.type) {
        case 'text':
            html = `<input type="text" class="form-control" id="configValue" name="value" value="${config.value || ''}">`;
            break;
        case 'number':
            html = `<input type="number" class="form-control" id="configValue" name="value" value="${config.value || ''}">`;
            break;
        case 'textarea':
            html = `<textarea class="form-control" id="configValue" name="value" rows="3">${config.value || ''}</textarea>`;
            break;
        case 'select':
            html = '<select class="form-control" id="configValue" name="value">';
            if (config.options) {
                Object.keys(config.options).forEach(key => {
                    html += `<option value="${key}" ${config.value === key ? 'selected' : ''}>${config.options[key]}</option>`;
                });
            }
            html += '</select>';
            break;
        default:
            html = `<input type="text" class="form-control" id="configValue" name="value" value="${config.value || ''}">`;
    }
    
    container.html(html);
}

// 保存配置
function saveConfig() {
    var formData = {
        id: $('#configId').val(),
        value: $('#configValue').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    $.ajax({
        url: '/api/v1/' + getSecurePath() + '/system_configs/save',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.data) {
                showAlert('配置保存成功', 'success');
                $('#configModal').modal('hide');
                // 重新加载当前标签页
                var activeTab = $('.nav-tabs .active a').attr('href').replace('#', '');
                loadConfigs(activeTab);
            } else {
                showAlert(response.message || '保存失败', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('保存配置失败:', error);
            showAlert('保存失败，请重试', 'error');
        }
    });
}

// 刷新缓存
function refreshCache() {
    $.ajax({
        url: '/api/v1/' + getSecurePath() + '/system_configs/refresh-cache',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.data) {
                showAlert('缓存刷新成功', 'success');
            } else {
                showAlert(response.message || '刷新失败', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('刷新缓存失败:', error);
            showAlert('刷新失败，请重试', 'error');
        }
    });
}

// 显示提示消息
function showAlert(message, type) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var alertHtml = `
        <div class="alert ${alertClass} alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            ${message}
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>

<style>
/* 开关样式 */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:focus + .slider {
    box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
    -webkit-transform: translateX(26px);
    -ms-transform: translateX(26px);
    transform: translateX(26px);
}

.slider.round {
    border-radius: 24px;
}

.slider.round:before {
    border-radius: 50%;
}

/* 配置值样式 */
.config-value {
    font-weight: bold;
    color: #333;
}

/* 信息框样式调整 */
.info-box {
    margin-bottom: 15px;
}

.info-box-content {
    padding: 5px 10px;
}

.progress {
    margin-top: 5px;
    height: 2px;
}

.progress-description {
    font-size: 12px;
    color: #777;
    margin-top: 5px;
}
</style>
@endsection