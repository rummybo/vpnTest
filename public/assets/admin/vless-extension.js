/**
 * v2board VLESS 节点管理扩展
 * 在后台菜单中添加“VLESS 节点管理”入口，链接到 /server-vless-admin.html
 */
(function() {
  'use strict';

  if (window.vlessExtensionLoaded) return; 
  window.vlessExtensionLoaded = true;

  const CONFIG = {
    menuTitle: 'VLESS 测试',
    menuIcon: 'si si-layers',
    adminUrl: '/server-vless-admin.html',
    maxRetries: 40,
    retryInterval: 250
  };

  let retryCount = 0;
  let menuInserted = false;

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
      const el = document.querySelector(selector);
      if (el) return el;
    }
    return null;
  }

  function checkExistingItem() {
    const container = findMenuContainer();
    if (!container) return false;
    const items = container.querySelectorAll('li');
    for (const item of items) {
      const text = (item.textContent || '').trim();
      if (text.includes('VLESS 节点管理')) return true;
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
    icon.className = 'nav-main-link-icon ' + CONFIG.menuIcon;
    const span = document.createElement('span');
    span.className = 'nav-main-link-name';
    span.textContent = CONFIG.menuTitle;
    a.appendChild(icon);
    a.appendChild(span);
    li.appendChild(a);
    a.addEventListener('click', function(e){
      e.preventDefault();
      window.open(CONFIG.adminUrl, '_blank');
    });
    return li;
  }

  function insertMenuItem() {
    if (menuInserted) return true;
    const container = findMenuContainer();
    if (!container) return false;
    const item = createMenuItem();
    // 插入到菜单末尾，避免依赖具体分组/标题
    container.appendChild(item);
    menuInserted = true;
    return true;
  }

  function tryInsert() {
    if (retryCount >= CONFIG.maxRetries) return;
    retryCount++;
    if (checkExistingItem()) return;
    if (insertMenuItem()) {
      setupRouteGuard();
      return;
    }
    setTimeout(tryInsert, CONFIG.retryInterval);
  }

  function setupRouteGuard() {
    const ensureExists = () => {
      if (!checkExistingItem()) {
        menuInserted = false;
        tryInsert();
      }
    };
    const origPush = history.pushState;
    const origReplace = history.replaceState;
    history.pushState = function(){ origPush.apply(history, arguments); setTimeout(ensureExists, 600); };
    history.replaceState = function(){ origReplace.apply(history, arguments); setTimeout(ensureExists, 600); };
    window.addEventListener('popstate', () => setTimeout(ensureExists, 600));

    const observer = new MutationObserver((mutations) => {
      let shouldCheck = false;
      for (const m of mutations) {
        if (m.type === 'childList') {
          const nodes = [...m.addedNodes, ...m.removedNodes];
          if (nodes.some(n => n.nodeType === 1 && (n.matches?.('ul.nav-main, .nav-main, li.nav-main-item') || n.querySelector?.('ul.nav-main, .nav-main, li.nav-main-item')))) {
            shouldCheck = true;
          }
        }
      }
      if (shouldCheck) setTimeout(ensureExists, 600);
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  function init() {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', tryInsert);
    } else {
      setTimeout(tryInsert, 800);
    }
  }

  init();
})();