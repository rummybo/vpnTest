<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NavLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v2_nav_links';

    protected $fillable = [
        'title',
        'link', 
        'icon',
        'sort',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return $this->status ? '显示' : '隐藏';
    }
}