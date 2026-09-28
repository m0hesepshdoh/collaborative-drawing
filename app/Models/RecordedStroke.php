<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordedStroke extends Model
{
    public $timestamps = false;

    protected $fillable = ['session_id', 'player_ip', 'points', 'color', 'size', 'drawn_at'];

    protected $casts = ['points' => 'array', 'drawn_at' => 'datetime', 'created_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(DrawingSession::class, 'session_id');
    }
}
