<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'v2_messages';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'content',
        'weigh',
        'status',
        'createtime',
        'updatetime',
        'to_all',
        'user_ids',
    ];

    protected $casts = [
        'weigh' => 'integer',
        'createtime' => 'integer',
        'updatetime' => 'integer',
        'to_all' => 'integer',
    ];

    public function userMessages()
    {
        return $this->hasMany(UserMessage::class, 'message_id');
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