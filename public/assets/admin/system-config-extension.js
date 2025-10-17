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
    
    if (window.settings.system_config_enable != 1) {
        console.log('系统配置管理功能未启用');
        return;
    }

    // 配置对象
    const CONFIG = {
        title: '系统配置管理',
        icon: 'fa fa-cogs',
        url: '/admin/system/config',
        menuSelectors: [
            'ul.nav-main',
            '.nav-main',
            'nav ul',
            '#sidebar ul'
        ]
    };

    // 初始化函数
    function init() {
        console.log('系统配置管理扩展初始化开始');

        // 查找菜单容器
        let menuContainer = null;
        for (const selector of CONFIG.menuSelectors) {
            menuContainer = document.querySelector(selector);
            if (menuContainer) {
                console.log('找到菜单容器:', selector);
                break;
            }
        }

        if (!menuContainer) {
            console.log('未找到菜单容器，将在3秒后重试');
            setTimeout(init, 3000);
            return;
        }

        // 创建菜单项
        const menuItem = document.createElement('li');
        menuItem.className = 'nav-item';
        menuItem.innerHTML = `
            <a class="nav-link" href="${CONFIG.url}">
                <i class="${CONFIG.icon}"></i>
                <span>${CONFIG.title}</span>
            </a>
        `;

        // 插入到菜单中
        menuContainer.appendChild(menuItem);
        console.log('系统配置管理菜单项已添加');

        // 监听路由变化，确保菜单项持续存在
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const currentItem = menuContainer.querySelector(`a[href="${CONFIG.url}"]`);
                    if (!currentItem) {
                        console.log('检测到菜单项丢失，重新添加');
                        menuContainer.appendChild(menuItem);
                    }
                }
            });
        });

        observer.observe(menuContainer, { childList: true, subtree: true });
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    console.log('系统配置管理扩展加载完成');
})();