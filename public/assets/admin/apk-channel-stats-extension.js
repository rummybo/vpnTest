/**
 * APK 渠道统计后台扩展
 * 在 v2board 后台（admin.blade 渲染的 SPA）动态插入菜单项，跳转到 Laravel 后台路由
 */
(function(){
  'use strict';

  if (window.apkChannelStatsExtensionLoaded) return;
  window.apkChannelStatsExtensionLoaded = true;

  const CONFIG = {
    groupTitle: 'APK 渠道统计',
    items: [
      { title: '统计列表', icon: 'si si-list', url: '/admin/apk-channel-stats' },
      { title: '汇总与图表', icon: 'si si-bar-chart', url: '/admin/apk-channel-stats/summary' },
      { title: '实时仪表盘', icon: 'si si-speedometer', url: '/admin/apk-channel-stats/dashboard' }
    ],
    maxRetries: 50,
    retryInterval: 200
  };

  function enabled(){
    if (window.settings && typeof window.settings.apk_channel_stats_enable !== 'undefined') {
      return window.settings.apk_channel_stats_enable === 1 || window.settings.apk_channel_stats_enable === '1';
    }
    return true;
  }

  function findMenuContainer(){
    const selectors = ['ul.nav-main', '.nav-main', 'ul.sidebar-nav', '.sidebar-nav ul', 'nav ul', '.navigation ul'];
    for (const s of selectors) {
      const el = document.querySelector(s);
      if (el) return el;
    }
    return null;
  }

  function menuItemExists(text){
    const container = findMenuContainer();
    if (!container) return false;
    const items = container.querySelectorAll('li');
    for (const li of items){
      const t = (li.textContent || '').trim();
      if (t.includes(text)) return true;
    }
    return false;
  }

  function createHeading(title){
    const li = document.createElement('li');
    li.className = 'nav-main-heading';
    li.textContent = title;
    return li;
  }

  function createItem(title, icon, url){
    const li = document.createElement('li');
    li.className = 'nav-main-item';
    const a = document.createElement('a');
    a.className = 'nav-main-link';
    a.href = url;
    const i = document.createElement('i');
    i.className = 'nav-main-link-icon ' + icon;
    const span = document.createElement('span');
    span.className = 'nav-main-link-name';
    span.textContent = title;
    a.appendChild(i);
    a.appendChild(span);
    li.appendChild(a);
    return li;
  }

  let inserted = false;
  let retries = 0;

  function insertMenu(){
    if (inserted) return true;
    const container = findMenuContainer();
    if (!container) return false;

    // 若已存在任一项则认为已插入
    if (menuItemExists(CONFIG.items[0].title)) { inserted = true; return true; }

    // 插入分组标题
    const heading = createHeading(CONFIG.groupTitle);
    container.appendChild(heading);

    // 插入三个子项
    for (const it of CONFIG.items){
      const node = createItem(it.title, it.icon, it.url);
      container.appendChild(node);
    }

    inserted = true;
    return true;
  }

  function tryInsert(){
    if (retries >= CONFIG.maxRetries) return;
    retries++;
    if (insertMenu()) { setupObservers(); return; }
    setTimeout(tryInsert, CONFIG.retryInterval);
  }

  function setupObservers(){
    // 监听路由变化，确保菜单保留
    const onRoute = function(){
      setTimeout(function(){ if (!menuItemExists(CONFIG.items[0].title)) { inserted = false; tryInsert(); } }, 600);
    };
    const op = history.pushState;
    history.pushState = function(){ op.apply(history, arguments); onRoute(); };
    const orp = history.replaceState;
    history.replaceState = function(){ orp.apply(history, arguments); onRoute(); };
    window.addEventListener('popstate', onRoute);

    // 监听 DOM 变更
    const obs = new MutationObserver(function(){
      if (!menuItemExists(CONFIG.items[0].title)) { inserted = false; tryInsert(); }
    });
    obs.observe(document.body, { childList: true, subtree: true });
  }

  function init(){
    if (!enabled()) return;
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', tryInsert);
    } else {
      tryInsert();
    }
    setTimeout(tryInsert, 1000);
    setTimeout(tryInsert, 3000);
  }

  init();
})();
