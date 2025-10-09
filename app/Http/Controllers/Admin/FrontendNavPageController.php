<?php

namespace App\Http\Controllers\Admin;

use App\Models\FrontendNavPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FrontendNavPageController extends Controller
{
    /**
     * 显示前端导航页列表
     */
    public function index(Request $request)
    {
        $query = FrontendNavPage::query();
        
        // 搜索条件
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $navPages = $query->orderBy('sort', 'asc')
                         ->orderBy('id', 'desc')
                         ->paginate(15);
        
        return view('admin.frontend_nav_pages.index', compact('navPages'));
    }

    /**
     * 显示创建表单
     */
    public function create()
    {
        return view('admin.frontend_nav_pages.create');
    }

    /**
     * 保存新的前端导航页
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'sort' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive'
        ], [
            'name.required' => '导航页名称不能为空',
            'name.max' => '导航页名称不能超过100个字符',
            'url.required' => '跳转URL不能为空',
            'url.url' => '请输入有效的URL地址',
            'url.max' => 'URL长度不能超过500个字符',
            'icon.max' => '图标字段不能超过255个字符',
            'sort.integer' => '排序必须是数字',
            'sort.min' => '排序不能小于0',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效'
        ]);

        // 如果没有设置排序，自动设为最大值+1
        if (!isset($validated['sort'])) {
            $validated['sort'] = FrontendNavPage::max('sort') + 1;
        }

        FrontendNavPage::create($validated);

        return redirect()->route('admin.frontend_nav_pages.index')
                        ->with('success', '前端导航页创建成功');
    }

    /**
     * 显示编辑表单
     */
    public function edit(FrontendNavPage $frontendNavPage)
    {
        return view('admin.frontend_nav_pages.edit', compact('frontendNavPage'));
    }

    /**
     * 更新前端导航页
     */
    public function update(Request $request, FrontendNavPage $frontendNavPage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'sort' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive'
        ], [
            'name.required' => '导航页名称不能为空',
            'name.max' => '导航页名称不能超过100个字符',
            'url.required' => '跳转URL不能为空',
            'url.url' => '请输入有效的URL地址',
            'url.max' => 'URL长度不能超过500个字符',
            'icon.max' => '图标字段不能超过255个字符',
            'sort.integer' => '排序必须是数字',
            'sort.min' => '排序不能小于0',
            'status.required' => '状态不能为空',
            'status.in' => '状态值无效'
        ]);

        $frontendNavPage->update($validated);

        return redirect()->route('admin.frontend_nav_pages.index')
                        ->with('success', '前端导航页更新成功');
    }

    /**
     * 删除前端导航页
     */
    public function destroy(FrontendNavPage $frontendNavPage)
    {
        $frontendNavPage->delete();

        return redirect()->route('admin.frontend_nav_pages.index')
                        ->with('success', '前端导航页删除成功');
    }

    /**
     * 获取前端导航页列表 - API格式 (v2board后台使用)
     */
    public function fetch(Request $request)
    {
        $query = FrontendNavPage::query();

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
        $sortField = $request->input('sort', 'id');
        $query->orderBy($sortField, $sortType);

        // 分页
        $current = $request->input('current', 1);
        $pageSize = $request->input('pageSize', 10);
        
        $total = $query->count();
        $navPages = $query->forPage($current, $pageSize)->get();

        return response()->json([
            'data' => $navPages,
            'total' => $total
        ]);
    }

    /**
     * 保存前端导航页 - API格式 (v2board后台使用)
     */
    public function save(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'url' => 'required|string|max:500',
            'icon' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive'
        ];

        $messages = [
            'name.required' => '导航页名称不能为空',
            'name.max' => '导航页名称长度不能超过100个字符',
            'url.required' => 'URL不能为空',
            'url.max' => 'URL长度不能超过500个字符',
            'status.required' => '状态不能为空',
            'status.in' => '状态值必须是active或inactive'
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

        // 检查是否是更新操作
        $isUpdate = $request->has('id') && $request->input('id');
        
        $data = [
            'name' => $request->input('name'),
            'url' => $url,
            'icon' => $request->input('icon', ''),
            'sort' => $request->input('sort', 0),
            'status' => $request->input('status', 'active')
        ];
        
        if ($isUpdate) {
            // 更新操作
            $navPage = FrontendNavPage::find($request->input('id'));
            if (!$navPage) {
                return response()->json([
                    'message' => '记录不存在'
                ], 404);
            }
            $navPage->update($data);
        } else {
            // 新增操作
            if (!isset($data['sort'])) {
                $data['sort'] = FrontendNavPage::max('sort') + 1;
            }
            FrontendNavPage::create($data);
        }

        return response()->json([
            'message' => '前端导航页保存成功'
        ]);
    }

    /**
     * 删除前端导航页 - API格式 (v2board后台使用)
     */
    public function drop(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json([
                'message' => '参数错误'
            ], 422);
        }

        $navPage = FrontendNavPage::find($id);
        if (!$navPage) {
            return response()->json([
                'message' => '前端导航页不存在'
            ], 404);
        }

        $navPage->delete();

        return response()->json([
            'message' => '前端导航页删除成功'
        ]);
    }

    /**
     * 启用/禁用前端导航页 - API格式 (v2board后台使用)
     */
    public function show(Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        
        if (!$id || !in_array($status, ['active', 'inactive'])) {
            return response()->json([
                'message' => '参数错误'
            ], 422);
        }

        $navPage = FrontendNavPage::find($id);
        if (!$navPage) {
            return response()->json([
                'message' => '前端导航页不存在'
            ], 404);
        }

        $navPage->update(['status' => $status]);

        return response()->json([
            'message' => '操作成功'
        ]);
    }

    /**
     * 排序前端导航页 - API格式 (v2board后台使用)
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
                FrontendNavPage::where('id', $sort['id'])->update([
                    'sort' => $sort['sort']
                ]);
            }
        }

        return response()->json([
            'message' => '排序更新成功'
        ]);
    }
}