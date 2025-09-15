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
    
    // 检查福利导航是否启用
    function checkNavLinksEnabled() {
        return fetch('/api/v1/admin/menu/config', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.data && data.data.nav_links) {
                return data.data.nav_links.enabled === 1;
            }
            return false;
        })
        .catch(() => false);
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