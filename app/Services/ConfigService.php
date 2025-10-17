<?php

namespace App\Services;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfigService
{
    /**
     * 缓存键前缀
     */
    const CACHE_PREFIX = 'system_config:';
    
    /**
     * 缓存时间（秒）
     */
    const CACHE_TTL = 3600; // 1小时

    /**
     * 获取配置值（带缓存）
     */
    public static function get($key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
                return SystemConfig::getValue($key, $default);
            });
        } catch (\Exception $e) {
            Log::error('ConfigService::get error: ' . $e->getMessage(), [
                'key' => $key,
                'default' => $default
            ]);
            return $default;
        }
    }

    /**
     * 设置配置值（同时更新缓存）
     */
    public static function set($key, $value)
    {
        try {
            $result = SystemConfig::setValue($key, $value);
            
            // 清除单个配置缓存
            self::forget($key);
            
            // 清除所有配置缓存
            self::forgetAll();
            
            return $result;
        } catch (\Exception $e) {
            Log::error('ConfigService::set error: ' . $e->getMessage(), [
                'key' => $key,
                'value' => $value
            ]);
            return false;
        }
    }

    /**
     * 检查配置是否启用（用于开关类型）
     */
    public static function isEnabled($key, $default = false)
    {
        $value = self::get($key, $default ? '1' : '0');
        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * 获取所有启用的配置（带缓存）
     */
    public static function getAll()
    {
        $cacheKey = self::CACHE_PREFIX . 'all_enabled';
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return SystemConfig::getAllEnabled();
            });
        } catch (\Exception $e) {
            Log::error('ConfigService::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取前端配置（APP使用）
     */
    public static function getFrontendConfig()
    {
        $cacheKey = self::CACHE_PREFIX . 'frontend_config';
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return SystemConfig::where('group', 'frontend')
                    ->where('status', 1)
                    ->pluck('value', 'key')
                    ->toArray();
            });
        } catch (\Exception $e) {
            Log::error('ConfigService::getFrontendConfig error: ' . $e->getMessage());
            return [
                'nav_links_enable' => '1',
                'common_links_enable' => '1', 
                'frontend_nav_pages_enable' => '1',
                'user_display_enable' => '1'
            ];
        }
    }

    /**
     * 按分组获取配置
     */
    public static function getByGroup($group)
    {
        $cacheKey = self::CACHE_PREFIX . 'group:' . $group;
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group) {
                return SystemConfig::getByGroup($group);
            });
        } catch (\Exception $e) {
            Log::error('ConfigService::getByGroup error: ' . $e->getMessage(), [
                'group' => $group
            ]);
            return collect();
        }
    }

    /**
     * 批量设置配置
     */
    public static function setBatch($configs)
    {
        try {
            foreach ($configs as $key => $value) {
                SystemConfig::setValue($key, $value);
            }
            
            // 清除所有相关缓存
            self::forgetAll();
            
            return true;
        } catch (\Exception $e) {
            Log::error('ConfigService::setBatch error: ' . $e->getMessage(), [
                'configs' => $configs
            ]);
            return false;
        }
    }

    /**
     * 清除单个配置缓存
     */
    public static function forget($key)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        Cache::forget($cacheKey);
    }

    /**
     * 清除所有配置相关缓存
     */
    public static function forgetAll()
    {
        $keys = [
            self::CACHE_PREFIX . 'all_enabled',
            self::CACHE_PREFIX . 'frontend_config',
            self::CACHE_PREFIX . 'group:frontend',
            self::CACHE_PREFIX . 'group:general',
            self::CACHE_PREFIX . 'group:system',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 刷新配置缓存
     */
    public static function refresh()
    {
        self::forgetAll();
        
        // 预热关键配置缓存
        self::getFrontendConfig();
        self::getAll();
    }

    /**
     * 获取配置的显示值（用于后台管理）
     */
    public static function getDisplayValue($config)
    {
        switch ($config->type) {
            case 'switch':
                return $config->value === '1' ? '开启' : '关闭';
            case 'select':
                if ($config->options && is_array($config->options)) {
                    return $config->options[$config->value] ?? $config->value;
                }
                return $config->value;
            default:
                return $config->value;
        }
    }

    /**
     * 验证配置值
     */
    public static function validateValue($type, $value, $options = null)
    {
        switch ($type) {
            case 'switch':
                return in_array($value, ['0', '1', 0, 1, true, false]);
            case 'number':
                return is_numeric($value);
            case 'select':
                if ($options && is_array($options)) {
                    return array_key_exists($value, $options);
                }
                return true;
            case 'text':
            case 'textarea':
            default:
                return true;
        }
    }
}