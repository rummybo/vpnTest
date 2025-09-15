<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommonLink;
use Illuminate\Http\Request;

class CommonLinkController extends Controller
{
    /**
     * 获取前端常用导航列表
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $links = CommonLink::where('status', 'normal')
                ->orderBy('weigh', 'desc')
                ->orderBy('id', 'asc')
                ->get(['id', 'title', 'url', 'logo', 'weigh']);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $links
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
     * 按权重分组获取常用导航
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function grouped()
    {
        try {
            $links = CommonLink::where('status', 'normal')
                ->orderBy('weigh', 'desc')
                ->orderBy('id', 'asc')
                ->get(['id', 'title', 'url', 'logo', 'weigh']);

            // 按权重分组
            $grouped = $links->groupBy('weigh')->map(function ($group) {
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
}