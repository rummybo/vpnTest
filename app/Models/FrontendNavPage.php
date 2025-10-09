<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FrontendNavPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v2_frontend_nav_pages';

    protected $fillable = [
        'name',
        'icon',
        'url',
        'sort',
        'status'
    ];

    protected $casts = [
        'sort' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return $this->attributes['status'] === 'active' ? '启用' : '禁用';
    }

    /**
     * 获取启用的导航页
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 按排序字段排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 搜索范围
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            return $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('url', 'like', '%' . $keyword . '%');
            });
        }
        return $query;
    }
}