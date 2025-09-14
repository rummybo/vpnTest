<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionDevice extends Model
{
    protected $table = 'v2_promotion_devices';
    
    protected $fillable = [
        'device_id',
        'promotion_code',
        'total_referrals',
        'coins',
        'is_vip'
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'total_referrals' => 'integer',
        'coins' => 'integer'
    ];

    /**
     * 获取推广使用记录
     */
    public function usageRecords()
    {
        return $this->hasMany(PromotionUsage::class, 'promotion_code', 'promotion_code');
    }

    /**
     * 获取推广统计
     */
    public function stats()
    {
        return $this->hasOne(PromotionStats::class, 'promotion_code', 'promotion_code');
    }

    /**
     * 获取金币兑换记录
     */
    public function coinExchanges()
    {
        return $this->hasMany(CoinExchange::class, 'device_id', 'device_id');
    }

    /**
     * 计算当前奖励等级
     */
    public function getRewardLevel()
    {
        $referrals = $this->total_referrals;
        
        if ($referrals == 0) {
            return ['daily' => 3600, 'single' => 3600, 'level' => '新手'];
        } elseif ($referrals == 1) {
            return ['daily' => 7200, 'single' => 3600, 'level' => '初级'];
        } elseif ($referrals == 2) {
            return ['daily' => 10800, 'single' => 5400, 'level' => '中级'];
        } elseif ($referrals >= 3 && $referrals < 23) {
            $dailyHours = min(4 + ($referrals - 3), 24);
            return ['daily' => $dailyHours * 3600, 'single' => -1, 'level' => '高级'];
        } else {
            return ['daily' => -1, 'single' => -1, 'level' => '永久'];
        }
    }

    /**
     * 是否可以获得金币
     */
    public function canEarnCoins()
    {
        return $this->total_referrals >= 23;
    }

    /**
     * 生成推广码
     */
    public static function generatePromotionCode($deviceId)
    {
        return 'PROMO_' . strtoupper(substr(md5($deviceId . time()), 0, 8));
    }
}