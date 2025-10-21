<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChannelManagementController extends Controller
{
    private $channels_config_file;
    private $apk_dir;
    
    public function __construct()
    {
        $this->channels_config_file = public_path('channels.json');
        $this->apk_dir = public_path('apk/');
    }
    
    /**
     * 获取所有渠道列表
     */
    public function getChannels()
    {
        try {
            $channels = $this->loadChannels();
            
            return response()->json([
                'success' => true,
                'data' => $channels
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取渠道列表失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 获取指定渠道信息
     */
    public function getChannel($channelCode)
    {
        try {
            $channels = $this->loadChannels();
            
            if (!isset($channels[$channelCode])) {
                return response()->json([
                    'success' => false,
                    'message' => '渠道不存在'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $channels[$channelCode]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取渠道信息失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 获取渠道的下载链接（供APK下载统计使用）
     */
    public function getChannelDownloadUrl($channelCode)
    {
        try {
            $channels = $this->loadChannels();
            
            if (!isset($channels[$channelCode])) {
                return response()->json([
                    'success' => false,
                    'message' => '渠道不存在'
                ], 404);
            }
            
            $channelInfo = $channels[$channelCode];
            
            if (empty($channelInfo['download_url'])) {
                return response()->json([
                    'success' => false,
                    'message' => '该渠道暂无可用的下载链接'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'channel_code' => $channelCode,
                    'channel_name' => $channelInfo['name'],
                    'version' => $channelInfo['version'],
                    'download_url' => $channelInfo['download_url'],
                    'update_time' => $channelInfo['update_time']
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取下载链接失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 检查渠道是否存在（供统计接口验证使用）
     */
    public function validateChannel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel_code' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }
        
        try {
            $channelCode = $request->channel_code;
            $channels = $this->loadChannels();
            
            $exists = isset($channels[$channelCode]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'channel_code' => $channelCode,
                    'exists' => $exists,
                    'channel_name' => $exists ? $channels[$channelCode]['name'] : null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '验证渠道失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 加载渠道配置
     */
    private function loadChannels()
    {
        if (file_exists($this->channels_config_file)) {
            $content = file_get_contents($this->channels_config_file);
            return json_decode($content, true) ?: [];
        }
        return [];
    }
}