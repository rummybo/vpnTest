<?php

namespace App\Http\Controllers\Admin;

use App\Models\NavLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NavLinkController extends Controller
{
    /**
     * 显示导航链接列表
     */
    public function index(Request $request)
    {
        $query = NavLink::query();
        
        // 搜索条件
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $navLinks = $query->orderBy('sort', 'asc')
                         ->orderBy('id', 'desc')
                         ->paginate(15);
        
        return view('admin.nav_links.index', compact('navLinks'));
    }

    /**
     * 显示创建表单
     */
    public function create()
    {
        return view('admin.nav_links.create');
    }

    /**
     * 保存新导航链接
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'link' => 'required|url|max:255',
            'icon' => 'nullable|string|max:50',
            'sort' => 'nullable|integer',
            'status' => 'required|boolean'
        ]);

        NavLink::create($validated);

        return redirect()->route('admin.nav_links.index')
                        ->with('success', '导航链接创建成功');
    }

    /**
     * 显示编辑表单
     */
    public function edit(NavLink $navLink)
    {
        return view('admin.nav_links.edit', compact('navLink'));
    }

    /**
     * 更新导航链接
     */
    public function update(Request $request, NavLink $navLink)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'link' => 'required|url|max:255',
            'icon' => 'nullable|string|max:50',
            'sort' => 'nullable|integer',
            'status' => 'required|boolean'
        ]);

        $navLink->update($validated);

        return redirect()->route('admin.nav_links.index')
                        ->with('success', '导航链接更新成功');
    }

    /**
     * 删除导航链接
     */
    public function destroy(Request $request, NavLink $navLink)
    {
        $navLink->delete();

        return redirect()->route('admin.nav_links.index')
                        ->with('success', '导航链接删除成功');
    }

    /**
     * 获取福利导航列表 - API格式 (v2board后台使用)
     */
    public function fetch(Request $request)
    {
        $query = NavLink::query();

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

        // 排序
        $sortType = $request->input('sort_type', 'desc');
        $sortField = $request->input('sort', 'created_at');
        $query->orderBy($sortField, $sortType);

        // 分页
        $current = $request->input('current', 1);
        $pageSize = $request->input('pageSize', 10);
        
        $total = $query->count();
        $navLinks = $query->forPage($current, $pageSize)->get();

        return response()->json([
            'data' => $navLinks,
            'total' => $total
        ]);
    }

    /**
     * 保存福利导航 - API格式 (v2board后台使用)
     */
    public function save(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'status' => 'required|in:normal,hidden'
        ];

        $messages = [
            'title.required' => '标题不能为空',
            'title.max' => '标题长度不能超过100个字符',
            'url.required' => 'URL不能为空',
            'url.max' => 'URL长度不能超过255个字符',
            'status.required' => '状态不能为空',
            'status.in' => '状态值必须是normal或hidden'
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        // 处理URL格式 - 如果没有协议前缀，自动添加https://
        $url = $request->input('url');
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        $data = [
            'title' => $request->input('title'),
            'url' => $url, // 使用处理后的URL
            'logo' => $request->input('logo'),
            'sort' => $request->input('sort', 0),
            'status' => $request->input('status', 'normal'),
            'createtime' => time(),
            'updatetime' => time()
        ];

        NavLink::create($data);

        return response()->json([
            'message' => '福利导航创建成功'
        ]);
    }

    /**
     * 删除福利导航 - API格式 (v2board后台使用)
     */
    public function drop(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json([
                'message' => '参数错误'
            ], 422);
        }

        $navLink = NavLink::find($id);
        if (!$navLink) {
            return response()->json([
                'message' => '福利导航不存在'
            ], 404);
        }

        $navLink->delete();

        return response()->json([
            'message' => '福利导航删除成功'
        ]);
    }

    /**
     * 显示/隐藏福利导航 - API格式 (v2board后台使用)
     */
    public function show(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        
        if (!$id || !in_array($status, ['normal', 'hidden'])) {
            return response()->json([
                'message' => '参数错误'
            ], 422);
        }

        $navLink = NavLink::find($id);
        if (!$navLink) {
            return response()->json([
                'message' => '福利导航不存在'
            ], 404);
        }

        $navLink->update([
            'status' => $status,
            'updatetime' => time()
        ]);

        return response()->json([
            'message' => '操作成功'
        ]);
    }

    /**
     * 排序福利导航 - API格式 (v2board后台使用)
     */
    public function sort(Request $request)
    {
        $sorts = $request->input('sorts');
        if (!$sorts || !is_array($sorts)) {
            return response()->json([
                'message' => '参数错误'
            ], 422);
        }

        foreach ($sorts as $sort) {
            if (isset($sort['id']) && isset($sort['sort'])) {
                NavLink::where('id', $sort['id'])->update([
                    'sort' => $sort['sort'],
                    'updatetime' => time()
                ]);
            }
        }

        return response()->json([
            'message' => '排序更新成功'
        ]);
    }
}