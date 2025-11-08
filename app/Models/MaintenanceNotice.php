<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceNotice extends Model
{
    use HasFactory;

    protected $table = 'v2_maintenance_notices';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'content',
        'weigh',
        'status',
        'createtime',
        'updatetime',
    ];

    protected $casts = [
        'weigh' => 'integer',
        'createtime' => 'integer',
        'updatetime' => 'integer',
    ];

    public function getStatusTextAttribute(): string
    {
        return $this->status === 'normal' ? '显示' : '隐藏';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'normal');
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('weigh')->orderByDesc('id');
    }

    public function getCreatedAtAttribute(): ?string
    {
        return $this->createtime ? date('Y-m-d H:i:s', $this->createtime) : null;
    }

    public function getUpdatedAtAttribute(): ?string
    {
        return $this->updatetime ? date('Y-m-d H:i:s', $this->updatetime) : null;
    }
}