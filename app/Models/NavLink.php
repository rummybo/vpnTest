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
        'status',
        'createtime',
        'updatetime'
    ];

    protected $casts = [
        'sort' => 'integer',
        'createtime' => 'integer',
        'updatetime' => 'integer'
    ];
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return $this->attributes['status'] === 'normal' ? '显示' : '隐藏';
    }
    
    /**
     * 获取启用的导航链接
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'normal');
    }

    /**
     * 按排序字段排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->orderBy('id', 'asc');
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