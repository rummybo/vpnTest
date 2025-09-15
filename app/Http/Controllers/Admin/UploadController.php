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
            
            // 保存到public/uploads/nav_links目录
            $path = $file->storeAs('uploads/nav_links', $filename, 'public');
            
            // 返回完整的URL
            $url = asset('storage/' . $path);
            
            return response()->json([
                'message' => '图片上传成功',
                'url' => $url,
                'path' => $path
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => '图片上传失败: ' . $e->getMessage()
            ], 500);
        }
    }
}