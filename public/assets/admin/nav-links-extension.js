/**
 * v2board 福利导航扩展 - 简化版
 * 直接添加福利导航菜单项到后台管理界面
 */

(function() {
    'use strict';
    
    console.log('福利导航扩展已加载 - 简化版');
    
    // 检查福利导航是否启用（从配置中读取）
    function isNavLinksEnabled() {
        // 从window.settings中读取配置
        if (window.settings && window.settings.nav_links_enable) {
            return window.settings.nav_links_enable === 1 || window.settings.nav_links_enable === '1';
        }
        
        // 默认启用（如果没有配置）
        return true;
    }
    
    // 获取安全路径
    function getSecurePath() {
        if (window.settings && window.settings.secure_path) {
            return window.settings.secure_path;
        }
        
        // 从URL中提取
        const currentPath = window.location.pathname;
        const pathMatch = currentPath.match(/\/([a-f0-9]{8})/);
        if (pathMatch) {
            return pathMatch[1];
        }
        
        return '149544e4'; // 备用
    }
    
    // 添加福利导航菜单项
    function addNavLinksMenuItem() {
        console.log('正在添加福利导航菜单项...');
        
        const securePath = getSecurePath();
        
        // 创建菜单项HTML
        const menuItemHtml = `
            <li class="ant-menu-item" role="menuitem">
                <a href="/#/nav_links" class="nav-links-menu-item">
                    <span class="ant-menu-item-icon">
                        <svg viewBox="64 64 896 896" focusable="false" data-icon="link" width="1em" height="1em" fill="currentColor" aria-hidden="true">
                            <path d="M574 665.4a8.03 8.03 0 00-11.3 0L446.5 781.6c-53.8 53.8-144.6 59.5-204 0-59.5-59.5-53.8-150.2 0-204l116.2-116.2c3.1-3.1 3.1-8.2 0-11.3l-39.8-39.8a8.03 8.03 0 00-11.3 0L191.4 526.5c-84.6 84.6-84.6 221.5 0 306s221.5 84.6 306 0l116.2-116.2c3.1-3.1 3.1-8.2 0-11.3L574 665.4zm258.6-474c-84.6-84.6-221.5-84.6-306 0L410.3 307.6a8.03 8.03 0 000 11.3l39.7 39.7c3.1 3.1 8.2 3.1 11.3 0l116.2-116.2c53.8-53.8 144.6-59.5 204 0 59.5 59.5 53.8 150.2 0 204L665.3 562.6a8.03 8.03 0 000 11.3l39.8 39.8c3.1 3.1 8.2 3.1 11.3 0l116.2-116.2c84.5-84.6 84.5-221.5 0-306.1zM610.1 372.3a8.03 8.03 0 00-11.3 0L372.3 598.7a8.03 8.03 0 000 11.3l39.6 39.6c3.1 3.1 8.2 3.1 11.3 0l226.4-226.4c3.1-3.1 3.1-8.2 0-11.3l-39.5-39.6z"></path>
                        </svg>
                    </span>
                    <span class="ant-menu-title-content">福利导航</span>
                </a>
            </li>
        `;
        
        // 尝试多种方式添加菜单项
        let attempts = 0;
        const maxAttempts = 10;
        
        function tryAddMenuItem() {
            attempts++;
            
            // 方法1: 查找侧边栏菜单
            const sidebarMenu = document.querySelector('.ant-menu-root, .ant-menu, [role="menu"]');
            if (sidebarMenu) {
                // 查找合适的插入位置（在"系统管理"或最后一个菜单项后）
                const systemMenuItem = sidebarMenu.querySelector('li:contains("系统管理"), li:contains("System")');
                const lastMenuItem = sidebarMenu.querySelector('li:last-child');
                
                if (systemMenuItem) {
                    systemMenuItem.insertAdjacentHTML('beforebegin', menuItemHtml);
                    console.log('福利导航菜单项已添加到系统管理前');
                    return true;
                } else if (lastMenuItem) {
                    lastMenuItem.insertAdjacentHTML('afterend', menuItemHtml);
                    console.log('福利导航菜单项已添加到菜单末尾');
                    return true;
                }
            }
            
            // 方法2: 查找特定的菜单容器
            const menuContainer = document.querySelector('.ant-layout-sider .ant-menu');
            if (menuContainer) {
                menuContainer.insertAdjacentHTML('beforeend', menuItemHtml);
                console.log('福利导航菜单项已添加到菜单容器');
                return true;
            }
            
            // 如果还没找到，继续尝试
            if (attempts < maxAttempts) {
                setTimeout(tryAddMenuItem, 500);
                return false;
            } else {
                console.warn('无法找到合适的菜单容器添加福利导航菜单项');
                return false;
            }
        }
        
        return tryAddMenuItem();
    }
    
    // 初始化函数
    function init() {
        console.log('初始化福利导航扩展...');
        
        // 检查是否启用
        if (!isNavLinksEnabled()) {
            console.log('福利导航功能未启用');
            return;
        }
        
        console.log('福利导航功能已启用，准备添加菜单项');
        
        // 等待页面加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addNavLinksMenuItem);
        } else {
            // 延迟执行，确保React应用已渲染
            setTimeout(addNavLinksMenuItem, 1000);
        }
        
        // 监听路由变化，重新添加菜单项（如果需要）
        let lastUrl = location.href;
        new MutationObserver(() => {
            const url = location.href;
            if (url !== lastUrl) {
                lastUrl = url;
                setTimeout(addNavLinksMenuItem, 500);
            }
        }).observe(document, { subtree: true, childList: true });
    }
    
    // 启动扩展
    init();
    
})();