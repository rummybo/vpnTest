<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FrontendNavPage;
use Illuminate\Http\Request;

class FrontendNavPageController extends Controller
{
    /**
     * 获取前端导航页列表
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $navPages = FrontendNavPage::active()
                ->ordered()
                ->get(['id', 'name', 'icon', 'url', 'sort']);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $navPages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => []
            ], 500);
        }
    }

    /**
     * 按分组获取前端导航页（可根据排序值分组）
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function grouped()
    {
        try {
            $navPages = FrontendNavPage::active()
                ->ordered()
                ->get(['id', 'name', 'icon', 'url', 'sort']);

            // 按排序值分组
            $grouped = $navPages->groupBy('sort')->map(function ($group) {
                return $group->values();
            });

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $grouped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => []
            ], 500);
        }
    }

    /**
     * 获取热门导航页（前N个）
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            $navPages = FrontendNavPage::active()
                ->ordered()
                ->limit($limit)
                ->get(['id', 'name', 'icon', 'url', 'sort']);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $navPages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => []
            ], 500);
        }
    }
}