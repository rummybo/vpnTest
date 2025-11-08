<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', '后台管理') - {{ config('app.name', 'V2Board') }}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/admin-lte/2.4.18/css/skins/_all-skins.min.css">
    
    <style>
        /* 自定义样式 */
        .content-wrapper {
            min-height: 100vh;
        }
        
        .main-header {
            display: none;
        }
        
        .main-header .navbar {
            margin-left: 0;
        }
        
        .content-wrapper, .right-side, .main-footer {
            margin-left: 0;
            padding-top: 0;
        }
        
        .main-sidebar {
            display: none;
        }
        
        .navbar-nav > li > a {
            padding-top: 15px;
            padding-bottom: 15px;
        }
        
        .navbar-brand {
            padding: 15px;
            font-size: 18px;
            line-height: 20px;
        }
    </style>
    
    @yield('styles')
</head>

<body class="hold-transition skin-blue layout-top-nav">
    <div class="wrapper">
        @hasSection('no_header')
        @else
        <!-- Main Header -->
        <header class="main-header">
            <nav class="navbar navbar-static-top">
                <div class="container">
                    <div class="navbar-header">
                        <a href="#" class="navbar-brand">
                            <b>{{ config('app.name', 'V2Board') }}</b> 后台管理
                        </a>
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                            <i class="fa fa-bars"></i>
                        </button>
                    </div>

                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                        <ul class="nav navbar-nav">
                            <li><a href="{{ url('/admin') }}">首页</a></li>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    导航管理 <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="{{ url('/admin/nav_links') }}">福利导航</a></li>
                                    <li><a href="{{ url('/admin/common_links') }}">常用导航</a></li>
                                    <li><a href="{{ url('/admin/frontend_nav_pages') }}">前端导航页</a></li>
                                    <li><a href="{{ url('/admin/maintenance_notices') }}">维护通知</a></li>
                                    <li role="separator" class="divider"></li>
                                    <li><a href="{{ url('/admin/apk-channel-stats') }}">APK 渠道统计</a></li>
                                    <li><a href="{{ url('/admin/apk-channel-stats/summary') }}">APK 渠道汇总</a></li>
                                    <li><a href="{{ url('/admin/apk-channel-stats/dashboard') }}">APK 实时仪表盘</a></li>
                                </ul>
                            </li>
                            <li><a href="{{ url('/admin/user_display') }}">用户显示</a></li>
                            <li><a href="{{ url('/admin/system_configs') }}">系统配置</a></li>
                        </ul>
                    </div>

                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <li class="dropdown user user-menu">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <span class="hidden-xs">管理员</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="user-footer">
                                        <div class="pull-right">
                                            <a href="#" class="btn btn-default btn-flat">退出</a>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        @endif

        <!-- Content Wrapper -->
        @yield('content')

        @hasSection('no_footer')
        @else
        <!-- Main Footer -->
        <footer class="main-footer">
            <div class="container">
                <div class="pull-right hidden-xs">
                    <b>Version</b> {{ config('app.version', '1.0.0') }}
                </div>
                <strong>Copyright &copy; {{ date('Y') }} <a href="#">{{ config('app.name', 'V2Board') }}</a>.</strong>
                All rights reserved.
            </div>
        </footer>
        @endif
    </div>

    <!-- jQuery 3 -->
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.bootcdn.net/ajax/libs/admin-lte/2.4.18/js/adminlte.min.js"></script>
    
    <script>
        // 设置CSRF token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // 全局设置，供其他脚本使用
        window.settings = {
            secure_path: '{{ config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key')))) }}'
        };
    </script>
    
    @yield('scripts')
</body>
</html>