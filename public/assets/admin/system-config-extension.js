(function() {
    // 防止重复加载
    if (window.systemConfigExtensionLoaded) {
        console.log('系统配置管理扩展已经加载过，跳过重复加载');
        return;
    }
    window.systemConfigExtensionLoaded = true;

    console.log('系统配置管理扩展开始加载...');

    // 检查配置是否启用
    if (!window.settings) {
        console.log('window.settings对象不存在');
        return;
    }
    
    console.log('window.settings.system_config_enable:', window.settings.system_config_enable);
    
    if (window.settings.system_config_enable !== 1 && window.settings.system_config_enable !== '1') {
        console.log('系统配置管理功能未启用');
        return;
    }

    // 配置对象
    const CONFIG = {
        title: '系统配置管理',
        icon: 'fa fa-cogs',
        url: '/admin/system_configs',
        menuSelectors: [
            'ul.nav-main',
            '.nav-main',
            'nav ul',
            '#sidebar ul',
            '.sidebar-menu',
            '#sidebar-menu',
            '.main-navigation ul',
            '.navigation ul'
        ]
    };

    // 初始化函数
    function init() {
        console.log('系统配置管理扩展初始化开始');

        // 查找菜单容器
        let menuContainer = null;
        let retryCount = 0;
        const maxRetries = 10;

        function findMenuContainer() {
            for (const selector of CONFIG.menuSelectors) {
                const element = document.querySelector(selector);
                if (element) {
                    console.log('找到菜单容器:', selector);
                    return element;
                }
            }
            return null;
        }

        function tryInit() {
            menuContainer = findMenuContainer();
            
            if (!menuContainer) {
                retryCount++;
                if (retryCount < maxRetries) {
                    console.log(`未找到菜单容器，第${retryCount}次重试 (${retryCount}/${maxRetries})`);
                    setTimeout(tryInit, 1000);
                } else {
                    console.log('达到最大重试次数，停止尝试添加菜单项');
                }
                return;
            }

            // 检查是否已存在该菜单项
            const existingItem = menuContainer.querySelector(`a[href="${CONFIG.url}"]`);
            if (existingItem) {
                console.log('系统配置管理菜单项已存在，跳过添加');
                return;
            }

            // 创建菜单项
            const menuItem = document.createElement('li');
            menuItem.className = 'nav-item system-config-menu-item';
            menuItem.innerHTML = `
                <a class="nav-link" href="${CONFIG.url}">
                    <i class="${CONFIG.icon}"></i>
                    <span>${CONFIG.title}</span>
                </a>
            `;

            // 尝试找到合适的插入位置（在用户管理相关菜单附近）
            const userMenus = menuContainer.querySelectorAll('a[href*="user"], a[href*="admin"]');
            let insertPosition = null;
            
            if (userMenus.length > 0) {
                // 在最后一个用户相关菜单后插入
                const lastUserMenu = userMenus[userMenus.length - 1];
                insertPosition = lastUserMenu.closest('li');
            }

            if (insertPosition && insertPosition.nextSibling) {
                menuContainer.insertBefore(menuItem, insertPosition.nextSibling);
            } else {
                menuContainer.appendChild(menuItem);
            }

            console.log('系统配置管理菜单项已添加');

            // 监听路由变化，确保菜单项持续存在
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        const currentItem = menuContainer.querySelector(`a[href="${CONFIG.url}"]`);
                        if (!currentItem) {
                            console.log('检测到菜单项丢失，重新添加');
                            if (insertPosition && insertPosition.nextSibling) {
                                menuContainer.insertBefore(menuItem, insertPosition.nextSibling);
                            } else {
                                menuContainer.appendChild(menuItem);
                            }
                        }
                    }
                });
            });

            observer.observe(menuContainer, { childList: true, subtree: true });
        }

        tryInit();
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    console.log('系统配置管理扩展加载完成');
})();