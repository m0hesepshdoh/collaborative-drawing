<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public $timestamps = false;

    protected $fillable = ['session_id', 'ip_address', 'is_bot', 'last_activity_at', 'joined_at', 'left_at'];

    protected $casts = ['is_bot' => 'boolean', 'last_activity_at' => 'datetime', 'joined_at' => 'datetime', 'left_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(DrawingSession::class, 'session_id');
    }
}
