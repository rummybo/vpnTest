<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DeviceSession extends Model
{
    protected $table = 'v2_device_sessions';
    
    protected $fillable = [
        'device_id',
        'session_id',
        'start_time',
        'end_time',
        'duration',
        'is_active',
        'session_type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    /**
     * 获取设备每日使用统计
     */
    public function dailyUsage()
    {
        return $this->belongsTo(DeviceDailyUsage::class, 'device_id', 'device_id')
                    ->where('usage_date', Carbon::today());
    }

    /**
     * 生成会话ID
     */
    public static function generateSessionId($deviceId)
    {
        return 'SESSION_' . strtoupper(substr(md5($deviceId . microtime()), 0, 12));
    }

    /**
     * 开始新会话
     */
    public static function startSession($deviceId, $sessionType = 'promotion')
    {
        // 结束该设备的所有活跃会话
        self::where('device_id', $deviceId)
            ->where('is_active', true)
            ->update(['is_active' => false, 'end_time' => now()]);

        // 创建新会话
        return self::create([
            'device_id' => $deviceId,
            'session_id' => self::generateSessionId($deviceId),
            'start_time' => now(),
            'is_active' => true,
            'session_type' => $sessionType
        ]);
    }

    /**
     * 更新会话时长
     */
    public function updateDuration($duration)
    {
        $this->update(['duration' => $duration]);
        
        // 注意：每日使用统计现在在 DeviceController 中直接处理
        // 避免在这里重复更新
    }

    /**
     * 结束会话
     */
    public function endSession($finalDuration = null)
    {
        $duration = $finalDuration ?? $this->duration;
        
        $this->update([
            'duration' => $duration,
            'end_time' => now(),
            'is_active' => false
        ]);
        
        // 注意：每日使用统计现在在 DeviceController 中直接处理
        // 避免在这里重复更新
        
        return $duration;
    }

    /**
     * 更新每日使用统计
     */
    private function updateDailyUsage($duration)
    {
        // 注意：这个方法现在主要在 DeviceController 中直接处理
        // 保留此方法是为了向后兼容，但建议在控制器中直接更新
        $today = Carbon::today();
        
        $dailyUsage = DeviceDailyUsage::firstOrCreate([
            'device_id' => $this->device_id,
            'usage_date' => $today
        ], [
            'total_duration' => 0,
            'session_count' => 0
        ]);
        
        // 简化逻辑：直接设置为当前时长（避免重复计算问题）
        // 实际的增量计算应该在控制器中处理
    }

    /**
     * 获取活跃会话
     */
    public static function getActiveSession($deviceId)
    {
        return self::where('device_id', $deviceId)
                   ->where('is_active', true)
                   ->first();
    }

    /**
     * 清理过期会话
     */
    public static function cleanupExpiredSessions()
    {
        // 清理超过24小时的活跃会话
        $expiredTime = Carbon::now()->subHours(24);
        
        self::where('is_active', true)
            ->where('start_time', '<', $expiredTime)
            ->update([
                'is_active' => false,
                'end_time' => now()
            ]);
    }
}