<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    /**
     * 获取APP前端配置
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $config = ConfigService::getFrontendConfig();
            
            // 转换为布尔值，方便前端使用
            $formattedConfig = [];
            foreach ($config as $key => $value) {
                $formattedConfig[$key] = $value === '1' || $value === 1 || $value === true;
            }
            
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $formattedConfig
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => [
                    'nav_links_enable' => true,
                    'common_links_enable' => true,
                    'frontend_nav_pages_enable' => true,
                    'user_display_enable' => true
                ]
            ], 500);
        }
    }

    /**
     * 获取完整的前端配置（包含更多信息）
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailed()
    {
        try {
            $configs = ConfigService::getByGroup('frontend');
            
            $result = [];
            foreach ($configs as $config) {
                $result[$config->key] = [
                    'enabled' => $config->value === '1' || $config->value === 1 || $config->value === true,
                    'name' => $config->name,
                    'description' => $config->description,
                    'value' => $config->value
                ];
            }
            
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $result
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
     * 检查单个功能是否启用
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        try {
            $key = $request->input('key');
            
            if (!$key) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Missing required parameter: key',
                    'data' => null
                ], 400);
            }
            
            $enabled = ConfigService::isEnabled($key, false);
            
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'key' => $key,
                    'enabled' => $enabled
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => 'Internal Server Error',
                'data' => null
            ], 500);
        }
    }

    /**
     * 获取系统基本信息（可选，用于APP显示）
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function info()
    {
        try {
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'app_name' => config('v2board.app_name', 'V2Board'),
                    'app_version' => config('app.version', '1.0.0'),
                    'logo' => config('v2board.logo'),
                    'description' => config('v2board.app_description', 'V2Board is best'),
                    'features' => ConfigService::getFrontendConfig()
                ]
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
     * 批量检查多个功能状态
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchCheck(Request $request)
    {
        try {
            $keys = $request->input('keys', []);
            
            if (!is_array($keys) || empty($keys)) {
                return response()->json([
                    'code' => 400,
                    'message' => 'Parameter keys must be a non-empty array',
                    'data' => []
                ], 400);
            }
            
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = ConfigService::isEnabled($key, false);
            }
            
            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $result
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