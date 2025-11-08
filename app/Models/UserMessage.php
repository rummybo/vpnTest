<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMessage extends Model
{
    use HasFactory;

    protected $table = 'v2_user_messages';

    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'user_id',
        'viewed',
        'viewtime',
        'createtime',
        'updatetime',
    ];

    protected $casts = [
        'viewed' => 'integer',
        'viewtime' => 'integer',
        'createtime' => 'integer',
        'updatetime' => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}