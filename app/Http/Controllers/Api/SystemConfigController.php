<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * 获取系统配置状态（供APP检查功能开关）
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        try {
            $configs = ConfigService::getAllEnabled();
            
            // 提取前端相关的配置状态
            $status = [
                'system_config_enable' => $configs['system_config_enable'] ?? 0,
                'user_display_enable' => $configs['user_display_enable'] ?? 0,
                'nav_links_enable' => $configs['nav_links_enable'] ?? 0,
                'common_links_enable' => $configs['common_links_enable'] ?? 0,
                'frontend_nav_pages_enable' => $configs['frontend_nav_pages_enable'] ?? 0
            ];

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $status
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
     * 获取前端相关系统配置（直接查库，不使用缓存）
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function frontend()
    {
        try {
            // 直接查询数据库，不使用缓存
            $configs = \App\Models\SystemConfig::where('group', 'frontend')
                ->where('status', 1)
                ->pluck('value', 'key')
                ->toArray();
            
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $configs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * 获取所有启用的系统配置
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $configs = ConfigService::getAllEnabled();
            
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $configs
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