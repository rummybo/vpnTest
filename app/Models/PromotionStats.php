<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionStats extends Model
{
    protected $table = 'v2_promotion_stats';
    
    protected $fillable = [
        'promotion_code',
        'total_uses',
        'active_users',
        'total_duration'
    ];

    protected $casts = [
        'total_uses' => 'integer',
        'active_users' => 'integer',
        'total_duration' => 'integer'
    ];

    /**
     * 获取推广设备
     */
    public function promotionDevice()
    {
        return $this->belongsTo(PromotionDevice::class, 'promotion_code', 'promotion_code');
    }

    /**
     * 更新统计数据
     */
    public function updateStats($newUse = false, $duration = 0)
    {
        if ($newUse) {
            $this->increment('total_uses');
        }
        
        if ($duration > 0) {
            $this->increment('total_duration', $duration);
        }
        
        // 更新活跃用户数
        $activeCount = PromotionUsage::where('promotion_code', $this->promotion_code)
            ->whereDate('last_used_date', today())
            ->count();
            
        $this->update(['active_users' => $activeCount]);
    }
}