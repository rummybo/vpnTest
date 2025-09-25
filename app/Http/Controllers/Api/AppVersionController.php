<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AppVersionController extends Controller
{
    /**
     * 获取所有平台版本信息
     */
    public function getVersions()
    {
        try {
            $androidVersion = $this->parseVersionFile('android');
            $iosVersion = $this->parseVersionFile('ios');

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'android' => $androidVersion,
                    'ios' => $iosVersion
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取版本信息失败: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * 获取指定平台版本信息
     */
    public function getVersionByPlatform($platform)
    {
        try {
            // 验证平台参数
            if (!in_array($platform, ['android', 'ios'])) {
                return response()->json([
                    'code' => 400,
                    'message' => '不支持的平台类型，仅支持 android 或 ios',
                    'data' => []
                ], 400);
            }

            $versionInfo = $this->parseVersionFile($platform);

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $versionInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取版本信息失败: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * 解析版本文件
     */
    private function parseVersionFile($platform)
    {
        $fileName = $platform . '_version.txt';
        $filePath = public_path($fileName);

        // 检查文件是否存在
        if (!File::exists($filePath)) {
            return [
                'platform' => $platform,
                'version' => null,
                'download_url' => null,
                'update_time' => null,
                'status' => 'no_version_available'
            ];
        }

        // 读取文件内容
        $content = File::get($filePath);
        
        if (empty(trim($content))) {
            return [
                'platform' => $platform,
                'version' => null,
                'download_url' => null,
                'update_time' => null,
                'status' => 'empty_file'
            ];
        }

        // 解析文件内容
        $lines = explode("\n", $content);
        $versionInfo = [
            'platform' => $platform,
            'version' => null,
            'download_url' => null,
            'update_time' => null,
            'status' => 'available'
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 解析版本号
            if (strpos($line, '版本号:') === 0) {
                $versionInfo['version'] = trim(str_replace('版本号:', '', $line));
            }
            // 解析下载地址
            elseif (strpos($line, '下载地址:') === 0) {
                $versionInfo['download_url'] = trim(str_replace('下载地址:', '', $line));
            }
            // 解析更新时间
            elseif (strpos($line, '更新时间:') === 0) {
                $versionInfo['update_time'] = trim(str_replace('更新时间:', '', $line));
            }
        }

        // 如果下载地址包含错误信息，标记状态
        if ($versionInfo['download_url'] && strpos($versionInfo['download_url'], '无下载地址') !== false) {
            $versionInfo['status'] = 'no_download_url';
        }

        // 尝试获取文件大小（仅对APK文件）
        if ($platform === 'android' && $versionInfo['download_url']) {
            $versionInfo['file_size'] = $this->getApkFileSize($versionInfo['download_url']);
        }

        return $versionInfo;
    }

    /**
     * 获取APK文件大小
     */
    private function getApkFileSize($downloadUrl)
    {
        try {
            // 检查是否是本地APK文件
            if (strpos($downloadUrl, '/apk/') !== false) {
                $fileName = basename(parse_url($downloadUrl, PHP_URL_PATH));
                $apkPath = public_path('apk/' . $fileName);
                
                if (File::exists($apkPath)) {
                    $sizeBytes = File::size($apkPath);
                    return $this->formatFileSize($sizeBytes);
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 格式化文件大小
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * 检查更新接口
     */
    public function checkUpdate(Request $request)
    {
        try {
            $platform = $request->input('platform');
            $currentVersion = $request->input('current_version');

            // 验证参数
            if (!$platform || !in_array($platform, ['android', 'ios'])) {
                return response()->json([
                    'code' => 400,
                    'message' => '请提供有效的平台参数（android 或 ios）',
                    'data' => []
                ], 400);
            }

            if (!$currentVersion) {
                return response()->json([
                    'code' => 400,
                    'message' => '请提供当前版本号',
                    'data' => []
                ], 400);
            }

            // 获取最新版本信息
            $latestVersion = $this->parseVersionFile($platform);

            if ($latestVersion['status'] !== 'available' || !$latestVersion['version']) {
                return response()->json([
                    'code' => 200,
                    'message' => 'success',
                    'data' => [
                        'has_update' => false,
                        'message' => '暂无可用版本'
                    ]
                ]);
            }

            // 比较版本号
            $hasUpdate = version_compare($latestVersion['version'], $currentVersion, '>');

            $responseData = [
                'has_update' => $hasUpdate,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion['version']
            ];

            if ($hasUpdate) {
                $responseData['download_url'] = $latestVersion['download_url'];
                $responseData['update_time'] = $latestVersion['update_time'];
                if (isset($latestVersion['file_size'])) {
                    $responseData['file_size'] = $latestVersion['file_size'];
                }
                $responseData['message'] = '发现新版本';
            } else {
                $responseData['message'] = '已是最新版本';
            }

            return response()->json([
                'code' => 200,
                'message' => 'success',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '检查更新失败: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}