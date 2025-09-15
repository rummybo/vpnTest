<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommonLink extends Model
{
    use HasFactory;

    protected $table = 'v2_common_links';
    
    // 禁用Laravel默认的timestamps，因为我们使用Unix时间戳
    public $timestamps = false;

    protected $fillable = [
        'title',
        'logo',
        'url',
        'weigh',      // 权重字段，大值靠前
        'status',
        'createtime',
        'updatetime'
    ];

    protected $casts = [
        'weigh' => 'integer',
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
     * 获取启用的常用导航
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'normal');
    }

    /**
     * 按权重字段排序（大值靠前）
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('weigh', 'desc')->orderBy('id', 'desc');
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

    /**
     * 获取格式化的创建时间
     */
    public function getFormattedCreateTimeAttribute()
    {
        return $this->createtime ? date('Y-m-d H:i:s', $this->createtime) : null;
    }

    /**
     * 获取格式化的更新时间
     */
    public function getFormattedUpdateTimeAttribute()
    {
        return $this->updatetime ? date('Y-m-d H:i:s', $this->updatetime) : null;
    }
}