/**
 * 系统配置管理扩展
 * System Config Management Extension for V2Board Admin
 */

(function() {
    'use strict';

    // 等待页面和框架加载完成
    document.addEventListener('DOMContentLoaded', function() {
        // 延迟初始化，确保主框架已经加载
        setTimeout(initSystemConfigExtension, 1000);
    });

    function initSystemConfigExtension() {
        console.log('[SystemConfig] Extension initializing...');

        // 检查是否在管理后台且框架已加载
        if (!window.settings || !document.getElementById('root')) {
            console.log('[SystemConfig] Not in admin panel or framework not ready');
            return;
        }

        // 检查系统配置管理功能是否启用
        const configEnabled = window.settings.system_config_enable;
        if (!configEnabled && configEnabled !== 1 && configEnabled !== '1') {
            console.log('[SystemConfig] System config management is disabled, value:', configEnabled);
            return;
        }
        console.log('[SystemConfig] System config management is enabled, value:', configEnabled);

        // 等待React应用加载完成
        waitForReactApp();
    }

    function waitForReactApp() {
        const checkInterval = setInterval(() => {
            // 检查是否有菜单容器或其他React组件
            const menuContainer = document.querySelector('.ant-menu') || 
                                document.querySelector('.ant-layout-sider') ||
                                document.querySelector('[class*="menu"]') ||
                                document.querySelector('[class*="sidebar"]');
            
            if (menuContainer) {
                clearInterval(checkInterval);
                console.log('[SystemConfig] React app detected, registering extension');
                registerSystemConfigExtension();
            }
        }, 500);

        // 10秒后停止检查
        setTimeout(() => {
            clearInterval(checkInterval);
            console.log('[SystemConfig] Timeout waiting for React app, trying fallback registration');
            registerSystemConfigExtension();
        }, 10000);
    }

    function registerSystemConfigExtension() {
        try {
            // 尝试注册菜单项和路由
            registerMenuAndRoutes();
            
            // 监听路由变化
            monitorRouteChanges();
            
            console.log('[SystemConfig] Extension registered successfully');
        } catch (error) {
            console.error('[SystemConfig] Registration error:', error);
        }
    }

    function registerMenuAndRoutes() {
        // 如果存在全局的路由或菜单注册方法
        if (window.registerAdminExtension) {
            window.registerAdminExtension({
                name: 'system-config',
                title: '系统配置',
                icon: 'setting',
                path: '/system/config',
                component: renderSystemConfigPage
            });
        }

        // 或者直接添加到菜单中
        addToMenu();
    }

    function addToMenu() {
        // 寻找菜单容器
        const menuSelectors = [
            '.ant-menu',
            '.ant-layout-sider .ant-menu',
            '[class*="menu"]',
            '[class*="sidebar"] ul',
            '.sidebar-menu'
        ];

        let menuContainer = null;
        for (const selector of menuSelectors) {
            menuContainer = document.querySelector(selector);
            if (menuContainer) break;
        }

        if (menuContainer) {
            // 创建菜单项
            const menuItem = createMenuItem();
            menuContainer.appendChild(menuItem);
            console.log('[SystemConfig] Menu item added');
        } else {
            // 如果找不到菜单，创建一个浮动按钮
            createFloatingButton();
        }
    }

    function createMenuItem() {
        const menuItem = document.createElement('li');
        menuItem.className = 'ant-menu-item system-config-menu-item';
        menuItem.innerHTML = `
            <span class="ant-menu-title-content">
                <i class="anticon anticon-setting" style="margin-right: 8px;"></i>
                系统配置
            </span>
        `;
        
        menuItem.addEventListener('click', function(e) {
            e.preventDefault();
            showSystemConfigModal();
        });

        return menuItem;
    }

    function createFloatingButton() {
        const button = document.createElement('div');
        button.id = 'system-config-float-btn';
        button.innerHTML = `
            <div style="
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                background: #1890ff;
                border-radius: 50%;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 20px;
                z-index: 9999;
                transition: all 0.3s;
            " title="系统配置">
                ⚙️
            </div>
        `;

        button.addEventListener('click', showSystemConfigModal);
        
        button.addEventListener('mouseenter', function() {
            this.firstElementChild.style.transform = 'scale(1.1)';
            this.firstElementChild.style.background = '#40a9ff';
        });

        button.addEventListener('mouseleave', function() {
            this.firstElementChild.style.transform = 'scale(1)';
            this.firstElementChild.style.background = '#1890ff';
        });

        document.body.appendChild(button);
        console.log('[SystemConfig] Floating button created');
    }

    function monitorRouteChanges() {
        // 监听URL变化
        let currentPath = window.location.pathname;
        
        const observer = new MutationObserver(() => {
            if (window.location.pathname !== currentPath) {
                currentPath = window.location.pathname;
                handleRouteChange(currentPath);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // 监听pushState和replaceState
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;

        history.pushState = function() {
            originalPushState.apply(history, arguments);
            setTimeout(() => handleRouteChange(window.location.pathname), 100);
        };

        history.replaceState = function() {
            originalReplaceState.apply(history, arguments);
            setTimeout(() => handleRouteChange(window.location.pathname), 100);
        };

        // 监听popstate事件
        window.addEventListener('popstate', () => {
            setTimeout(() => handleRouteChange(window.location.pathname), 100);
        });
    }

    function handleRouteChange(path) {
        if (path.includes('system-config') || path.includes('system/config')) {
            showSystemConfigModal();
        }
    }

    function showSystemConfigModal() {
        const existingModal = document.getElementById('system-config-modal');
        if (existingModal) {
            existingModal.style.display = 'block';
            return;
        }

        const modal = createSystemConfigModal();
        document.body.appendChild(modal);
        loadSystemConfigData();
    }

    function createSystemConfigModal() {
        const modal = document.createElement('div');
        modal.id = 'system-config-modal';
        modal.innerHTML = `
            <div class="system-config-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div class="system-config-modal-content" style="
                    background: white;
                    border-radius: 8px;
                    width: 90%;
                    max-width: 1200px;
                    max-height: 90vh;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                ">
                    <div class="system-config-modal-header" style="
                        padding: 16px 24px;
                        border-bottom: 1px solid #f0f0f0;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <h3 style="margin: 0; color: #262626;">系统配置管理</h3>
                        <button class="close-btn" style="
                            background: none;
                            border: none;
                            font-size: 20px;
                            cursor: pointer;
                            color: #999;
                        ">&times;</button>
                    </div>
                    
                    <div class="system-config-modal-body" style="
                        padding: 24px;
                        max-height: calc(90vh - 100px);
                        overflow-y: auto;
                    ">
                        <div class="config-tabs">
                            <div class="tab-nav" style="
                                display: flex;
                                border-bottom: 1px solid #f0f0f0;
                                margin-bottom: 24px;
                            ">
                                <button class="tab-btn active" data-group="frontend" style="
                                    padding: 12px 24px;
                                    border: none;
                                    background: none;
                                    cursor: pointer;
                                    border-bottom: 2px solid #1890ff;
                                    color: #1890ff;
                                ">前端配置</button>
                                <button class="tab-btn" data-group="general" style="
                                    padding: 12px 24px;
                                    border: none;
                                    background: none;
                                    cursor: pointer;
                                    color: #8c8c8c;
                                ">通用配置</button>
                                <button class="tab-btn" data-group="system" style="
                                    padding: 12px 24px;
                                    border: none;
                                    background: none;
                                    cursor: pointer;
                                    color: #8c8c8c;
                                ">系统配置</button>
                            </div>
                            
                            <div class="tab-content">
                                <div class="tab-pane active" id="frontend-configs">
                                    <div class="loading" style="text-align: center; padding: 40px;">
                                        <div style="font-size: 14px; color: #999;">加载中...</div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="general-configs" style="display: none;">
                                    <div class="loading" style="text-align: center; padding: 40px;">
                                        <div style="font-size: 14px; color: #999;">加载中...</div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="system-configs" style="display: none;">
                                    <div class="loading" style="text-align: center; padding: 40px;">
                                        <div style="font-size: 14px; color: #999;">加载中...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-actions" style="
                            text-align: center;
                            margin-top: 24px;
                            padding-top: 24px;
                            border-top: 1px solid #f0f0f0;
                        ">
                            <button onclick="refreshConfigCache()" style="
                                background: #1890ff;
                                color: white;
                                border: none;
                                padding: 8px 16px;
                                border-radius: 4px;
                                cursor: pointer;
                                margin: 0 8px;
                            ">刷新缓存</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 绑定事件
        const closeBtn = modal.querySelector('.close-btn');
        const overlay = modal.querySelector('.system-config-modal-overlay');
        
        closeBtn.onclick = () => closeSystemConfigModal();
        overlay.onclick = (e) => {
            if (e.target === overlay) closeSystemConfigModal();
        };

        // 标签页切换
        const tabBtns = modal.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.onclick = () => switchConfigTab(btn.dataset.group);
        });

        return modal;
    }

    function closeSystemConfigModal() {
        const modal = document.getElementById('system-config-modal');
        if (modal) {
            modal.remove();
        }
    }

    function switchConfigTab(group) {
        // 切换按钮状态
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if (btn.dataset.group === group) {
                btn.style.borderBottom = '2px solid #1890ff';
                btn.style.color = '#1890ff';
                btn.classList.add('active');
            } else {
                btn.style.borderBottom = 'none';
                btn.style.color = '#8c8c8c';
                btn.classList.remove('active');
            }
        });

        // 切换内容面板
        document.querySelectorAll('.tab-pane').forEach(pane => {
            if (pane.id === group + '-configs') {
                pane.style.display = 'block';
                pane.classList.add('active');
            } else {
                pane.style.display = 'none';
                pane.classList.remove('active');
            }
        });

        // 加载对应分组的配置
        loadConfigsByGroup(group);
    }

    function loadSystemConfigData() {
        // 默认加载前端配置
        loadConfigsByGroup('frontend');
    }

    function loadConfigsByGroup(group) {
        const container = document.getElementById(group + '-configs');
        if (!container) return;

        container.innerHTML = '<div class="loading" style="text-align: center; padding: 40px;"><div style="font-size: 14px; color: #999;">加载中...</div></div>';

        const securePath = window.settings?.secure_path || '';
        const apiUrl = `/api/v1/${securePath}/system_configs/fetch?group=${group}`;

        fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.data) {
                renderConfigList(container, data.data);
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">暂无配置数据</div>';
            }
        })
        .catch(error => {
            console.error('Load configs error:', error);
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #ff4d4f;">加载失败，请重试</div>';
        });
    }

    function renderConfigList(container, configs) {
        if (!configs || configs.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">暂无配置数据</div>';
            return;
        }

        const html = configs.map(config => `
            <div class="config-item" style="
                border: 1px solid #f0f0f0;
                border-radius: 6px;
                padding: 16px;
                margin-bottom: 16px;
                transition: all 0.3s;
                background: #fafafa;
            " data-id="${config.id}">
                <div class="config-header" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 12px;
                ">
                    <h4 style="margin: 0; color: #262626;">${config.name}</h4>
                    ${config.type === 'switch' ? `
                        <label class="switch" style="
                            position: relative;
                            display: inline-block;
                            width: 44px;
                            height: 22px;
                        ">
                            <input type="checkbox" ${config.value === '1' ? 'checked' : ''} 
                                   onchange="toggleConfig(${config.id}, this.checked)"
                                   style="opacity: 0; width: 0; height: 0;">
                            <span class="slider" style="
                                position: absolute;
                                cursor: pointer;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background-color: ${config.value === '1' ? '#1890ff' : '#ccc'};
                                transition: .4s;
                                border-radius: 22px;
                            "></span>
                        </label>
                    ` : ''}
                </div>
                
                <div class="config-body">
                    <p style="margin: 0 0 8px 0; color: #8c8c8c; font-size: 14px;">${config.description || ''}</p>
                    <div style="display: flex; gap: 16px; font-size: 12px; color: #bfbfbf;">
                        <span>键名: ${config.key}</span>
                        <span>类型: ${config.type}</span>
                        ${config.is_system ? '<span style="background: #f50; color: white; padding: 2px 6px; border-radius: 2px; font-size: 10px;">系统配置</span>' : ''}
                    </div>
                </div>
                
                ${config.type !== 'switch' ? `
                    <div class="config-value" style="margin-top: 12px;">
                        <input type="text" value="${config.value || ''}" 
                               onchange="updateConfigValue(${config.id}, this.value)"
                               ${config.is_system ? 'readonly' : ''}
                               style="
                                   width: 100%;
                                   padding: 8px 12px;
                                   border: 1px solid #d9d9d9;
                                   border-radius: 4px;
                                   font-size: 14px;
                               ">
                    </div>
                ` : ''}
            </div>
        `).join('');

        container.innerHTML = html;

        // 添加开关样式
        addSwitchStyles();
    }

    function addSwitchStyles() {
        if (document.getElementById('switch-styles')) return;

        const style = document.createElement('style');
        style.id = 'switch-styles';
        style.textContent = `
            .switch .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 2px;
                bottom: 2px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            .switch input:checked + .slider:before {
                transform: translateX(22px);
            }
            
            .config-item:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
        `;
        document.head.appendChild(style);
    }

    // 全局函数
    window.toggleConfig = function(configId, enabled) {
        const securePath = window.settings?.secure_path || '';
        const apiUrl = `/api/v1/${securePath}/system_configs/toggle`;

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: configId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.data) {
                showMessage('配置更新成功', 'success');
                // 重新加载当前标签页
                const activeTab = document.querySelector('.tab-btn.active');
                if (activeTab) {
                    loadConfigsByGroup(activeTab.dataset.group);
                }
            } else {
                showMessage(data.message || '更新失败', 'error');
            }
        })
        .catch(error => {
            console.error('Toggle config error:', error);
            showMessage('更新失败，请重试', 'error');
        });
    };

    window.updateConfigValue = function(configId, value) {
        const securePath = window.settings?.secure_path || '';
        const apiUrl = `/api/v1/${securePath}/system_configs/save`;

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id: configId,
                value: value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.data) {
                showMessage('配置更新成功', 'success');
            } else {
                showMessage(data.message || '更新失败', 'error');
            }
        })
        .catch(error => {
            console.error('Update config error:', error);
            showMessage('更新失败，请重试', 'error');
        });
    };

    window.refreshConfigCache = function() {
        const securePath = window.settings?.secure_path || '';
        const apiUrl = `/api/v1/${securePath}/system_configs/refresh-cache`;

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.data) {
                showMessage('缓存刷新成功', 'success');
            } else {
                showMessage(data.message || '刷新失败', 'error');
            }
        })
        .catch(error => {
            console.error('Refresh cache error:', error);
            showMessage('刷新失败，请重试', 'error');
        });
    };

    function showMessage(message, type = 'info') {
        const messageEl = document.createElement('div');
        messageEl.className = `system-config-message message-${type}`;
        messageEl.textContent = message;
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#52c41a' : type === 'error' ? '#ff4d4f' : '#1890ff'};
            color: white;
            border-radius: 4px;
            z-index: 10001;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(messageEl);

        setTimeout(() => {
            messageEl.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => messageEl.remove(), 300);
        }, 3000);
    }

    // 添加动画样式
    const animationStyle = document.createElement('style');
    animationStyle.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(animationStyle);

    console.log('[SystemConfig] Extension loaded');
    
    // 直接初始化（如果DOM已经加载完成）
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initSystemConfigExtension, 1000);
        });
    } else {
        // DOM已经加载完成，直接初始化
        setTimeout(initSystemConfigExtension, 1000);
    }
    
    // 直接调用初始化函数（确保扩展启动）
    initSystemConfigExtension();
})();