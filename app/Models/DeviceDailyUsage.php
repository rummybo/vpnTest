<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DeviceDailyUsage extends Model
{
    protected $table = 'v2_device_daily_usage';
    
    protected $fillable = [
        'device_id',
        'usage_date',
        'total_duration',
        'session_count'
    ];

    protected $casts = [
        'usage_date' => 'date',
        'total_duration' => 'integer',
        'session_count' => 'integer'
    ];

    /**
     * 获取设备会话
     */
    public function sessions()
    {
        return $this->hasMany(DeviceSession::class, 'device_id', 'device_id')
                    ->whereDate('start_time', $this->usage_date);
    }

    /**
     * 获取设备今日使用情况
     */
    public static function getTodayUsage($deviceId)
    {
        return self::firstOrCreate([
            'device_id' => $deviceId,
            'usage_date' => Carbon::today()
        ], [
            'total_duration' => 0,
            'session_count' => 0
        ]);
    }

    /**
     * 获取设备历史使用情况
     */
    public static function getUsageHistory($deviceId, $days = 7)
    {
        $startDate = Carbon::today()->subDays($days - 1);
        
        return self::where('device_id', $deviceId)
                   ->where('usage_date', '>=', $startDate)
                   ->orderBy('usage_date', 'desc')
                   ->get();
    }

    /**
     * 获取设备总使用统计
     */
    public static function getTotalStats($deviceId)
    {
        $stats = self::where('device_id', $deviceId)
                     ->selectRaw('
                         SUM(total_duration) as total_duration,
                         SUM(session_count) as total_sessions,
                         AVG(total_duration) as avg_daily_duration,
                         COUNT(*) as active_days
                     ')
                     ->first();

        return [
            'total_duration' => $stats->total_duration ?? 0,
            'total_sessions' => $stats->total_sessions ?? 0,
            'avg_daily_duration' => $stats->avg_daily_duration ?? 0,
            'active_days' => $stats->active_days ?? 0,
            'avg_session_duration' => $stats->total_sessions > 0 
                ? round($stats->total_duration / $stats->total_sessions) 
                : 0
        ];
    }
}