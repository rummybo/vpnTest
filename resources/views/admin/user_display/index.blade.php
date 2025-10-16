<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <title>用户显示 - {{config('v2board.app_name', 'V2Board')}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f5f5f5; 
            color: #333;
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }
        .header { 
            padding: 20px; 
            border-bottom: 1px solid #e8e8e8; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .header h1 { 
            margin: 0; 
            font-size: 24px; 
            font-weight: 500;
        }
        .toolbar { 
            padding: 16px 20px; 
            display: flex; 
            gap: 12px; 
            align-items: center; 
            border-bottom: 1px solid #e8e8e8; 
            background: #fafafa;
        }
        .search-form { 
            display: flex; 
            gap: 8px; 
            align-items: center; 
            flex: 1; 
        }
        .search-input { 
            padding: 8px 12px; 
            border: 1px solid #d9d9d9; 
            border-radius: 6px; 
            width: 300px; 
            font-size: 14px; 
            transition: border-color 0.3s;
        }
        .search-input:focus { 
            outline: none; 
            border-color: #1890ff; 
            box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2); 
        }
        .btn { 
            padding: 8px 16px; 
            border: 1px solid #d9d9d9; 
            background: #fff; 
            border-radius: 6px; 
            cursor: pointer; 
            text-decoration: none; 
            color: #333; 
            font-size: 14px; 
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
        }
        .btn:hover { 
            background: #f5f5f5; 
            border-color: #40a9ff; 
            transform: translateY(-1px);
        }
        .btn-primary { 
            background: #1890ff; 
            border-color: #1890ff; 
            color: #fff; 
        }
        .btn-primary:hover { 
            background: #40a9ff; 
            border-color: #40a9ff; 
        }
        .btn-success {
            background: #52c41a;
            border-color: #52c41a;
            color: #fff;
        }
        .btn-success:hover {
            background: #73d13d;
            border-color: #73d13d;
        }
        .table-container { 
            overflow-x: auto; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th, td { 
            padding: 12px 16px; 
            text-align: left; 
            border-bottom: 1px solid #f0f0f0; 
        }
        th { 
            background: #fafafa; 
            font-weight: 600; 
            color: #333; 
            position: sticky;
            top: 0;
            z-index: 1;
        }
        tbody tr:hover { 
            background: #f5f5f5; 
        }
        .pagination { 
            padding: 20px; 
            display: flex; 
            justify-content: center; 
        }
        .stats { 
            padding: 16px 20px; 
            color: #666; 
            font-size: 14px; 
            background: #f9f9f9;
            border-bottom: 1px solid #e8e8e8;
        }
        .stats strong {
            color: #1890ff;
        }
        .empty-state { 
            padding: 60px 20px; 
            text-align: center; 
            color: #999; 
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        .user-id {
            font-weight: 600;
            color: #1890ff;
        }
        .user-email {
            color: #333;
            word-break: break-all;
        }
        .user-phone {
            color: #666;
        }
        .user-username {
            color: #333;
            font-weight: 500;
        }
        .user-ip {
            font-family: monospace;
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
        .user-time {
            color: #666;
            font-size: 13px;
        }
        .icon {
            width: 16px;
            height: 16px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span class="icon">👥</span>
                用户显示管理
            </h1>
        </div>
        
        @if($users->total() > 0)
            <div class="stats">
                共找到 <strong>{{ number_format($users->total()) }}</strong> 个用户
                @if(request('keyword'))
                    ，搜索关键词："<strong>{{ request('keyword') }}</strong>"
                @endif
            </div>
        @endif
        
        <div class="toolbar">
            <form method="get" action="{{ route('admin.user_display.index') }}" class="search-form">
                <input type="text" 
                       name="keyword" 
                       value="{{ request('keyword') }}" 
                       placeholder="搜索 Email / 手机号 / 用户名" 
                       class="search-input" />
                <button type="submit" class="btn btn-primary">
                    <span class="icon">🔍</span>
                    搜索
                </button>
                <a class="btn" href="{{ route('admin.user_display.index') }}">
                    <span class="icon">🔄</span>
                    重置
                </a>
                <a class="btn btn-success" href="{{ route('admin.user_display.export', request()->query()) }}">
                    <span class="icon">📊</span>
                    导出 CSV
                </a>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th style="width: 200px;">Email</th>
                    <th style="width: 120px;">手机号</th>
                    <th style="width: 150px;">用户名</th>
                    <th style="width: 140px;">最后登录IP</th>
                    <th style="width: 160px;">注册时间</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="user-id">{{ $user->id }}</td>
                        <td class="user-email">{{ $user->email ?: '-' }}</td>
                        <td class="user-phone">{{ $user->phone ?: '-' }}</td>
                        <td class="user-username">{{ $user->username ?: '-' }}</td>
                        <td>
                            @if($user->last_login_ip)
                                <span class="user-ip">{{ $user->last_login_ip }}</span>
                            @else
                                <span style="color: #ccc;">-</span>
                            @endif
                        </td>
                        <td class="user-time">
                            @if($user->created_at)
                                {{ is_numeric($user->created_at) ? date('Y-m-d H:i:s', (int)$user->created_at) : \Carbon\Carbon::parse($user->created_at)->format('Y-m-d H:i:s') }}
                            @else
                                <span style="color: #ccc;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            @if(request('keyword'))
                                <div>未找到匹配的用户记录</div>
                                <div style="margin-top: 8px; font-size: 12px; color: #999;">
                                    尝试使用其他关键词或 <a href="{{ route('admin.user_display.index') }}" style="color: #1890ff;">清除搜索条件</a>
                                </div>
                            @else
                                <div>暂无用户数据</div>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="pagination">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <script>
        // 简单的搜索框回车提交
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
        
        // 导出按钮点击提示
        document.querySelector('a[href*="export"]').addEventListener('click', function() {
            // 可以在这里添加导出进度提示
            console.log('开始导出用户数据...');
        });
    </script>
</body>
</html>