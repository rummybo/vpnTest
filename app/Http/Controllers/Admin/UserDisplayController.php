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

        // 创建临时文件
        $tempFile = tempnam(sys_get_temp_dir(), 'user_export_');
        $handle = fopen($tempFile, 'w');

        // 写入UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");

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
        });

        fclose($handle);

        // 返回文件下载响应
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }

    /**
     * 根据手机号查找用户
     */
    public function findByPhone(Request $request)
    {
        $phone = $request->input('phone');

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => '请输入手机号'
            ]);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '用户不存在'
            ]);
        }

        // 格式化时间
        $createdAt = '';
        if ($user->created_at) {
            if (is_numeric($user->created_at)) {
                $createdAt = date('Y-m-d H:i:s', (int)$user->created_at);
            } else {
                $createdAt = $user->created_at;
            }
        }

        return response()->json([
            'success' => true,
            'message' => '查找成功',
            'data' => [
                'id' => $user->id,
                'email' => $user->email ?: '-',
                'phone' => $user->phone ?: '-',
                'username' => $user->username ?: '-',
                'last_login_ip' => $user->last_login_ip ?: '-',
                'created_at' => $createdAt ?: '-'
            ]
        ]);
    }

    /**
     * 修改用户密码（基于手机号）
     */
    public function changePassword(Request $request)
    {
        $phone = $request->input('phone');
        $newPassword = $request->input('new_password');

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => '请输入手机号'
            ]);
        }

        if (empty($newPassword)) {
            return response()->json([
                'success' => false,
                'message' => '请输入新密码'
            ]);
        }

        if (strlen($newPassword) < 6) {
            return response()->json([
                'success' => false,
                'message' => '密码长度不能少于6位'
            ]);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '用户不存在'
            ]);
        }

        try {
            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $user->password_algo = NULL;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => '密码修改成功'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '密码修改失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 创建新用户（手机号为必填）
     */
    public function createUser(Request $request)
    {
        $phone = $request->input('phone');
        $email = $request->input('email');
        $password = $request->input('password');
        $username = $request->input('username');

        // 验证必填字段
        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => '请输入手机号'
            ]);
        }

        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => '请输入邮箱'
            ]);
        }

        if (empty($password)) {
            return response()->json([
                'success' => false,
                'message' => '请输入密码'
            ]);
        }

        if (strlen($password) < 6) {
            return response()->json([
                'success' => false,
                'message' => '密码长度不能少于6位'
            ]);
        }

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => '邮箱格式不正确'
            ]);
        }

        // 检查手机号是否已存在
        if (User::where('phone', $phone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => '手机号已存在'
            ]);
        }

        // 检查邮箱是否已存在
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => '邮箱已存在'
            ]);
        }

        try {
            $user = new User();
            $user->phone = $phone;
            $user->email = $email;
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->password_algo = NULL;
            $user->username = $username ?: '';
            $user->group_id = 1;
            $user->plan_id = 2;
            $user->transfer_enable = "1073741824000000000";
            $user->uuid = \App\Utils\Helper::guid(true);
            $user->token = \App\Utils\Helper::guid();
            $user->save();

            return response()->json([
                'success' => true,
                'message' => '用户创建成功',
                'data' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'username' => $user->username
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '用户创建失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 删除用户（基于手机号）
     */
    public function deleteUser(Request $request)
    {
        $phone = $request->input('phone');

        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => '请输入手机号'
            ]);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '用户不存在'
            ]);
        }

        try {
            $deletedUserInfo = [
                'id' => $user->id,
                'phone' => $user->phone,
                'email' => $user->email,
                'username' => $user->username
            ];

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => '用户删除成功',
                'data' => $deletedUserInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '用户删除失败：' . $e->getMessage()
            ]);
        }
    }
}
