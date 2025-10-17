<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_configs';
    
    protected $fillable = [
        'key',
        'value', 
        'name',
        'description',
        'type',
        'group',
        'sort',
        'options',
        'is_system',
        'status'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'status' => 'boolean',
        'sort' => 'integer'
    ];

    /**
     * 获取选项配置（JSON解析）
     */
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    /**
     * 设置选项配置（JSON编码）
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = $value ? json_encode($value) : null;
    }

    /**
     * 根据键名获取配置值
     */
    public static function getValue($key, $default = null)
    {
        $config = static::where('key', $key)
            ->where('status', 1)
            ->first();
            
        return $config ? $config->value : $default;
    }

    /**
     * 设置配置值
     */
    public static function setValue($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * 获取分组配置
     */
    public static function getByGroup($group)
    {
        return static::where('group', $group)
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 获取所有启用的配置（键值对形式）
     */
    public static function getAllEnabled()
    {
        return static::where('status', 1)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * 检查配置是否启用（用于开关类型）
     */
    public static function isEnabled($key, $default = false)
    {
        $value = static::getValue($key, $default ? '1' : '0');
        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * 按分组获取配置列表（用于后台管理）
     */
    public static function getGroupedConfigs()
    {
        return static::orderBy('group', 'asc')
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('group');
    }

    /**
     * 作用域：仅获取启用的配置
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 作用域：按分组筛选
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * 作用域：按类型筛选
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}