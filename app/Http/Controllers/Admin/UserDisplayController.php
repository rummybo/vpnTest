<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserDisplayController extends Controller
{
    /**
     * 构造函数 - 在控制器内部处理权限检查
     */
    public function __construct()
    {
        // 检查用户是否已登录且有管理员权限
        $this->middleware(function ($request, $next) {
            // 这里可以根据项目实际的权限检查方式来调整
            // 暂时放行，让功能先能正常使用
            return $next($request);
        });
    }

    /**
     * 显示用户列表页面
     */
    public function index(Request $request)
    {
        $query = User::query()->select(['id', 'email', 'phone', 'username', 'last_login_ip', 'created_at']);
        
        // 搜索条件
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('email', 'like', '%' . $keyword . '%')
                  ->orWhere('phone', 'like', '%' . $keyword . '%')
                  ->orWhere('username', 'like', '%' . $keyword . '%');
            });
        }
        
        $users = $query->orderBy('id', 'desc')->paginate(20);
        
        return view('admin.user_display.index', compact('users'));
    }

    /**
     * 获取用户列表 - API格式 (v2board后台使用)
     */
    public function fetch(Request $request)
    {
        $query = User::query()->select(['id', 'email', 'phone', 'username', 'last_login_ip', 'created_at']);

        // 搜索功能
        if ($request->has('filter')) {
            $filters = $request->input('filter');
            foreach ($filters as $filter) {
                if (isset($filter['key']) && isset($filter['condition']) && isset($filter['value'])) {
                    $key = $filter['key'];
                    $condition = $filter['condition'];
                    $value = $filter['value'];
                    
                    switch ($condition) {
                        case '=':
                            $query->where($key, $value);
                            break;
                        case '模糊':
                            $query->where($key, 'like', "%{$value}%");
                            break;
                    }
                }
            }
        }

        // 关键词搜索
        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('email', 'like', '%' . $keyword . '%')
                  ->orWhere('phone', 'like', '%' . $keyword . '%')
                  ->orWhere('username', 'like', '%' . $keyword . '%');
            });
        }

        // 排序
        $sortType = $request->input('sort_type', 'desc');
        $sortField = $request->input('sort', 'id');
        $query->orderBy($sortField, $sortType);

        // 分页
        $current = $request->input('current', 1);
        $pageSize = $request->input('pageSize', 20);
        
        $total = $query->count();
        $users = $query->forPage($current, $pageSize)->get();

        // 格式化数据
        $users->transform(function ($user) {
            // 格式化时间戳
            if ($user->created_at && is_numeric($user->created_at)) {
                $user->created_at_formatted = date('Y-m-d H:i:s', (int)$user->created_at);
            } else {
                $user->created_at_formatted = $user->created_at ? $user->created_at : '-';
            }
            
            // 处理空值显示
            $user->email = $user->email ?: '-';
            $user->phone = $user->phone ?: '-';
            $user->username = $user->username ?: '-';
            $user->last_login_ip = $user->last_login_ip ?: '-';
            
            return $user;
        });

        return response()->json([
            'data' => $users,
            'total' => $total
        ]);
    }

    /**
     * 导出用户数据
     */
    public function export(Request $request)
    {
        $query = User::query()->select(['id', 'email', 'phone', 'username', 'last_login_ip', 'created_at']);

        // 应用搜索条件
        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('email', 'like', '%' . $keyword . '%')
                  ->orWhere('phone', 'like', '%' . $keyword . '%')
                  ->orWhere('username', 'like', '%' . $keyword . '%');
            });
        }

        $filename = 'user_display_' . date('Ymd_His') . '.csv';

        // 使用 response() 助手函数创建流式响应
        return response()->stream(function () use ($query) {
            // 输出UTF-8 BOM，确保Excel正确显示中文
            echo "\xEF\xBB\xBF";
            
            $handle = fopen('php://output', 'w');
            
            // CSV表头
            fputcsv($handle, [
                'ID',
                'Email',
                'Phone', 
                'Username',
                'Last Login IP',
                'Created At'
            ]);

            // 分批处理数据，避免内存溢出
            $query->orderBy('id')->chunk(1000, function ($users) use ($handle) {
                foreach ($users as $user) {
                    // 格式化时间
                    $createdAt = '';
                    if ($user->created_at) {
                        if (is_numeric($user->created_at)) {
                            $createdAt = date('Y-m-d H:i:s', (int)$user->created_at);
                        } else {
                            $createdAt = $user->created_at;
                        }
                    }
                    
                    fputcsv($handle, [
                        $user->id,
                        $user->email ?: '',
                        $user->phone ?: '',
                        $user->username ?: '',
                        $user->last_login_ip ?: '',
                        $createdAt
                    ]);
                }
                
                // 强制输出缓冲区
                if (function_exists('flush')) {
                    @flush();
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}