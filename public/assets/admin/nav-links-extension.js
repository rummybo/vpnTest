/**
 * v2board 福利导航扩展 - 简化版
 * 直接添加福利导航菜单项到后台管理界面
 */

(function() {
    'use strict';
    
    console.log('福利导航扩展已加载 - 简化版');
    
    // 全局标记，防止重复添加
    if (window.navLinksExtensionLoaded) {
        console.log('福利导航扩展已经加载过，跳过重复加载');
        return;
    }
    window.navLinksExtensionLoaded = true;
    
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
            
            // 检查是否已经添加过了（多种方式检查）
            const existingNavLink = document.querySelector('.nav-main-link[href="#/nav_links"]') ||
                                  document.querySelector('a[href="#/nav_links"]');
            
            // 检查是否有包含"福利导航"文本的菜单项
            const navLinkNames = document.querySelectorAll('.nav-main-link-name');
            for (let nameElement of navLinkNames) {
                if (nameElement.textContent && nameElement.textContent.includes('福利导航')) {
                    console.log('福利导航菜单项已存在（通过文本检查），跳过添加');
                    return true;
                }
            }
            
            if (existingNavLink) {
                console.log('福利导航菜单项已存在（通过链接检查），跳过添加');
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
        let isObserving = false;
        
        function handleRouteChange() {
            const url = location.href;
            if (url !== lastUrl) {
                lastUrl = url;
                // 延迟更长时间，确保新页面完全渲染
                setTimeout(() => {
                    // 只有当菜单项不存在时才添加
                    const existingNavLink = document.querySelector('.nav-main-link[href="#/nav_links"]');
                    if (!existingNavLink) {
                        console.log('路由变化检测到菜单项丢失，重新添加...');
                        addNavLinksMenuItem();
                    }
                }, 1000);
            }
        }
        
        // 使用更精确的观察器，只监听必要的变化
        if (!isObserving) {
            const observer = new MutationObserver((mutations) => {
                // 只在URL实际变化时处理
                handleRouteChange();
            });
            
            // 只观察body的直接子元素变化，减少触发频率
            observer.observe(document.body, { 
                childList: true, 
                subtree: false 
            });
            
            // 也监听popstate事件（浏览器前进后退）
            window.addEventListener('popstate', handleRouteChange);
            
            isObserving = true;
            console.log('路由变化监听器已启动');
        }
    }
    
    // 启动扩展
    init();
    
})();