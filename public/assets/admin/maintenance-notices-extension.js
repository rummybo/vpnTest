/**
 * v2board 维护通知管理扩展
 * 在后台菜单中添加维护通知管理入口
 */

(function() {
    'use strict';

    if (window.maintenanceNoticesExtensionLoaded) {
        return;
    }
    window.maintenanceNoticesExtensionLoaded = true;

    const CONFIG = {
        menuTitle: '维护通知',
        menuIcon: 'si si-bulb',
        // 直接跳转到 Laravel Blade 后台页
        adminUrl: '/admin/maintenance_notices',
        insertAfterText: '前端导航页',
        maxRetries: 50,
        retryInterval: 200
    };

    function isEnabled() {
        if (window.settings && typeof window.settings.maintenance_notices_enable !== 'undefined') {
            const v = window.settings.maintenance_notices_enable;
            return v === 1 || v === '1' || v === true;
        }
        return true;
    }

    function findMenuContainer() {
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
            if (container) return container;
        }
        return null;
    }

    function findInsertAfterItem(container) {
        if (!container) return null;
        const items = container.querySelectorAll('li');
        for (const item of items) {
            const text = (item.textContent || '').trim();
            if (text.includes(CONFIG.insertAfterText) || text.includes('Frontend Nav')) {
                return item;
            }
        }
        // fallback: 福利导航或常用导航后
        for (const item of items) {
            const text = (item.textContent || '').trim();
            if (text.includes('常用导航') || text.includes('福利导航')) {
                return item;
            }
        }
        return null;
    }

    function existingMenuItem(container) {
        if (!container) return false;
        const links = container.querySelectorAll('a');
        for (const a of links) {
            if (a.href && a.href.endsWith(CONFIG.adminUrl)) return true;
            const text = (a.textContent || '').trim();
            if (text.includes(CONFIG.menuTitle)) return true;
        }
        return false;
    }

    function createMenuItem() {
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
        a.addEventListener('click', function(e) {
            // 直接新窗口打开 Blade 管理页
        });
        return li;
    }

    let retries = 0;
    function tryInsert() {
        const container = findMenuContainer();
        if (!container) {
            if (retries++ < CONFIG.maxRetries) return setTimeout(tryInsert, CONFIG.retryInterval);
            return;
        }
        if (existingMenuItem(container)) return;
        const afterItem = findInsertAfterItem(container);
        const li = createMenuItem();
        if (afterItem && afterItem.parentNode) {
            afterItem.parentNode.insertBefore(li, afterItem.nextSibling);
        } else {
            container.appendChild(li);
        }
    }

    function setupObservers() {
        // 监听路由变化，确保菜单项存在
        window.addEventListener('hashchange', function() {
            setTimeout(tryInsert, 800);
        });
        window.addEventListener('popstate', function() {
            setTimeout(tryInsert, 800);
        });
        const observer = new MutationObserver(function() {
            setTimeout(tryInsert, 800);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function init() {
        if (!isEnabled()) return;
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                tryInsert();
                setupObservers();
            });
        } else {
            setTimeout(function() {
                tryInsert();
                setupObservers();
            }, 1000);
        }
    }

    init();
})();