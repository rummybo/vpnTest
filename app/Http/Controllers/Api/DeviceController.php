<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromotionUsage;
use App\Models\DeviceSession;
use App\Models\DeviceDailyUsage;
use App\Models\PromotionDevice;
use App\Models\PromotionStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DeviceController extends Controller
{
    /**
     * 获取设备权限信息
     */
    public function permission($deviceId)
    {
        try {
            // 检查是否是VIP用户
            $promotionDevice = PromotionDevice::where('device_id', $deviceId)->first();
            $isVip = $promotionDevice && $promotionDevice->is_vip;

            if ($isVip) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'device_id' => $deviceId,
                        'daily_limit' => -1,
                        'single_limit' => -1,
                        'used_today' => 0,
                        'remaining_today' => -1,
                        'is_vip' => true,
                        'can_start_session' => true
                    ]
                ]);
            }

            // 查找推广使用记录
            $usage = PromotionUsage::where('user_device_id', $deviceId)->first();
            
            if (!$usage) {
                return response()->json([
                    'success' => false,
                    'message' => '设备未绑定推广码，请先绑定推广码获取使用权限'
                ], 404);
            }

            // 检查每日重置
            $usage->checkDailyReset();
            
            $remainingDuration = $usage->getRemainingDuration();
            $canStartSession = $usage->canStartSession();

            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $deviceId,
                    'daily_limit' => $usage->daily_duration,
                    'single_limit' => $usage->single_duration,
                    'used_today' => $usage->used_today,
                    'remaining_today' => $remainingDuration,
                    'is_vip' => false,
                    'is_unlimited' => $usage->is_unlimited,
                    'can_start_session' => $canStartSession
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询权限失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 开始使用会话
     */
    public function startSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:128'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $deviceId = $request->input('device_id');

            // 检查权限
            $permissionResponse = $this->permission($deviceId);
            $permissionData = $permissionResponse->getData(true);
            
            if (!$permissionData['success']) {
                return $permissionResponse;
            }

            $permission = $permissionData['data'];
            if (!$permission['can_start_session']) {
                return response()->json([
                    'success' => false,
                    'message' => '今日使用时长已用完，无法开始新会话'
                ], 400);
            }

            // 清理过期会话
            DeviceSession::cleanupExpiredSessions();

            // 检查是否已有活跃会话
            $activeSession = DeviceSession::getActiveSession($deviceId);
            if ($activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => '已有活跃会话，请先结束当前会话',
                    'data' => [
                        'session_id' => $activeSession->session_id,
                        'start_time' => $activeSession->start_time,
                        'current_duration' => $activeSession->duration
                    ]
                ], 400);
            }

            // 开始新会话
            $sessionType = $permission['is_vip'] ? 'vip' : 'promotion';
            $session = DeviceSession::startSession($deviceId, $sessionType);

            // 计算最大可用时长
            $maxDuration = $permission['single_limit'];
            if ($maxDuration != -1 && $permission['remaining_today'] != -1) {
                $maxDuration = min($maxDuration, $permission['remaining_today']);
            } elseif ($permission['remaining_today'] != -1) {
                $maxDuration = $permission['remaining_today'];
            }

            $expiresAt = $maxDuration != -1 
                ? $session->start_time->addSeconds($maxDuration)
                : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->session_id,
                    'start_time' => $session->start_time,
                    'current_duration' => $session->duration ?? 0,
                    'max_duration' => $maxDuration,
                    'expires_at' => $expiresAt
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '开始会话失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新会话时长
     */
    public function updateSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:64',
            'duration' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $sessionId = $request->input('session_id');
            $duration = $request->input('duration');

            $session = DeviceSession::where('session_id', $sessionId)
                                   ->where('is_active', true)
                                   ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => '会话不存在或已结束'
                ], 404);
            }

            // 检查权限限制
            $permissionResponse = $this->permission($session->device_id);
            $permissionData = $permissionResponse->getData(true);
            
            if ($permissionData['success']) {
                $permission = $permissionData['data'];
                
                // 检查单次时长限制
                if ($permission['single_limit'] != -1 && $duration > $permission['single_limit']) {
                    $duration = $permission['single_limit'];
                }
                
                // 检查每日时长限制
                if ($permission['daily_limit'] != -1) {
                    $maxAllowed = $permission['daily_limit'] - $permission['used_today'] + $session->duration;
                    if ($duration > $maxAllowed) {
                        $duration = max(0, $maxAllowed);
                    }
                }
            }

            // 获取更新前的时长
            $previousDuration = $session->duration ?? 0;
            
            // 更新会话时长
            $session->update(['duration' => $duration]);
            
            // 手动更新每日使用统计
            $increment = $duration - $previousDuration;
            if ($increment != 0) {
                $dailyUsage = DeviceDailyUsage::firstOrCreate([
                    'device_id' => $session->device_id,
                    'usage_date' => today()
                ], [
                    'total_duration' => 0,
                    'session_count' => 0
                ]);
                
                // 更新总时长
                $dailyUsage->increment('total_duration', $increment);
                
                // 如果是新会话（从0开始），增加会话计数
                if ($previousDuration == 0 && $duration > 0) {
                    $dailyUsage->increment('session_count');
                }
            }

            // 计算剩余时长
            $remainingDuration = -1;
            if (isset($permission)) {
                if ($permission['single_limit'] != -1) {
                    $remainingDuration = max(0, $permission['single_limit'] - $duration);
                }
                if ($permission['daily_limit'] != -1) {
                    $dailyRemaining = max(0, $permission['daily_limit'] - $permission['used_today'] - ($duration - $session->getOriginal('duration')));
                    $remainingDuration = $remainingDuration == -1 ? $dailyRemaining : min($remainingDuration, $dailyRemaining);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'current_duration' => $duration,
                    'remaining_duration' => $remainingDuration,
                    'is_valid' => true
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新会话失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 结束使用会话
     */
    public function endSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:64',
            'final_duration' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $sessionId = $request->input('session_id');
            $finalDuration = $request->input('final_duration');

            $session = DeviceSession::where('session_id', $sessionId)
                                   ->where('is_active', true)
                                   ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => '会话不存在或已结束'
                ], 404);
            }

            // 获取会话结束前的时长
            $previousDuration = $session->duration ?? 0;
            
            // 结束会话
            $totalDuration = $session->endSession($finalDuration);

            // 手动更新每日使用统计
            $increment = $totalDuration - $previousDuration;
            if ($increment != 0) {
                $dailyUsage = DeviceDailyUsage::firstOrCreate([
                    'device_id' => $session->device_id,
                    'usage_date' => today()
                ], [
                    'total_duration' => 0,
                    'session_count' => 0
                ]);
                
                // 更新总时长
                $dailyUsage->increment('total_duration', $increment);
                
                // 如果是新会话（从0开始），增加会话计数
                if ($previousDuration == 0 && $totalDuration > 0) {
                    $dailyUsage->increment('session_count');
                }
            }

            // 更新推广使用记录（只有当时长增加时才更新）
            $usage = PromotionUsage::where('user_device_id', $session->device_id)->first();
            if ($usage && !$usage->is_unlimited && $usage->daily_duration != -1) {
                if ($increment > 0) {
                    $usage->updateUsage($increment);
                    
                    // 更新推广统计
                    $stats = PromotionStats::where('promotion_code', $usage->promotion_code)->first();
                    if ($stats) {
                        $stats->updateStats(false, $increment);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'total_duration' => $totalDuration,
                    'session_ended' => true
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '结束会话失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取设备使用历史
     */
    public function usageHistory($deviceId)
    {
        try {
            // 今日使用情况
            $todayUsage = DeviceDailyUsage::getTodayUsage($deviceId);
            
            // 最近7天使用历史
            $weeklyUsage = DeviceDailyUsage::getUsageHistory($deviceId, 7);
            
            // 总体统计
            $totalStats = DeviceDailyUsage::getTotalStats($deviceId);
            
            // 最近10次会话历史
            $recentSessions = DeviceSession::where('device_id', $deviceId)
                                         ->orderBy('start_time', 'desc')
                                         ->limit(10)
                                         ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $deviceId,
                    'today_usage' => [
                        'total_duration' => $todayUsage->total_duration,
                        'session_count' => $todayUsage->session_count,
                        'last_session' => $recentSessions->first() ? $recentSessions->first()->start_time : null
                    ],
                    'recent_sessions' => $recentSessions->map(function ($session) {
                        return [
                            'session_id' => $session->session_id,
                            'start_time' => $session->start_time,
                            'end_time' => $session->end_time,
                            'duration' => $session->duration,
                            'is_active' => $session->is_active,
                            'session_type' => $session->session_type
                        ];
                    }),
                    'weekly_usage' => $weeklyUsage->map(function ($usage) {
                        return [
                            'date' => $usage->usage_date->format('Y-m-d'),
                            'duration' => $usage->total_duration,
                            'session_count' => $usage->session_count
                        ];
                    }),
                    'total_stats' => $totalStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询使用历史失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取活跃会话
     */
    public function activeSession($deviceId)
    {
        try {
            $activeSession = DeviceSession::getActiveSession($deviceId);

            if (!$activeSession) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_active_session' => false
                    ]
                ]);
            }

            // 计算剩余时长
            $permissionResponse = $this->permission($deviceId);
            $permissionData = $permissionResponse->getData(true);
            
            $remainingDuration = -1;
            if ($permissionData['success']) {
                $permission = $permissionData['data'];
                if ($permission['single_limit'] != -1) {
                    $remainingDuration = max(0, $permission['single_limit'] - $activeSession->duration);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_active_session' => true,
                    'session_id' => $activeSession->session_id,
                    'start_time' => $activeSession->start_time,
                    'current_duration' => $activeSession->duration,
                    'remaining_duration' => $remainingDuration
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询活跃会话失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 强制结束会话
     */
    public function forceEndSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:128',
            'reason' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $deviceId = $request->input('device_id');
            $reason = $request->input('reason', 'force_end');

            $activeSession = DeviceSession::getActiveSession($deviceId);
            
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => '没有活跃会话'
                ], 404);
            }

            // 强制结束会话
            $totalDuration = $activeSession->endSession();

            return response()->json([
                'success' => true,
                'message' => '会话已强制结束',
                'data' => [
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
     * 获取设备会话历史记录（分页）
     */
    public function sessionHistory(Request $request, $deviceId)
    {
        try {
            $perPage = $request->input('per_page', 20);
            
            $sessions = DeviceSession::where('device_id', $deviceId)
                                   ->orderBy('start_time', 'desc')
                                   ->paginate($perPage);
            
            $data = $sessions->map(function ($session) {
                return [
                    'session_id' => $session->session_id,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'duration' => $session->duration,
                    'is_active' => $session->is_active,
                    'session_type' => $session->session_type,
                    'created_at' => $session->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询会话历史失败: ' . $e->getMessage()
            ], 500);
        }
    }
}