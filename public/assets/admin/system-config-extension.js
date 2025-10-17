/**
 * v2board 系统配置管理扩展
 * 在后台菜单中添加系统配置管理功能
 */

(function() {
    'use strict';
    
    // 防止重复加载
    if (window.systemConfigExtensionLoaded) {
        return;
    }
    window.systemConfigExtensionLoaded = true;
    
    // 配置
    const CONFIG = {
        menuTitle: '系统配置管理',
        menuIcon: 'si si-settings',
        adminUrl: '/system-config-admin.html',
        insertAfter: '用户显示', // 在用户显示后面插入
        maxRetries: 50,
        retryInterval: 200
    };
    
    let retryCount = 0;
    let menuInserted = false;
    
    /**
     * 查找菜单容器
     */
    function findMenuContainer() {
        console.log('[系统配置管理扩展] 开始查找菜单容器...');
        
        // v2board 使用自定义导航结构
        const selectors = [
            'ul.nav-main',
            '.nav-main',
            'ul.sidebar-nav',
            '.sidebar-nav ul',
            'nav ul',
            '.navigation ul',
            'ul[class*="nav"]',
            '.sidebar ul',
            '#sidebar ul'
        ];
        
        for (const selector of selectors) {
            const container = document.querySelector(selector);
            if (container) {
                console.log('[系统配置管理扩展] 找到菜单容器:', selector, container);
                return container;
            }
        }
        
        // 如果还没找到，尝试查找任何包含现有菜单项的ul元素
        const allUls = document.querySelectorAll('ul');
        for (const ul of allUls) {
            const menuItems = ul.querySelectorAll('li');
            for (const item of menuItems) {
                const text = item.textContent || '';
                if (text.includes('福利导航') || text.includes('常用导航') || text.includes('前端导航页') || text.includes('用户显示')) {
                    console.log('[系统配置管理扩展] 通过现有菜单项找到容器:', ul);
                    return ul;
                }
            }
        }
        
        console.warn('[系统配置管理扩展] 未找到菜单容器');
        return null;
    }
    
    /**
     * 查找用户显示菜单项
     */
    function findLastNavMenuItem() {
        const menuContainer = findMenuContainer();
        if (!menuContainer) return null;
        
        const menuItems = menuContainer.querySelectorAll('li');
        
        // 先找用户显示
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('用户显示') || text.includes('user_display') || text.includes('User Display')) {
                console.log('[系统配置管理扩展] 找到用户显示菜单项');
                return item;
            }
        }
        
        // 如果没找到用户显示，找前端导航页
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('前端导航页') || text.includes('frontend_nav_pages') || text.includes('Frontend Nav')) {
                console.log('[系统配置管理扩展] 找到前端导航页菜单项');
                return item;
            }
        }
        
        // 如果没找到前端导航页，找常用导航
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('常用导航') || text.includes('common_links') || text.includes('Common Links')) {
                console.log('[系统配置管理扩展] 找到常用导航菜单项');
                return item;
            }
        }
        
        // 如果都没找到，找福利导航
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('福利导航') || text.includes('nav_links') || text.includes('Nav Links')) {
                console.log('[系统配置管理扩展] 找到福利导航菜单项');
                return item;
            }
        }
        
        return null;
    }
    
    /**
     * 创建系统配置管理菜单项
     */
    function createSystemConfigMenuItem() {
        const li = document.createElement('li');
        li.className = 'nav-main-item';
        
        const a = document.createElement('a');
        a.className = 'nav-main-link';
        a.href = CONFIG.adminUrl;
        a.target = '_blank';
        
        const icon = document.createElement('i');
        icon.className = 'nav-main-link-icon ' + CONFIG.menuIcon;
        
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
        
        console.log('[系统配置管理扩展] 创建菜单项成功');
        return li;
    }
    
    /**
     * 插入系统配置管理菜单项
     */
    function insertSystemConfigMenuItem() {
        if (menuInserted) {
            console.log('[系统配置管理扩展] 菜单项已插入，跳过');
            return true;
        }
        
        const lastNavItem = findLastNavMenuItem();
        if (!lastNavItem) {
            console.log('[系统配置管理扩展] 未找到导航菜单项，尝试插入到菜单末尾');
            
            const menuContainer = findMenuContainer();
            if (menuContainer) {
                const systemConfigItem = createSystemConfigMenuItem();
                menuContainer.appendChild(systemConfigItem);
                menuInserted = true;
                console.log('[系统配置管理扩展] 菜单项已插入到末尾');
                return true;
            }
            return false;
        }
        
        // 在找到的导航项后面插入
        const systemConfigItem = createSystemConfigMenuItem();
        lastNavItem.parentNode.insertBefore(systemConfigItem, lastNavItem.nextSibling);
        menuInserted = true;
        
        console.log('[系统配置管理扩展] 菜单项已插入到导航项后面');
        return true;
    }
    
    /**
     * 检查是否已存在系统配置管理菜单
     */
    function checkExistingMenuItem() {
        const menuContainer = findMenuContainer();
        if (!menuContainer) return false;
        
        const menuItems = menuContainer.querySelectorAll('li');
        for (const item of menuItems) {
            const text = item.textContent || '';
            if (text.includes('系统配置管理') || text.includes('system_config') || text.includes('System Config')) {
                console.log('[系统配置管理扩展] 菜单项已存在，跳过插入');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 尝试插入菜单项
     */
    function tryInsertMenuItem() {
        console.log(`[系统配置管理扩展] 第${retryCount + 1}次尝试插入菜单项...`);
        
        if (retryCount >= CONFIG.maxRetries) {
            console.warn('[系统配置管理扩展] 达到最大重试次数，停止尝试');
            return;
        }
        
        retryCount++;
        
        // 检查是否已存在
        if (checkExistingMenuItem()) {
            console.log('[系统配置管理扩展] 菜单项已存在，跳过插入');
            return;
        }
        
        // 尝试插入
        if (insertSystemConfigMenuItem()) {
            console.log('[系统配置管理扩展] 菜单项插入成功！');
            
            // 监听路由变化，确保菜单项持续存在
            setupRouteChangeListener();
            return;
        }
        
        console.log(`[系统配置管理扩展] 第${retryCount}次插入失败，${CONFIG.retryInterval}ms后重试...`);
        
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
        
        console.log('[系统配置管理扩展] 路由变化监听器已设置');
    }
    
    /**
     * 检查系统配置管理是否启用
     */
    function isSystemConfigEnabled() {
        // 从window.settings中读取配置
        if (window.settings && typeof window.settings.system_config_enable !== 'undefined') {
            return window.settings.system_config_enable == 1 || 
                   window.settings.system_config_enable == '1';
        }
        
        // 默认启用（如果没有配置）
        return true;
    }
    
    /**
     * 初始化扩展
     */
    function init() {
        console.log('[系统配置管理扩展] 开始初始化');
        
        // 检查是否启用
        if (!isSystemConfigEnabled()) {
            console.log('[系统配置管理扩展] 功能未启用');
            return;
        }
        
        console.log('[系统配置管理扩展] 功能已启用，准备添加菜单项');
        
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