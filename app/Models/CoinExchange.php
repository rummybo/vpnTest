<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinExchange extends Model
{
    protected $table = 'v2_coin_exchanges';
    
    protected $fillable = [
        'device_id',
        'coins_used',
        'exchange_type'
    ];

    protected $casts = [
        'coins_used' => 'integer'
    ];

    /**
     * 获取推广设备
     */
    public function promotionDevice()
    {
        return $this->belongsTo(PromotionDevice::class, 'device_id', 'device_id');
    }

    /**
     * 兑换类型配置
     */
    public static function getExchangeConfig()
    {
        return [
            'vip' => [
                'name' => '黄金会员',
                'cost' => 25,
                'description' => '永久无限制使用'
            ]
        ];
    }

    /**
     * 执行兑换
     */
    public static function exchange($deviceId, $exchangeType)
    {
        $config = self::getExchangeConfig();
        
        if (!isset($config[$exchangeType])) {
            throw new \Exception('无效的兑换类型');
        }
        
        $cost = $config[$exchangeType]['cost'];
        
        $device = PromotionDevice::where('device_id', $deviceId)->first();
        if (!$device) {
            throw new \Exception('设备不存在');
        }
        
        if ($device->coins < $cost) {
            throw new \Exception('金币不足');
        }
        
        if ($device->is_vip) {
            throw new \Exception('已经是VIP用户');
        }
        
        // 开始事务
        \DB::transaction(function () use ($device, $deviceId, $exchangeType, $cost) {
            // 扣除金币
            $device->decrement('coins', $cost);
            
            // 设置VIP状态
            $device->update(['is_vip' => true]);
            
            // 记录兑换
            self::create([
                'device_id' => $deviceId,
                'coins_used' => $cost,
                'exchange_type' => $exchangeType
            ]);
            
            // 更新所有该设备的使用记录为无限制
            PromotionUsage::where('user_device_id', $deviceId)
                          ->update(['is_unlimited' => true]);
        });
        
        return true;
    }
}