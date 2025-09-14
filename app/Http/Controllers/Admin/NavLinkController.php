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
}