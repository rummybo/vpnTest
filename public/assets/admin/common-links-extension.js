/**
 * v2board 常用导航管理扩展
 * 在后台菜单中添加常用导航管理功能
 */

(function() {
    'use strict';
    
    // 防止重复加载
    if (window.commonLinksExtensionLoaded) {
        return;
    }
    window.commonLinksExtensionLoaded = true;
    
    // 配置
    const CONFIG = {
        menuTitle: '常用导航',
        menuIcon: 'si si-compass',
        adminUrl: '/common-links-admin.html',
        insertAfter: '福利导航', // 在福利导航后面插入
        maxRetries: 50,
        retryInterval: 200
    };
    
    let retryCount = 0;
    let menuInserted = false;
    
    /**
     * 查找菜单容器
     */
    function findMenuContainer() {
        // v2board 使用自定义导航结构
        const selectors = [
            'ul.nav-main',
            '.nav-main',
            'ul.sidebar-nav',
            '.sidebar-nav ul',
            'nav ul',
            '.navigation ul'
        ];
        
        for (const selector of selectors) {
            const container = document.querySelector(selector);
            if (container) {
                console.log('[常用导航扩展] 找到菜单容器:', selector);
                return container;
            }
        }
        
        return null;
    }
    
    /**
     * 查找福利导航菜单项
     */
    function findNavLinksMenuItem() {
        const menuContainer = findMenuContainer();
        if (!menuContainer) return null;
        
        const menuItems = menuContainer.querySelectorAll('li');
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('福利导航') || text.includes('nav_links') || text.includes('Nav Links')) {
                console.log('[常用导航扩展] 找到福利导航菜单项');
                return item;
            }
        }
        
        return null;
    }
    
    /**
     * 创建常用导航菜单项
     */
    function createCommonLinksMenuItem() {
        const li = document.createElement('li');
        li.className = 'nav-main-item';
        
        const a = document.createElement('a');
        a.className = 'nav-main-link';
        a.href = CONFIG.adminUrl;
        a.target = '_blank';
        
        const icon = document.createElement('i');
        icon.className = CONFIG.menuIcon;
        
        const span = document.createElement('span');
        span.className = 'nav-main-link-name';
        span.textContent = CONFIG.menuTitle;
        
        a.appendChild(icon);
        a.appendChild(span);
        li.appendChild(a);
        
        // 添加点击事件
        a.addEventListener('click', function(e) {
            e.preventDefault();
            window.open(CONFIG.adminUrl, '_blank');
        });
        
        console.log('[常用导航扩展] 创建菜单项成功');
        return li;
    }
    
    /**
     * 插入常用导航菜单项
     */
    function insertCommonLinksMenuItem() {
        if (menuInserted) {
            return true;
        }
        
        const navLinksItem = findNavLinksMenuItem();
        if (!navLinksItem) {
            console.log('[常用导航扩展] 未找到福利导航菜单项，尝试插入到菜单末尾');
            
            const menuContainer = findMenuContainer();
            if (menuContainer) {
                const commonLinksItem = createCommonLinksMenuItem();
                menuContainer.appendChild(commonLinksItem);
                menuInserted = true;
                console.log('[常用导航扩展] 菜单项已插入到末尾');
                return true;
            }
            return false;
        }
        
        // 在福利导航后面插入
        const commonLinksItem = createCommonLinksMenuItem();
        navLinksItem.parentNode.insertBefore(commonLinksItem, navLinksItem.nextSibling);
        menuInserted = true;
        
        console.log('[常用导航扩展] 菜单项已插入到福利导航后面');
        return true;
    }
    
    /**
     * 检查是否已存在常用导航菜单
     */
    function checkExistingMenuItem() {
        const menuContainer = findMenuContainer();
        if (!menuContainer) return false;
        
        const menuItems = menuContainer.querySelectorAll('li');
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('常用导航') || text.includes('common_links') || text.includes('Common Links')) {
                console.log('[常用导航扩展] 菜单项已存在，跳过插入');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 尝试插入菜单项
     */
    function tryInsertMenuItem() {
        if (retryCount >= CONFIG.maxRetries) {
            console.warn('[常用导航扩展] 达到最大重试次数，停止尝试');
            return;
        }
        
        retryCount++;
        
        // 检查是否已存在
        if (checkExistingMenuItem()) {
            return;
        }
        
        // 尝试插入
        if (insertCommonLinksMenuItem()) {
            console.log('[常用导航扩展] 菜单项插入成功');
            
            // 监听路由变化，确保菜单项持续存在
            setupRouteChangeListener();
            return;
        }
        
        // 继续重试
        setTimeout(tryInsertMenuItem, CONFIG.retryInterval);
    }
    
    /**
     * 监听路由变化
     */
    function setupRouteChangeListener() {
        // 监听 pushState 和 replaceState
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;
        
        history.pushState = function() {
            originalPushState.apply(history, arguments);
            setTimeout(() => {
                if (!checkExistingMenuItem()) {
                    menuInserted = false;
                    tryInsertMenuItem();
                }
            }, 500);
        };
        
        history.replaceState = function() {
            originalReplaceState.apply(history, arguments);
            setTimeout(() => {
                if (!checkExistingMenuItem()) {
                    menuInserted = false;
                    tryInsertMenuItem();
                }
            }, 500);
        };
        
        // 监听 popstate 事件
        window.addEventListener('popstate', function() {
            setTimeout(() => {
                if (!checkExistingMenuItem()) {
                    menuInserted = false;
                    tryInsertMenuItem();
                }
            }, 500);
        });
        
        // 使用 MutationObserver 监听 DOM 变化
        const observer = new MutationObserver(function(mutations) {
            let shouldCheck = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // 检查是否有菜单相关的变化
                    const addedNodes = Array.from(mutation.addedNodes);
                    const removedNodes = Array.from(mutation.removedNodes);
                    
                    const hasMenuChanges = [...addedNodes, ...removedNodes].some(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            return node.matches && (
                                node.matches('ul.nav-main, .nav-main, li.nav-main-item') ||
                                node.querySelector && node.querySelector('ul.nav-main, .nav-main, li.nav-main-item')
                            );
                        }
                        return false;
                    });
                    
                    if (hasMenuChanges) {
                        shouldCheck = true;
                    }
                }
            });
            
            if (shouldCheck) {
                setTimeout(() => {
                    if (!checkExistingMenuItem()) {
                        menuInserted = false;
                        tryInsertMenuItem();
                    }
                }, 300);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('[常用导航扩展] 路由变化监听器已设置');
    }
    
    /**
     * 初始化扩展
     */
    function init() {
        console.log('[常用导航扩展] 开始初始化');
        
        // 等待页面加载完成
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', tryInsertMenuItem);
        } else {
            tryInsertMenuItem();
        }
        
        // 额外的延迟检查，确保在动态加载的情况下也能工作
        setTimeout(tryInsertMenuItem, 1000);
        setTimeout(tryInsertMenuItem, 3000);
    }
    
    // 启动扩展
    init();
    
})();