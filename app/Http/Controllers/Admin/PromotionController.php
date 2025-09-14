<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionDevice;
use App\Models\PromotionUsage;
use App\Models\PromotionStats;
use App\Models\DeviceSession;
use App\Models\DeviceDailyUsage;
use App\Models\CoinExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /**
     * 推广者列表
     */
    public function promotionList(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            
            $query = PromotionDevice::with(['stats']);
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('device_id', 'like', "%{$search}%")
                      ->orWhere('promotion_code', 'like', "%{$search}%");
                });
            }
            
            $promotions = $query->orderBy('total_referrals', 'desc')
                               ->paginate($perPage);
            
            $data = $promotions->map(function ($device) {
                $rewardLevel = $device->getRewardLevel();
                return [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'promotion_code' => $device->promotion_code,
                    'total_referrals' => $device->total_referrals,
                    'coins' => $device->coins,
                    'is_vip' => $device->is_vip,
                    'reward_level' => $rewardLevel,
                    'can_earn_coins' => $device->canEarnCoins(),
                    'stats' => [
                        'total_uses' => $device->stats->total_uses ?? 0,
                        'active_users' => $device->stats->active_users ?? 0,
                        'total_duration' => $device->stats->total_duration ?? 0
                    ],
                    'created_at' => $device->created_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取推广者列表失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 设备列表
     */
    public function deviceList(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            
            $query = PromotionUsage::with(['promotionDevice']);
            
            if ($search) {
                $query->where('user_device_id', 'like', "%{$search}%");
            }
            
            $devices = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);
            
            $data = $devices->map(function ($usage) {
                // 获取今日使用情况
                $todayUsage = DeviceDailyUsage::getTodayUsage($usage->user_device_id);
                
                // 获取活跃会话
                $activeSession = DeviceSession::getActiveSession($usage->user_device_id);
                
                return [
                    'id' => $usage->id,
                    'device_id' => $usage->user_device_id,
                    'promotion_code' => $usage->promotion_code,
                    'promoter_device_id' => $usage->promotionDevice->device_id ?? null,
                    'daily_duration' => $usage->daily_duration,
                    'single_duration' => $usage->single_duration,
                    'is_unlimited' => $usage->is_unlimited,
                    'today_usage' => [
                        'used_duration' => $usage->used_today,
                        'session_count' => $todayUsage->session_count,
                        'remaining_duration' => $usage->getRemainingDuration()
                    ],
                    'active_session' => $activeSession ? [
                        'session_id' => $activeSession->session_id,
                        'start_time' => $activeSession->start_time,
                        'duration' => $activeSession->duration
                    ] : null,
                    'last_used_date' => $usage->last_used_date,
                    'created_at' => $usage->created_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $devices->currentPage(),
                    'last_page' => $devices->lastPage(),
                    'per_page' => $devices->perPage(),
                    'total' => $devices->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取设备列表失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 强制结束设备会话
     */
    public function forceEndDeviceSession(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:128',
            'reason' => 'string|max:255'
        ]);

        try {
            $deviceId = $request->input('device_id');
            $reason = $request->input('reason', 'admin_force_end');

            $activeSession = DeviceSession::getActiveSession($deviceId);
            
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => '该设备没有活跃会话'
                ], 404);
            }

            // 强制结束会话
            $totalDuration = $activeSession->endSession();

            return response()->json([
                'success' => true,
                'message' => '设备会话已强制结束',
                'data' => [
                    'device_id' => $deviceId,
                    'session_id' => $activeSession->session_id,
                    'total_duration' => $totalDuration,
                    'reason' => $reason
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '强制结束会话失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 系统统计概览
     */
    public function statsOverview()
    {
        try {
            // 推广统计
            $promotionStats = [
                'total_promoters' => PromotionDevice::count(),
                'total_referrals' => PromotionDevice::sum('total_referrals'),
                'vip_users' => PromotionDevice::where('is_vip', true)->count(),
                'total_coins' => PromotionDevice::sum('coins')
            ];

            // 使用统计
            $usageStats = [
                'total_users' => PromotionUsage::count(),
                'unlimited_users' => PromotionUsage::where('is_unlimited', true)->count(),
                'active_sessions' => DeviceSession::where('is_active', true)->count(),
                'today_sessions' => DeviceSession::whereDate('start_time', today())->count()
            ];

            // 今日使用统计
            $todayStats = DeviceDailyUsage::where('usage_date', today())
                                         ->selectRaw('
                                             SUM(total_duration) as total_duration,
                                             SUM(session_count) as total_sessions,
                                             COUNT(*) as active_devices
                                         ')
                                         ->first();

            // 金币兑换统计
            $coinStats = [
                'total_exchanges' => CoinExchange::count(),
                'total_coins_used' => CoinExchange::sum('coins_used'),
                'vip_exchanges' => CoinExchange::where('exchange_type', 'vip')->count()
            ];

            // 最近7天趋势
            $weeklyTrend = DeviceDailyUsage::where('usage_date', '>=', today()->subDays(6))
                                          ->groupBy('usage_date')
                                          ->selectRaw('
                                              usage_date,
                                              SUM(total_duration) as total_duration,
                                              SUM(session_count) as total_sessions,
                                              COUNT(*) as active_devices
                                          ')
                                          ->orderBy('usage_date')
                                          ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'promotion_stats' => $promotionStats,
                    'usage_stats' => $usageStats,
                    'today_stats' => [
                        'total_duration' => $todayStats->total_duration ?? 0,
                        'total_sessions' => $todayStats->total_sessions ?? 0,
                        'active_devices' => $todayStats->active_devices ?? 0
                    ],
                    'coin_stats' => $coinStats,
                    'weekly_trend' => $weeklyTrend->map(function ($day) {
                        return [
                            'date' => $day->usage_date,
                            'total_duration' => $day->total_duration,
                            'total_sessions' => $day->total_sessions,
                            'active_devices' => $day->active_devices
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取统计概览失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 推广排行榜
     */
    public function promotionRanking(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);
            
            $ranking = PromotionDevice::orderBy('total_referrals', 'desc')
                                    ->limit($limit)
                                    ->get()
                                    ->map(function ($device, $index) {
                                        $rewardLevel = $device->getRewardLevel();
                                        return [
                                            'rank' => $index + 1,
                                            'device_id' => $device->device_id,
                                            'promotion_code' => $device->promotion_code,
                                            'total_referrals' => $device->total_referrals,
                                            'coins' => $device->coins,
                                            'is_vip' => $device->is_vip,
                                            'reward_level' => $rewardLevel['level'],
                                            'created_at' => $device->created_at
                                        ];
                                    });

            return response()->json([
                'success' => true,
                'data' => $ranking
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取推广排行榜失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 清理异常会话
     */
    public function cleanupSessions()
    {
        try {
            // 清理过期会话
            DeviceSession::cleanupExpiredSessions();
            
            // 统计清理结果
            $expiredCount = DeviceSession::where('is_active', false)
                                       ->whereNotNull('end_time')
                                       ->whereDate('updated_at', today())
                                       ->count();

            return response()->json([
                'success' => true,
                'message' => '异常会话清理完成',
                'data' => [
                    'cleaned_sessions' => $expiredCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '清理异常会话失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 重置设备使用统计
     */
    public function resetDeviceUsage(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:128',
            'reset_type' => 'required|in:today,all'
        ]);

        try {
            $deviceId = $request->input('device_id');
            $resetType = $request->input('reset_type');

            if ($resetType === 'today') {
                // 重置今日使用统计
                $usage = PromotionUsage::where('user_device_id', $deviceId)->first();
                if ($usage) {
                    $usage->update(['used_today' => 0]);
                }
                
                DeviceDailyUsage::where('device_id', $deviceId)
                               ->where('usage_date', today())
                               ->update([
                                   'total_duration' => 0,
                                   'session_count' => 0
                               ]);
                
                $message = '今日使用统计已重置';
            } else {
                // 重置所有使用统计
                PromotionUsage::where('user_device_id', $deviceId)
                             ->update(['used_today' => 0]);
                
                DeviceDailyUsage::where('device_id', $deviceId)->delete();
                
                // 结束活跃会话
                DeviceSession::where('device_id', $deviceId)
                            ->where('is_active', true)
                            ->update([
                                'is_active' => false,
                                'end_time' => now()
                            ]);
                
                $message = '所有使用统计已重置';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '重置使用统计失败: ' . $e->getMessage()
            ], 500);
        }
    }
}