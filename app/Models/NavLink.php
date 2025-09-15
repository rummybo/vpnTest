<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NavLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v2_nav_links';
    
    // 禁用Laravel默认的timestamps，因为我们使用Unix时间戳
    public $timestamps = false;

    protected $fillable = [
        'title',
        'url',        // 使用实际字段名
        'logo',       // 使用实际字段名 
        'sort',
        'status'
    ];

    protected $casts = [
        'sort' => 'integer',
        'createtime' => 'integer',
        'updatetime' => 'integer'
    ];
    
    // 状态访问器 - 将enum转换为数字
    public function getStatusAttribute($value)
    {
        return $value === 'normal' ? 1 : 0;
    }
    
    // 状态修改器 - 将数字转换为enum
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value == 1 ? 'normal' : 'hidden';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return $this->status ? '显示' : '隐藏';
    }
    
    /**
     * 获取创建时间（兼容性）
     */
    public function getCreatedAtAttribute()
    {
        return $this->createtime;
    }
    
    /**
     * 获取更新时间（兼容性）
     */
    public function getUpdatedAtAttribute()
    {
        return $this->updatetime;
    }
}