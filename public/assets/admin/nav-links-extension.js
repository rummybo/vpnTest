/**
 * v2board 福利导航扩展
 * 动态添加福利导航菜单项到后台管理界面
 */

(function() {
    'use strict';
    
    // 配置信息
    const NAV_LINKS_CONFIG = {
        title: '福利导航',
        icon: 'fa fa-link',
        path: '/nav_links'
    };
    
    // 获取正确的API基础路径
    function getApiBasePath() {
        // 方法1: 从window.settings中获取secure_path
        if (window.settings && window.settings.secure_path) {
            console.log('从window.settings获取secure_path:', window.settings.secure_path);
            return `/api/v1/${window.settings.secure_path}`;
        }
        
        // 方法2: 从当前URL路径中提取secure_path
        const currentPath = window.location.pathname;
        const pathMatch = currentPath.match(/\/([a-f0-9]{8})/);
        if (pathMatch) {
            console.log('从URL路径提取secure_path:', pathMatch[1]);
            return `/api/v1/${pathMatch[1]}`;
        }
        
        // 方法3: 从页面中的script标签或meta标签获取
        const metaSecurePath = document.querySelector('meta[name="secure-path"]');
        if (metaSecurePath) {
            console.log('从meta标签获取secure_path:', metaSecurePath.content);
            return `/api/v1/${metaSecurePath.content}`;
        }
        
        // 备用方案: 使用已知的secure_path
        console.log('使用备用secure_path: 149544e4');
        return '/api/v1/149544e4';
    }
    
    // 检查福利导航是否启用
    function checkNavLinksEnabled() {
        const apiPath = getApiBasePath() + '/menu/config';
        
        return fetch(apiPath, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            console.log('菜单配置API响应状态:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('菜单配置API响应数据:', data);
            if (data && data.data && data.data.nav_links) {
                return data.data.nav_links.enabled === 1;
            }
            return false;
        })
        .catch(error => {
            console.error('检查福利导航配置失败:', error);
            return false;
        });
    }
    
    // 添加菜单项到侧边栏
    function addNavLinksToSidebar() {
        const sidebar = document.querySelector('.sidebar-menu, .nav-sidebar, .ant-menu');
        if (sidebar && !sidebar.querySelector('[href="#/nav_links"]')) {
            const menuItem = document.createElement('li');
            menuItem.className = 'nav-item';
            menuItem.innerHTML = `
                <a class="nav-link" href="#/nav_links">
                    <i class="${NAV_LINKS_CONFIG.icon}"></i>
                    <span>${NAV_LINKS_CONFIG.title}</span>
                </a>
            `;
            sidebar.appendChild(menuItem);
            console.log('福利导航菜单项已添加');
        }
    }
    
    // 初始化
    function init() {
        checkNavLinksEnabled().then(enabled => {
            if (enabled) {
                setTimeout(addNavLinksToSidebar, 2000);
                setInterval(addNavLinksToSidebar, 5000);
            }
        });
    }
    
    // 启动
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();