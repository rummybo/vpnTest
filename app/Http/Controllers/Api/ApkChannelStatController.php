<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApkChannelStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApkChannelStatController extends Controller
{
    /**
     * 记录APK渠道统计
     */
    public function record(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel_code' => 'required|string|max:50',
            'type' => 'required|integer|in:1,2,3',
            'device_id' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer|exists:v2_user,id',
            'app_version' => 'nullable|string|max:20',
            'platform' => 'nullable|string|max:20',
            'extra_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $stat = ApkChannelStat::create([
                'channel_code' => $request->channel_code,
                'type' => $request->type,
                'device_id' => $request->device_id,
                'user_id' => $request->user_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'app_version' => $request->app_version,
                'platform' => $request->platform ?? 'android',
                'extra_data' => $request->extra_data,
                'created_at' => time(),
                'updated_at' => time()
            ]);

            return response()->json([
                'success' => true,
                'message' => '统计记录成功',
                'data' => [
                    'id' => $stat->id,
                    'type_text' => ApkChannelStat::getTypeText($stat->type)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '记录失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取渠道统计数据
     */
    public function stats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel_code' => 'nullable|string|max:50',
            'type' => 'nullable|integer|in:1,2,3',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $query = ApkChannelStat::query();

            // 筛选条件
            if ($request->channel_code) {
                $query->where('channel_code', $request->channel_code);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->start_date) {
                $query->where('created_at', '>=', strtotime($request->start_date));
            }

            if ($request->end_date) {
                $query->where('created_at', '<=', strtotime($request->end_date . ' 23:59:59'));
            }

            // 分页
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $offset = ($page - 1) * $limit;

            $total = $query->count();
            $stats = $query->orderBy('created_at', 'desc')
                          ->offset($offset)
                          ->limit($limit)
                          ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'list' => $stats->map(function ($stat) {
                        return [
                            'id' => $stat->id,
                            'channel_code' => $stat->channel_code,
                            'type' => $stat->type,
                            'type_text' => ApkChannelStat::getTypeText($stat->type),
                            'device_id' => $stat->device_id,
                            'user_id' => $stat->user_id,
                            'ip_address' => $stat->ip_address,
                            'app_version' => $stat->app_version,
                            'platform' => $stat->platform,
                            'extra_data' => $stat->extra_data,
                            'created_at' => $stat->created_at,
                            'created_at_formatted' => date('Y-m-d H:i:s', $stat->created_at)
                        ];
                    }),
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => ceil($total / $limit)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取统计数据失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取渠道汇总统计
     */
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel_code' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $query = ApkChannelStat::query();

            // 筛选条件
            if ($request->channel_code) {
                $query->where('channel_code', $request->channel_code);
            }

            if ($request->start_date) {
                $query->where('created_at', '>=', strtotime($request->start_date));
            }

            if ($request->end_date) {
                $query->where('created_at', '<=', strtotime($request->end_date . ' 23:59:59'));
            }

            // 按渠道和类型分组统计
            $summary = $query->selectRaw('
                channel_code,
                type,
                COUNT(*) as count,
                COUNT(DISTINCT device_id) as unique_devices,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->groupBy(['channel_code', 'type'])
            ->orderBy('channel_code')
            ->orderBy('type')
            ->get();

            // 整理数据格式
            $result = [];
            foreach ($summary as $item) {
                $channelCode = $item->channel_code;
                if (!isset($result[$channelCode])) {
                    $result[$channelCode] = [
                        'channel_code' => $channelCode,
                        'download_count' => 0,
                        'register_count' => 0,
                        'login_count' => 0,
                        'unique_devices' => 0,
                        'unique_users' => 0
                    ];
                }

                switch ($item->type) {
                    case ApkChannelStat::TYPE_DOWNLOAD:
                        $result[$channelCode]['download_count'] = $item->count;
                        break;
                    case ApkChannelStat::TYPE_REGISTER:
                        $result[$channelCode]['register_count'] = $item->count;
                        break;
                    case ApkChannelStat::TYPE_LOGIN:
                        $result[$channelCode]['login_count'] = $item->count;
                        break;
                }

                $result[$channelCode]['unique_devices'] = max($result[$channelCode]['unique_devices'], $item->unique_devices);
                $result[$channelCode]['unique_users'] = max($result[$channelCode]['unique_users'], $item->unique_users);
            }

            return response()->json([
                'success' => true,
                'data' => array_values($result)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取汇总统计失败：' . $e->getMessage()
            ], 500);
        }
    }
}