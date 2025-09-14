<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromotionDevice;
use App\Models\PromotionUsage;
use App\Models\PromotionStats;
use App\Models\CoinExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShareController extends Controller
{
    /**
     * 生成推广链接
     */
    public function generate(Request $request)
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
            
            // 查找或创建推广设备
            $device = PromotionDevice::firstOrCreate(
                ['device_id' => $deviceId],
                ['promotion_code' => PromotionDevice::generatePromotionCode($deviceId)]
            );

            // 创建推广统计记录
            PromotionStats::firstOrCreate(['promotion_code' => $device->promotion_code]);

            $shareUrl = url('/device/promotion-bind.html?code=' . $device->promotion_code);

            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $deviceId ?? '',
                    'promotion_code' => $device->promotion_code ?? '',
                    'share_url' => $shareUrl ?? '',
                    'current_referrals' => $device->total_referrals ?? 0,
                    'reward_level' => $device->getRewardLevel() ?? ['daily' => 60, 'single' => 30],
                    'coins' => $device->coins ?? 0,
                    'is_vip' => $device->is_vip ?? false
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '生成推广链接失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 绑定推广码
     */
    public function bind(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promotion_code' => 'required|string|max:32',
            'user_device_id' => 'required|string|max:128'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $promotionCode = $request->input('promotion_code');
            $userDeviceId = $request->input('user_device_id');

            // 验证推广码
            $promotionDevice = PromotionDevice::where('promotion_code', $promotionCode)->first();
            if (!$promotionDevice) {
                return response()->json([
                    'success' => false,
                    'message' => '无效的推广码'
                ], 400);
            }

            // 检查是否已经绑定过
            $existingUsage = PromotionUsage::where('promotion_code', $promotionCode)
                                         ->where('user_device_id', $userDeviceId)
                                         ->first();
            if ($existingUsage) {
                return response()->json([
                    'success' => false,
                    'message' => '该设备已经绑定过此推广码'
                ], 400);
            }

            // 不能绑定自己的推广码
            if ($promotionDevice->device_id === $userDeviceId) {
                return response()->json([
                    'success' => false,
                    'message' => '不能绑定自己的推广码'
                ], 400);
            }

            DB::transaction(function () use ($promotionDevice, $promotionCode, $userDeviceId) {
                // 增加推广人数
                $promotionDevice->increment('total_referrals');
                
                // 如果达到23人，开始获得金币
                if ($promotionDevice->total_referrals >= 23) {
                    $promotionDevice->increment('coins');
                }

                // 给推广者发放奖励（更新推广者的使用权限）
                $this->grantPromoterReward($promotionDevice);

                // 创建绑定记录（新用户获得基础体验时长）
                PromotionUsage::create([
                    'promotion_code' => $promotionCode,
                    'user_device_id' => $userDeviceId,
                    'daily_duration' => 60, // 新用户基础体验时长：60分钟/天
                    'single_duration' => 30, // 单次使用时长：30分钟
                    'is_unlimited' => false
                ]);

                // 更新统计
                $stats = PromotionStats::where('promotion_code', $promotionCode)->first();
                if ($stats) {
                    $stats->updateStats(true); // 新用户绑定
                }
            });

            return response()->json([
                'success' => true,
                'message' => '绑定成功，您获得了基础体验时长',
                'data' => [
                    'daily_duration' => 60,
                    'single_duration' => 30,
                    'is_unlimited' => false,
                    'promoter_info' => [
                        'total_referrals' => $promotionDevice->total_referrals ?? 0,
                        'reward_level' => $promotionDevice->getRewardLevel() ?? ['daily' => 60, 'single' => 30],
                        'coins' => $promotionDevice->coins ?? 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '绑定失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 查询推广统计
     */
    public function stats($promotionCode)
    {
        try {
            $device = PromotionDevice::where('promotion_code', $promotionCode)->first();
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => '推广码不存在'
                ], 404);
            }

            $stats = PromotionStats::where('promotion_code', $promotionCode)->first();
            $rewardLevel = $device->getRewardLevel();

            return response()->json([
                'success' => true,
                'data' => [
                    'promotion_code' => $promotionCode ?? '',
                    'device_id' => $device->device_id ?? '',
                    'total_referrals' => $device->total_referrals ?? 0,
                    'coins' => $device->coins ?? 0,
                    'is_vip' => $device->is_vip ?? false,
                    'reward_level' => $rewardLevel ?? ['daily' => 60, 'single' => 30],
                    'can_earn_coins' => $device->canEarnCoins() ?? false,
                    'stats' => [
                        'total_uses' => $stats->total_uses ?? 0,
                        'active_users' => $stats->active_users ?? 0,
                        'total_duration' => $stats->total_duration ?? 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 给推广者发放奖励
     */
    private function grantPromoterReward($promotionDevice)
    {
        // 获取推广者当前的奖励等级
        $rewardLevel = $promotionDevice->getRewardLevel();
        
        // 查找或创建推广者自己的使用记录
        $promoterUsage = PromotionUsage::firstOrCreate(
            [
                'promotion_code' => $promotionDevice->promotion_code,
                'user_device_id' => $promotionDevice->device_id
            ],
            [
                'daily_duration' => $rewardLevel['daily'],
                'single_duration' => $rewardLevel['single'],
                'is_unlimited' => $rewardLevel['daily'] == -1
            ]
        );

        // 更新推广者的使用权限
        $promoterUsage->update([
            'daily_duration' => $rewardLevel['daily'],
            'single_duration' => $rewardLevel['single'],
            'is_unlimited' => $rewardLevel['daily'] == -1
        ]);

        // 记录奖励发放日志
        \Log::info("推广者奖励发放", [
            'device_id' => $promotionDevice->device_id,
            'promotion_code' => $promotionDevice->promotion_code,
            'total_referrals' => $promotionDevice->total_referrals,
            'reward_level' => $rewardLevel,
            'coins' => $promotionDevice->coins
        ]);
    }

    /**
     * 获取推广者信息
     */
    public function promoterInfo($deviceId)
    {
        try {
            $device = PromotionDevice::where('device_id', $deviceId)->first();
            
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => '推广者不存在，请先生成推广链接'
                ], 404);
            }

            $rewardLevel = $device->getRewardLevel();

            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $deviceId ?? '',
                    'promotion_code' => $device->promotion_code ?? '',
                    'total_referrals' => $device->total_referrals ?? 0,
                    'coins' => $device->coins ?? 0,
                    'is_vip' => $device->is_vip ?? false,
                    'reward_level' => $rewardLevel ?? ['daily' => 60, 'single' => 30],
                    'can_earn_coins' => $device->canEarnCoins() ?? false,
                    'next_milestone' => $this->getNextMilestone($device->total_referrals ?? 0),
                    'share_url' => url('/device/promotion-bind.html?code=' . ($device->promotion_code ?? ''))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取推广者奖励状态
     */
    public function rewardStatus($deviceId)
    {
        try {
            $device = PromotionDevice::where('device_id', $deviceId)->first();
            
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => '推广者不存在'
                ], 404);
            }

            // 查找推广者的使用记录
            $promoterUsage = PromotionUsage::where('promotion_code', $device->promotion_code)
                                         ->where('user_device_id', $deviceId)
                                         ->first();

            $rewardLevel = $device->getRewardLevel();

            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $deviceId ?? '',
                    'promotion_code' => $device->promotion_code ?? '',
                    'total_referrals' => $device->total_referrals ?? 0,
                    'coins' => $device->coins ?? 0,
                    'is_vip' => $device->is_vip ?? false,
                    'current_reward' => $rewardLevel ?? ['daily' => 60, 'single' => 30],
                    'usage_permission' => $promoterUsage ? [
                        'daily_duration' => $promoterUsage->daily_duration ?? 60,
                        'single_duration' => $promoterUsage->single_duration ?? 30,
                        'is_unlimited' => $promoterUsage->is_unlimited ?? false
                    ] : [
                        'daily_duration' => 60,
                        'single_duration' => 30,
                        'is_unlimited' => false
                    ],
                    'next_milestone' => $this->getNextMilestone($device->total_referrals ?? 0)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '查询失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取下一个里程碑信息
     */
    private function getNextMilestone($currentReferrals)
    {
        if ($currentReferrals < 1) {
            return ['target' => 1, 'reward' => '每日2小时使用时长'];
        } elseif ($currentReferrals < 2) {
            return ['target' => 2, 'reward' => '每日3小时使用时长'];
        } elseif ($currentReferrals < 3) {
            return ['target' => 3, 'reward' => '单次无限制使用'];
        } elseif ($currentReferrals < 23) {
            return ['target' => 23, 'reward' => '无限制使用 + 金币奖励'];
        } else {
            return ['target' => null, 'reward' => '已达到最高等级'];
        }
    }

    /**
     * 兑换黄金会员
     */
    public function exchangeVip(Request $request)
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
            
            CoinExchange::exchange($deviceId, 'vip');

            $device = PromotionDevice::where('device_id', $deviceId)->first();

            return response()->json([
                'success' => true,
                'message' => '兑换成功，您已成为黄金会员',
                'data' => [
                    'is_vip' => true,
                    'remaining_coins' => $device->coins
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}