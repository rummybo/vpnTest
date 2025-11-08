<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceNoticeController extends Controller
{
    // Blade 视图：列表
    public function index(Request $request)
    {
        $query = MaintenanceNotice::query();
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        $notices = $query->orderByDesc('weigh')->orderByDesc('id')->paginate(20);
        return view('admin.maintenance_notices.index', compact('notices'));
    }

    // Blade 视图：新增
    public function create()
    {
        return view('admin.maintenance_notices.create');
    }

    // Blade 视图：保存新增
    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'weigh' => 'nullable|integer',
            'status' => 'required|string|in:normal,hidden',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data['createtime'] = time();
        $data['updatetime'] = time();
        MaintenanceNotice::create($data);
        return redirect()->route('admin.maintenance_notices.index')->with('success', '创建成功');
    }

    // Blade 视图：编辑
    public function edit($id)
    {
        $notice = MaintenanceNotice::findOrFail($id);
        return view('admin.maintenance_notices.edit', compact('notice'));
    }

    // Blade 视图：保存编辑
    public function update(Request $request, $id)
    {
        $notice = MaintenanceNotice::findOrFail($id);
        $data = $request->all();
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'weigh' => 'nullable|integer',
            'status' => 'required|string|in:normal,hidden',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $data['updatetime'] = time();
        $notice->update($data);
        return redirect()->route('admin.maintenance_notices.index')->with('success', '更新成功');
    }

    // Blade 视图：删除
    public function destroy($id)
    {
        MaintenanceNotice::where('id', $id)->delete();
        return redirect()->route('admin.maintenance_notices.index')->with('success', '删除成功');
    }

    // v2board 风格接口：获取列表（含搜索/分页）
    public function fetch(Request $request)
    {
        $query = MaintenanceNotice::query();
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }
        $query->orderByDesc('weigh')->orderByDesc('id');
        $pageSize = (int)($request->input('page_size', 10));
        $pageNo = (int)($request->input('page_no', 1));
        $total = $query->count();
        $items = $query->forPage($pageNo, $pageSize)->get();
        return response()->json([
            'total' => $total,
            'items' => $items,
        ]);
    }

    // v2board 风格接口：保存（新增或更新）
    public function save(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'weigh' => 'nullable|integer',
            'status' => 'required|string|in:normal,hidden',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        if (!empty($data['id'])) {
            $notice = MaintenanceNotice::findOrFail($data['id']);
            $data['updatetime'] = time();
            $notice->update($data);
        } else {
            $data['createtime'] = time();
            $data['updatetime'] = time();
            $notice = MaintenanceNotice::create($data);
        }
        return response()->json(['message' => 'ok', 'data' => $notice]);
    }

    // v2board 风格接口：删除
    public function drop(Request $request)
    {
        $id = (int)$request->input('id');
        if (!$id) {
            return response()->json(['message' => 'id 必填'], 422);
        }
        MaintenanceNotice::where('id', $id)->delete();
        return response()->json(['message' => 'ok']);
    }

    // v2board 风格接口：显示/隐藏
    public function show(Request $request)
    {
        $id = (int)$request->input('id');
        $status = $request->input('status');
        if (!$id || !in_array($status, ['normal','hidden'], true)) {
            return response()->json(['message' => '参数错误'], 422);
        }
        MaintenanceNotice::where('id', $id)->update([
            'status' => $status,
            'updatetime' => time(),
        ]);
        return response()->json(['message' => 'ok']);
    }

    // v2board 风格接口：调整排序
    public function sort(Request $request)
    {
        $id = (int)$request->input('id');
        $weigh = (int)$request->input('weigh');
        if (!$id) {
            return response()->json(['message' => 'id 必填'], 422);
        }
        MaintenanceNotice::where('id', $id)->update([
            'weigh' => $weigh,
            'updatetime' => time(),
        ]);
        return response()->json(['message' => 'ok']);
    }
}