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
            
            // 生成完整的URL - 使用最可靠的方法
            $scheme = $request->isSecure() ? 'https' : 'http';
            
            // 直接使用 HTTP_HOST，它包含了主机名和端口号
            $httpHost = $request->server('HTTP_HOST'); // 例如: 192.197.113.52:2053
            
            // 如果 HTTP_HOST 为空，则手动构建
            if (empty($httpHost)) {
                $host = $request->server('SERVER_NAME') ?: $request->server('SERVER_ADDR') ?: 'localhost';
                $port = $request->server('SERVER_PORT') ?: ($scheme === 'https' ? 443 : 80);
                
                if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
                    $httpHost = $host . ':' . $port;
                } else {
                    $httpHost = $host;
                }
            }
            
            // 构建完整URL
            $baseUrl = $scheme . '://' . $httpHost;
            $url = $baseUrl . '/' . $relativePath;
            
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