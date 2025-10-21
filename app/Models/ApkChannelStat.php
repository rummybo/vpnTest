<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApkChannelStat extends Model
{
    protected $table = 'apk_channel_stats';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'extra_data' => 'array'
    ];

    // 统计类型常量
    const TYPE_DOWNLOAD = 1;  // 下载
    const TYPE_REGISTER = 2;  // 注册
    const TYPE_LOGIN = 3;     // 登录

    // 获取统计类型文本
    public static function getTypeText($type)
    {
        $types = [
            self::TYPE_DOWNLOAD => '下载',
            self::TYPE_REGISTER => '注册',
            self::TYPE_LOGIN => '登录'
        ];
        return $types[$type] ?? '未知';
    }

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}