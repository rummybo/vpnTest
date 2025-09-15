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
        
        // 创建v2board风格的菜单项HTML
        const menuItemHtml = `
            <li class="nav-main-item">
                <a class="nav-main-link" href="#/nav_links">
                    <i class="nav-main-link-icon si si-link"></i>
                    <span class="nav-main-link-name">福利导航</span>
                </a>
            </li>
        `;
        
        // 尝试多种方式添加菜单项
        let attempts = 0;
        const maxAttempts = 10;
        
        function tryAddMenuItem() {
            attempts++;
            
            // 检查是否已经添加过了
            const existingNavLink = document.querySelector('.nav-main-link[href="#/nav_links"]');
            if (existingNavLink) {
                console.log('福利导航菜单项已存在，跳过添加');
                return true;
            }
            
            // 方法1: 查找v2board的主导航菜单
            const navMain = document.querySelector('ul.nav-main');
            if (navMain) {
                // 查找合适的插入位置
                const lastHeading = navMain.querySelector('li.nav-main-heading:last-of-type');
                const lastMenuItem = navMain.querySelector('li.nav-main-item:last-of-type');
                
                if (lastHeading) {
                    // 在最后一个分组标题后添加
                    lastHeading.insertAdjacentHTML('afterend', menuItemHtml);
                    console.log('福利导航菜单项已添加到最后一个分组后');
                    return true;
                } else if (lastMenuItem) {
                    // 在最后一个菜单项后添加
                    lastMenuItem.insertAdjacentHTML('afterend', menuItemHtml);
                    console.log('福利导航菜单项已添加到菜单末尾');
                    return true;
                } else {
                    // 直接添加到导航容器末尾
                    navMain.insertAdjacentHTML('beforeend', menuItemHtml);
                    console.log('福利导航菜单项已添加到导航容器末尾');
                    return true;
                }
            }
            
            // 方法2: 查找包含nav的容器
            const navContainers = document.querySelectorAll('[class*="nav"]');
            for (let container of navContainers) {
                if (container.tagName === 'UL' && container.querySelector('li.nav-main-item')) {
                    container.insertAdjacentHTML('beforeend', menuItemHtml);
                    console.log('福利导航菜单项已添加到nav容器');
                    return true;
                }
            }
            
            // 如果还没找到，继续尝试
            if (attempts < maxAttempts) {
                console.log(`第${attempts}次尝试失败，${maxAttempts - attempts}次后放弃...`);
                setTimeout(tryAddMenuItem, 500);
                return false;
            } else {
                console.warn('无法找到合适的菜单容器添加福利导航菜单项');
                console.log('当前页面的导航结构:', document.querySelector('ul.nav-main'));
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