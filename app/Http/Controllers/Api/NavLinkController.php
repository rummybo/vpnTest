<?php

namespace App\Http\Controllers\Api;

use App\Models\NavLink;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NavLinkController extends Controller
{
    /**
     * 获取福利导航列表（前端API）
     */
    public function index(Request $request)
    {
        // 只获取状态为显示的导航链接
        $navLinks = NavLink::where('status', 1)
                          ->orderBy('sort', 'asc')
                          ->orderBy('id', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $navLinks,
            'message' => '获取成功'
        ]);
    }

    /**
     * 获取单个导航链接详情
     */
    public function show($id)
    {
        $navLink = NavLink::where('status', 1)->find($id);

        if (!$navLink) {
            return response()->json([
                'success' => false,
                'message' => '导航链接不存在或已隐藏'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $navLink,
            'message' => '获取成功'
        ]);
    }

    /**
     * 获取导航链接分组（按分类）
     */
    public function grouped()
    {
        // 这里可以根据需要实现分组逻辑
        $navLinks = NavLink::where('status', 1)
                          ->orderBy('sort', 'asc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $navLinks,
            'message' => '获取成功'
        ]);
    }
}