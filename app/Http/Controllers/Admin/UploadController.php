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
            
            // 生成完整的URL，包含正确的协议和端口
            $baseUrl = $request->getScheme() . '://' . $request->getHttpHost();
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