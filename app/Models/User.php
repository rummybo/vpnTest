<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'v2_user';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    /**
     * 获取用户名
     */
    public function getUsernameAttribute()
    {
        return $this->attributes['username'] ?? null;
    }

    /**
     * 获取手机号
     */
    public function getPhoneAttribute()
    {
        return $this->attributes['phone'] ?? null;
    }

    /**
     * 设置用户名
     */
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = $value;
    }

    /**
     * 设置手机号
     */
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = $value;
    }
}
