<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromotionUsage extends Model
{
    protected $table = 'v2_promotion_usage';
    
    protected $fillable = [
        'promotion_code',
        'user_device_id',
        'daily_duration',
        'single_duration',
        'used_today',
        'last_used_date',
        'is_unlimited'
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'daily_duration' => 'integer',
        'single_duration' => 'integer',
        'used_today' => 'integer',
        'last_used_date' => 'date'
    ];

    /**
     * 获取推广设备
     */
    public function promotionDevice()
    {
        return $this->belongsTo(PromotionDevice::class, 'promotion_code', 'promotion_code');
    }

    /**
     * 获取设备会话
     */
    public function deviceSessions()
    {
        return $this->hasMany(DeviceSession::class, 'device_id', 'user_device_id');
    }

    /**
     * 检查今日是否需要重置使用时长
     */
    public function checkDailyReset()
    {
        $today = Carbon::today();
        
        if (!$this->last_used_date || $this->last_used_date->lt($today)) {
            $this->update([
                'used_today' => 0,
                'last_used_date' => $today
            ]);
        }
    }

    /**
     * 获取今日剩余时长
     */
    public function getRemainingDuration()
    {
        $this->checkDailyReset();
        
        if ($this->is_unlimited || $this->daily_duration == -1) {
            return -1; // 无限制
        }
        
        return max(0, $this->daily_duration - $this->used_today);
    }

    /**
     * 是否可以开始新会话
     */
    public function canStartSession()
    {
        if ($this->is_unlimited) {
            return true;
        }
        
        $remaining = $this->getRemainingDuration();
        return $remaining == -1 || $remaining > 0;
    }

    /**
     * 更新使用时长
     */
    public function updateUsage($duration)
    {
        $this->checkDailyReset();
        
        if (!$this->is_unlimited && $this->daily_duration != -1) {
            $this->increment('used_today', $duration);
        }
    }
}