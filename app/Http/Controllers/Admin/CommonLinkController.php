<?php

namespace App\Http\Controllers\Admin;

use App\Models\CommonLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CommonLinkController extends Controller
{
    /**
     * 获取常用导航列表 - API格式 (v2board后台使用)
     */
    public function fetch(Request $request)
    {
        $query = CommonLink::query();

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

        // 排序 - 权重大值靠前
        $sortType = $request->input('sort_type', 'desc');
        $sortField = $request->input('sort', 'weigh');
        $query->orderBy($sortField, $sortType);

        // 分页
        $current = $request->input('current', 1);
        $pageSize = $request->input('pageSize', 10);
        
        $total = $query->count();
        $commonLinks = $query->forPage($current, $pageSize)->get();

        return response()->json([
            'data' => $commonLinks,
            'total' => $total
        ]);
    }

    /**
     * 保存常用导航 - API格式 (v2board后台使用)
     */
    public function save(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:100',
            'url' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'weigh' => 'nullable|integer|min:0',
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

        // 检查是否是更新操作
        $isUpdate = $request->has('id') && $request->input('id');
        
        $data = [
            'title' => $request->input('title'),
            'url' => $url, // 使用处理后的URL
            'logo' => $request->input('logo', ''),
            'weigh' => $request->input('weigh', 0),
            'status' => $request->input('status', 'normal'),
            'updatetime' => time()
        ];
        
        if ($isUpdate) {
            // 更新操作
            $commonLink = CommonLink::find($request->input('id'));
            if (!$commonLink) {
                return response()->json([
                    'message' => '记录不存在'
                ], 404);
            }
            $commonLink->update($data);
        } else {
            // 新增操作
            $data['createtime'] = time();
            CommonLink::create($data);
        }

        return response()->json([
            'message' => '常用导航保存成功'
        ]);
    }

    /**
     * 删除常用导航 - API格式 (v2board后台使用)
     */
    public function drop(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json([
                'message' => '参数错误'
            ], 422);
        }

        $commonLink = CommonLink::find($id);
        if (!$commonLink) {
            return response()->json([
                'message' => '常用导航不存在'
            ], 404);
        }

        $commonLink->delete();

        return response()->json([
            'message' => '常用导航删除成功'
        ]);
    }

    /**
     * 显示/隐藏常用导航 - API格式 (v2board后台使用)
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

        $commonLink = CommonLink::find($id);
        if (!$commonLink) {
            return response()->json([
                'message' => '常用导航不存在'
            ], 404);
        }

        $commonLink->update([
            'status' => $status,
            'updatetime' => time()
        ]);

        return response()->json([
            'message' => '操作成功'
        ]);
    }

    /**
     * 排序常用导航 - API格式 (v2board后台使用)
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
            if (isset($sort['id']) && isset($sort['weigh'])) {
                CommonLink::where('id', $sort['id'])->update([
                    'weigh' => $sort['weigh'],
                    'updatetime' => time()
                ]);
            }
        }

        return response()->json([
            'message' => '排序更新成功'
        ]);
    }
}