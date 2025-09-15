<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * 上传图片文件
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        try {
            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // 直接保存到public/uploads/nav_links目录，避免storage链接问题
            $uploadDir = public_path('uploads/nav_links');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // 移动文件到public目录
            $relativePath = 'uploads/nav_links/' . $filename;
            $fullPath = public_path($relativePath);
            
            if (!$file->move($uploadDir, $filename)) {
                throw new \Exception('文件移动失败');
            }
            
            // 验证文件是否真的保存成功
            if (!file_exists($fullPath)) {
                throw new \Exception('文件保存验证失败');
            }
            
            // 生成完整的URL - 强制包含端口号的最终解决方案
            $scheme = $request->isSecure() ? 'https' : 'http';
            
            // 获取原始主机信息
            $originalHost = $request->server('HTTP_HOST') ?: $request->server('SERVER_NAME') ?: $request->getHost();
            $serverPort = $request->server('SERVER_PORT') ?: $request->getPort();
            
            // 强制端口号处理 - 针对你的环境
            $finalHost = $originalHost;
            
            // 如果是你的服务器环境，强制添加端口号
            if (strpos($originalHost, '192.197.113.52') !== false) {
                // 移除可能存在的端口号，然后重新添加
                $finalHost = preg_replace('/:\d+$/', '', $originalHost);
                $finalHost = $finalHost . ':2053';
            } else {
                // 其他环境的通用处理
                if (strpos($originalHost, ':') === false) {
                    // 如果没有端口号，根据协议和端口添加
                    if (($scheme === 'http' && $serverPort != 80) || ($scheme === 'https' && $serverPort != 443)) {
                        $finalHost = $originalHost . ':' . $serverPort;
                    }
                }
            }
            
            $url = $scheme . '://' . $finalHost.':2053' . '/' . $relativePath;
            
            // 记录调试信息
            \Log::info('Upload URL Final Generation', [
                'original_host' => $originalHost,
                'server_port' => $serverPort,
                'final_host' => $finalHost,
                'final_url' => $url,
                'scheme' => $scheme
            ]);
            
            return response()->json([
                'message' => '图片上传成功',
                'url' => $url,
                'path' => $relativePath,
                'filename' => $filename,
                'size' => filesize($fullPath)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => '图片上传失败: ' . $e->getMessage()
            ], 500);
        }
    }
}