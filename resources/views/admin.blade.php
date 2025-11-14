<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="/assets/admin/components.chunk.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/umi.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/custom.css?v={{$version}}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no">
    <title>{{$title}}</title>
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700"> -->
    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            theme: {
                sidebar: '{{$theme_sidebar}}',
                header: '{{$theme_header}}',
                color: '{{$theme_color}}',
            },
            version: '{{$version}}',
            background_url: '{{$background_url}}',
            logo: '{{$logo}}',
            secure_path: '{{$secure_path}}',
            nav_links_enable: {{config('v2board.nav_links_enable', 1)}},
            common_links_enable: {{config('v2board.common_links_enable', 1)}},
            frontend_nav_pages_enable: {{config('v2board.frontend_nav_pages_enable', 1)}},
            user_display_enable: {{config('v2board.user_display_enable', 1)}},
            system_config_enable: {{config('v2board.system_config_enable', 1)}},
            apk_channel_stats_enable: {{config('v2board.apk_channel_stats_enable', 1)}}
            ,maintenance_notices_enable: {{config('v2board.maintenance_notices_enable', 1)}}
            ,messages_enable: {{config('v2board.messages_enable', 1)}}
        }
    </script>
</head>

<body>
<div id="root"></div>
<script src="/assets/admin/vendors.async.js?v={{$version}}"></script>
<script src="/assets/admin/components.async.js?v={{$version}}"></script>
<script src="/assets/admin/umi.js?v={{$version}}"></script>
<!-- 福利导航扩展 -->
<script src="/assets/admin/nav-links-extension.js?v={{$version}}"></script>
<!-- 常用导航扩展 -->
<script src="/assets/admin/common-links-extension.js?v={{$version}}"></script>
<!-- 前端导航页扩展 -->
<script src="/assets/admin/frontend-nav-pages-extension.js?v={{$version}}"></script>
<!-- 用户显示扩展 -->
<script src="/assets/admin/user-display-extension.js?v={{$version}}"></script>
<!-- 系统配置管理扩展 -->
<script src="/assets/admin/system-config-extension.js?v={{$version}}"></script>
<!-- APK 渠道统计扩展 -->
<script src="/assets/admin/apk-channel-stats-extension.js?v={{$version}}"></script>
<!-- 维护通知扩展 -->
<script src="/assets/admin/maintenance-notices-extension.js?v={{$version}}"></script>
<!-- 消息中心扩展 -->
<script src="/assets/admin/messages-extension.js?v={{$version}}"></script>
<!-- VLESS 节点管理扩展 -->
<script src="/assets/admin/vless-extension.js?v={{$version}}"></script>
</body>

</html>
